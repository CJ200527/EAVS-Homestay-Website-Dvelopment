# verify.ps1 — EAV's Homestay verification gate (run after every change block)
# Catches what `php -l` and HTTP 200 CANNOT see: template tags and markup leaked
# into rendered output (e.g. an orphaned `?>` printed as visible text).
#
# Usage:  powershell -File tools/verify.ps1
$ErrorActionPreference = 'Stop'
$src    = Split-Path -Parent $PSScriptRoot
$mirror = 'C:\xampp\htdocs\EAVS Homestay Website Development'
$base   = 'http://localhost/EAVS%20Homestay%20Website%20Development'
$fail   = 0
function Bad($m){ Write-Host "  FAIL  $m" -ForegroundColor Red; $script:fail++ }
function Ok($m){ Write-Host "  ok    $m" -ForegroundColor Green }

Add-Type -AssemblyName System.Net.Http

Write-Host "`n[1] Source <-> mirror parity" -ForegroundColor Cyan
foreach ($f in @('admin\dashboard.php','admin\bookings.php','admin\rooms.php','admin\users.php',
                 'admin\layout.php','config\helpers.php','assets\css\admin.css','assets\js\admin.js')) {
    $a = (Get-FileHash "$src\$f" -Algorithm MD5).Hash
    $b = (Get-FileHash "$mirror\$f" -Algorithm MD5).Hash
    if ($a -eq $b) { Ok "$f in sync" } else { Bad "$f DIFFERS from mirror" }
}

Write-Host "`n[2] PHP lint" -ForegroundColor Cyan
Get-ChildItem $src -Recurse -Include *.php -File |
    Where-Object { $_.FullName -notmatch 'vendor|_backup' } | ForEach-Object {
        & 'C:\xampp\php\php.exe' -l $_.FullName 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) { Bad "lint failed: $($_.Name)" }
    }
Ok 'all php files linted'

Write-Host "`n[3] Rendered-output leak scan" -ForegroundColor Cyan
# The gate that would have caught the orphaned `?>` in v0.9.8-v0.10.0.
$h = New-Object System.Net.Http.HttpClientHandler
$h.CookieContainer = New-Object System.Net.CookieContainer
$c = New-Object System.Net.Http.HttpClient($h)
$m = New-Object System.Net.Http.MultipartFormDataContent
$m.Add((New-Object System.Net.Http.StringContent('admin')), 'username')
$m.Add((New-Object System.Net.Http.StringContent('123')),  'password')
$c.PostAsync("$base/admin/login.php", $m).Result | Out-Null

$pages = @('admin/dashboard.php','admin/bookings.php','admin/rooms.php','admin/users.php','admin/login.php')
foreach ($p in $pages) {
    $d = $c.GetStringAsync("$base/$p").Result
    if ($d.Contains('?>'))  { Bad "$p leaks a literal '?>' into the page" }
    else                    { Ok   "$p no leaked PHP tags" }
    if ($d.Contains('toast-icon')) { Bad "$p still emits the removed emoji toast icon" }
    if ($d -match 'Warning:|Notice:|Fatal error:|Deprecated:') { Bad "$p leaks a PHP error into output" }
}
# NOTE: $c stays open - section [5] reuses it. Disposed at the very end.

Write-Host "`n[4] Route status" -ForegroundColor Cyan
foreach ($u in @('index.php','rooms.php','room-details.php?id=1','booking.php',
                 'booking-success.php?id=9',
                 'check-availability.php?room_id=1&check_in=2026-10-25&check_out=2026-10-27')) {
    try { $r = Invoke-WebRequest "$base/$u" -UseBasicParsing -TimeoutSec 15; Ok "$($r.StatusCode)  $u" }
    catch { Bad "ERR $u" }
}

Write-Host "`n[5] Booking POST smoke test" -ForegroundColor Cyan
# GET-only checks cannot see failures in POST handlers (bind_param/insert bugs are
# invisible until the form is actually submitted). These assert the guard rules and
# then remove the rows they create.
$proofFile = Join-Path $mirror 'assets\images\logo.jpg'   # any small jpg stands in for a payment shot
$ci = (Get-Date).AddDays(40).ToString('yyyy-MM-dd')
$co = (Get-Date).AddDays(42).ToString('yyyy-MM-dd')
$mp = New-Object System.Net.Http.MultipartFormDataContent
foreach ($kv in @(@('room_id','1'),@('guest_name','verify-bot'),@('phone','09000000001'),
                  @('num_guests','2'),@('check_in',$ci),@('check_out',$co),
                  @('payment_type','Advance'),@('payment_method','Cash'),@('agree_terms','1'))) {
    $mp.Add((New-Object System.Net.Http.StringContent([string]$kv[1])), $kv[0])
}
# Proof is now required for EVERY method, cash included.
$pc = New-Object System.Net.Http.ByteArrayContent(,[System.IO.File]::ReadAllBytes($proofFile))
$pc.Headers.ContentType = [System.Net.Http.Headers.MediaTypeHeaderValue]::Parse('image/jpeg')
$mp.Add($pc,'proof','pay.jpg')
$r1 = $c.PostAsync("$base/booking.php", $mp).Result
$b1 = $r1.Content.ReadAsStringAsync().Result
if ($b1 -match 'Fatal error|Uncaught') { Bad "booking POST throws a PHP fatal error" }
elseif ($b1 -notmatch 'receiptModal') { Bad "booking POST did not confirm (no receipt)" }
else { Ok "booking POST confirms instantly" }

$mp2 = New-Object System.Net.Http.MultipartFormDataContent
foreach ($kv in @(@('room_id','1'),@('guest_name','verify-bot'),@('phone','09000000002'),
                  @('num_guests','2'),@('check_in',$ci),@('check_out',$co),
                  @('payment_type','Advance'),@('payment_method','Cash'),@('agree_terms','1'))) {
    $mp2.Add((New-Object System.Net.Http.StringContent([string]$kv[1])), $kv[0])
}
$pc2 = New-Object System.Net.Http.ByteArrayContent(,[System.IO.File]::ReadAllBytes($proofFile))
$pc2.Headers.ContentType = [System.Net.Http.Headers.MediaTypeHeaderValue]::Parse('image/jpeg')
$mp2.Add($pc2,'proof','pay.jpg')
$r2 = $c.PostAsync("$base/booking.php", $mp2).Result
$b2 = $r2.Content.ReadAsStringAsync().Result
if ($b2 -match 'receiptModal') { Bad "DOUBLE-BOOKING NOT BLOCKED - same room+dates was accepted twice" }
else { Ok "overlap guard rejects the same room+dates" }

$mp3 = New-Object System.Net.Http.MultipartFormDataContent
foreach ($kv in @(@('room_id','1'),@('guest_name','verify-bot'),@('phone','09000000003'),
                  @('num_guests','2'),@('check_in',$ci),@('check_out',$co),
                  @('payment_type','Advance'),@('payment_method','Cash'))) {
    $mp3.Add((New-Object System.Net.Http.StringContent([string]$kv[1])), $kv[0])
}
$pc3 = New-Object System.Net.Http.ByteArrayContent(,[System.IO.File]::ReadAllBytes($proofFile))
$pc3.Headers.ContentType = [System.Net.Http.Headers.MediaTypeHeaderValue]::Parse('image/jpeg')
$mp3.Add($pc3,'proof','pay.jpg')
$b3 = $c.PostAsync("$base/booking.php", $mp3).Result.Content.ReadAsStringAsync().Result
if ($b3 -match 'Terms') { Ok "terms gate blocks submit without agreement" }
else { Bad "booking accepted without agreeing to the Terms" }

# clean up the rows this test created
& 'C:\xampp\mysql\bin\mysql.exe' -u root -e "USE eavs_homestay; DELETE FROM bookings WHERE guest_name='verify-bot';" 2>$null

# Admin walk-in POST. This path had its own bind_param defect that only a real
# submit could surface -- the guest-form test did not cover it.
$wi = New-Object System.Net.Http.MultipartFormDataContent
$wi.Add((New-Object System.Net.Http.StringContent('1')),'new_booking')
$wiDates = @{
    'check_in'   = (Get-Date).AddDays(100).ToString('yyyy-MM-dd')
    'check_out'  = (Get-Date).AddDays(102).ToString('yyyy-MM-dd')
    'room_id'    = '1'
    'guest_name' = 'verify-walkin'
    'phone'      = '09000000009'
    'num_guests' = '2'
    'payment_type'  = 'Advance'
    'payment_method'= 'Cash'
}
foreach ($k in $wiDates.Keys) { $wi.Add((New-Object System.Net.Http.StringContent([string]$wiDates[$k])), $k) }
$wpc = New-Object System.Net.Http.ByteArrayContent(,[System.IO.File]::ReadAllBytes($proofFile))
$wpc.Headers.ContentType = [System.Net.Http.Headers.MediaTypeHeaderValue]::Parse('image/jpeg')
$wi.Add($wpc,'walkin_proof','walkin.jpg')
$bw = $c.PostAsync("$base/admin/bookings.php", $wi).Result.Content.ReadAsStringAsync().Result
if ($bw -match 'Fatal error|Uncaught') { Bad "admin walk-in POST throws a PHP fatal error" }
elseif ($bw -match 'Attach a photo of the payment') { Bad "admin walk-in rejected despite a proof being uploaded" }
else { Ok "admin walk-in creates a booking" }
& 'C:\xampp\mysql\bin\mysql.exe' -u root -e "USE eavs_homestay; DELETE FROM bookings WHERE guest_name='verify-walkin';" 2>$null
$c.Dispose()

Write-Host "`n[6] Apache error-log delta" -ForegroundColor Cyan
# Only entries appended since the recorded baseline count - historical warnings
# from earlier sessions must not fail today's run.
$log = 'C:\xampp\apache\logs\error.log'
$state = Join-Path $PSScriptRoot '.verify-baseline'
$total = (Get-Content $log | Measure-Object -Line).Lines
if (Test-Path $state) { $base = [int](Get-Content $state -Raw).Trim() } else { $base = $total; Set-Content -LiteralPath $state -Value $total }
$new = $total - $base
if ($new -gt 0) {
    Get-Content $log -Tail $new | ForEach-Object { if ($_ -match 'EAVS Homestay') { Bad $_ } }
}
if ($new -le 0) { Ok "no new log entries since baseline ($total lines)" }
else { Set-Content -LiteralPath $state -Value $total; Write-Host "  ..   baseline advanced $base -> $total" }

Write-Host "`n---------------------------------------" -ForegroundColor Cyan
if ($fail -eq 0) { Write-Host "VERIFY: ALL GREEN" -ForegroundColor Green }
else { Write-Host "VERIFY: $fail FAILURE(S)" -ForegroundColor Red; exit 1 }