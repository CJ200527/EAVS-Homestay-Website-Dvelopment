<?php
// Shared admin shell: categorized sidebar (logout bottom), header (bell + user), toasts, cascade.
// $theme: '' (default) = current skin; 'ocean' = Deep Ocean Glass pilot (photo + gradient + frosted panels).
function admin_shell_open($title, $active, $glass=false, $navi=false, $theme='') {
  $u = $_SESSION['admin'] ?? 'admin';
  $role = $_SESSION['admin_role'] ?? 'staff';
  $initial = strtoupper(substr($u, 0, 1));
  $owner = ($role === 'owner');
  // Notification activity (guarded: layout used only after db include)
  $notif_n = 0; $notif_items = [];
  if (isset($GLOBALS['conn']) || isset($conn)) {
    $db = $GLOBALS['conn'] ?? $conn;
    if ($db instanceof mysqli && !$db->connect_error) {
      $today = date('Y-m-d');
      $p = $db->query("SELECT id,guest_name,room_id,check_in,check_out FROM bookings WHERE booking_status='Confirmed' ORDER BY created_at DESC LIMIT 5");
      if ($p) while ($r = $p->fetch_assoc()) { $notif_items[] = ['t'=>'Confirmed booking #'.$r['id'].' — '.$r['guest_name'],'s'=>$r['check_in'].' → '.$r['check_out']]; }
      $in = $db->query("SELECT guest_name FROM bookings WHERE booking_status IN ('Confirmed','CheckedIn') AND check_in='$today' LIMIT 5");
      if ($in) while ($r = $in->fetch_assoc()) { $notif_items[] = ['t'=>'Check-in today — '.$r['guest_name'],'s'=>'Arrival']; }
      $out = $db->query("SELECT guest_name FROM bookings WHERE booking_status IN ('CheckedIn','Confirmed') AND check_out='$today' LIMIT 5");
      if ($out) while ($r = $out->fetch_assoc()) { $notif_items[] = ['t'=>'Check-out today — '.$r['guest_name'],'s'=>'Departure']; }
      $notif_n = count($notif_items);
    }
  }
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($title); ?> - EAV's Admin</title>
<link rel="icon" href="../assets/images/logo.jpg">
<link href="../assets/vendor/bootstrap.min.css?v=20251017" rel="stylesheet">
<link href="../assets/css/admin.css?v=20251017" rel="stylesheet">
</head><body<?php $bc=trim(($navi?'navi':($glass?'glass-dash':'')).($theme?' theme-'.$theme:'')); echo $bc?' class="'.$bc.'"':''; ?>>
<?php if($glass && !$navi): ?><div class="glass-bg"></div><?php endif; ?>
<?php if($navi): ?><div class="<?php echo $theme?'theme-bg':'navi-bg'; ?>"></div><?php endif; ?>
<svg xmlns="http://www.w3.org/2000/svg" style="display:none">
<symbol id="i-dash" viewBox="0 0 24 24"><path fill="currentColor" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></symbol>
<symbol id="i-book" viewBox="0 0 24 24"><path fill="currentColor" d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 16H5V10h14v10zM9 14H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2z"/></symbol>
<symbol id="i-bed" viewBox="0 0 24 24"><path fill="currentColor" d="M7 13a2 2 0 0 0-2 2v4h2v-2h14v2h2v-6c0-1.1-.9-2-2-2H7zm0-6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4H5V7z"/></symbol>
<symbol id="i-users" viewBox="0 0 24 24"><path fill="currentColor" d="M16 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm-8 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05A4.22 4.22 0 0 1 17 16.5V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></symbol>
<symbol id="i-out" viewBox="0 0 24 24"><path fill="currentColor" d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8v-2H4V5z"/></symbol>
<symbol id="i-site" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4a8 8 0 1 0 8 8 8 8 0 0 0-8-8zm7 6h-3a15.6 15.6 0 0 0-1-4 6 6 0 0 1 4 4zM12 6a14.5 14.5 0 0 1 1.9 4h-3.8A14.5 14.5 0 0 1 12 6zM4.3 14H5a7 7 0 0 1 0-4H4.3a6 6 0 0 0 0 4zM5 16H4.3a6 6 0 0 0 4 3.3A15.6 15.6 0 0 1 7 16zm3 0a15.6 15.6 0 0 0 1 4 6 6 0 0 1-4-4zm9.7 0h.3a6 6 0 0 0 0-4h-.3a15.6 15.6 0 0 1 1 4 6 6 0 0 1-4 3.3A15.6 15.6 0 0 0 17 16zm0 0"/></symbol>
<symbol id="i-bell" viewBox="0 0 24 24"><path fill="currentColor" d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></symbol>
<symbol id="i-filter" viewBox="0 0 24 24"><path fill="currentColor" d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/></symbol>
<symbol id="i-refresh" viewBox="0 0 24 24"><path fill="currentColor" d="M17.65 6.35A8 8 0 1 0 19.73 14h-2.08a6 6 0 1 1-1.41-6.24L13 11h7V4l-2.35 2.35z"/></symbol>
<symbol id="i-check" viewBox="0 0 24 24"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></symbol>
<symbol id="i-x" viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></symbol>
<symbol id="i-plus" viewBox="0 0 24 24"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></symbol>
<symbol id="i-search" viewBox="0 0 24 24"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0A4.5 4.5 0 1 1 14 9.5 4.5 4.5 0 0 1 9.5 14z"/></symbol>
<symbol id="a-view" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></symbol>
<symbol id="a-edit" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></symbol>
<symbol id="a-up" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22h-9.65a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88z"/></symbol>
<symbol id="a-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 14V2"/><path d="M9 18.22 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2h9.65a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.78z"/></symbol>
<symbol id="a-trash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></symbol>
<symbol id="a-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></symbol>
<symbol id="a-in" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></symbol>
<symbol id="a-out2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></symbol>
<symbol id="a-flag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22v-7"/></symbol>
<symbol id="a-undo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-15-6.7L3 13"/></symbol>
<symbol id="k-inbox" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></symbol>
<symbol id="k-key" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="15" r="4"/><path d="M10.85 12.15L19 4"/><path d="M15 5l3 3"/></symbol>
<symbol id="k-peso" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h9a4 4 0 0 1 0 8H6z"/><path d="M6 4v16"/><path d="M6 12h8"/><path d="M15 9v11"/></symbol>
<symbol id="k-bed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8"/><path d="M3 18h18"/><path d="M3 18v2M21 18v2"/><path d="M7 8V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v4"/></symbol>
</svg>
<aside class="ad-side">
  <a class="ad-brand serif" href="dashboard.php"><img src="../assets/images/logo.jpg" alt="EAV's logo" class="brand-logo"> EAV's</a>
  <nav class="ad-nav">
    <span class="ad-sec">Main</span>
    <a href="dashboard.php" class="side-link <?php echo $active==='dash'?'on':''; ?>"><svg class="nav-ic"><use href="#i-dash"/></svg> Dashboard</a>
    <a href="bookings.php" class="side-link <?php echo $active==='book'?'on':''; ?>"><svg class="nav-ic"><use href="#i-book"/></svg> Booking</a>
    <span class="ad-sec">Manage</span>
    <a href="rooms.php" class="side-link <?php echo $active==='rooms'?'on':''; ?>"><svg class="nav-ic"><use href="#i-bed"/></svg> Rooms</a>
    <?php if($owner): ?><a href="users.php" class="side-link <?php echo $active==='users'?'on':''; ?>"><svg class="nav-ic"><use href="#i-users"/></svg> Users</a><?php endif; ?>
  </nav>
  <nav class="ad-bottom">
    <a href="../index.php" class="side-link"><svg class="nav-ic"><use href="#i-site"/></svg> View Site</a>
    <a href="logout.php" class="side-link out"><svg class="nav-ic"><use href="#i-out"/></svg> Logout</a>
  </nav>
</aside>
<div class="ad-main">
<?php
// Single unified header: glass strip with title + actions + bell + user (replaces old ad-head bar).
function admin_strip($title, $subtitle, $actions='') {
  global $strip_u, $strip_role, $strip_initial, $strip_notif_n, $strip_notif_items;
?>
<div class="head-strip glass">
  <div><h2 class="serif mb-0"><?php echo htmlspecialchars($title); ?></h2><small class="text-muted"><?php echo htmlspecialchars($subtitle); ?></small></div>
  <div class="ad-tools">
    <?php echo $actions; ?>
    <div class="notif-wrap">
      <button type="button" class="notif-bell" aria-label="Notifications" aria-expanded="false">
        <svg class="nav-ic"><use href="#i-bell"/></svg>
        <?php if($strip_notif_n>0): ?><span class="notif-dot"><?php echo $strip_notif_n>9?'9+':$strip_notif_n; ?></span><?php endif; ?>
      </button>
      <div class="notif-panel">
        <b>Activity</b>
        <?php if(!$strip_notif_items): ?><p class="text-muted small mb-0">All clear — no pending or today's movements.</p><?php endif; ?>
        <?php foreach(array_slice($strip_notif_items,0,8) as $n): ?>
        <a href="bookings.php" class="notif-item"><span><?php echo htmlspecialchars($n['t']); ?></span><small><?php echo htmlspecialchars($n['s']); ?></small></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="ad-user"><span class="avatar"><?php echo htmlspecialchars($strip_initial); ?></span>
      <span>Welcome, <b><?php echo htmlspecialchars($strip_u); ?></b><br><small class="badge bg-info"><?php echo htmlspecialchars($strip_role); ?></small></span>
    </div>
  </div>
</div>
<?php }
$GLOBALS['strip_u']=$u; $GLOBALS['strip_role']=$role; $GLOBALS['strip_initial']=$initial;
$GLOBALS['strip_notif_n']=$notif_n; $GLOBALS['strip_notif_items']=$notif_items;
?>
<div class="ad-body dashboard-container">
<div class="toast-container" id="toast-container"><?php echo flash_html(); ?></div>
<?php }
function admin_shell_close() { ?>
</div></div>
<script src="../assets/vendor/bootstrap.bundle.min.js?v=20251017"></script>
<script src="../assets/vendor/fullcalendar.min.js?v=20251017"></script>
<script src="../assets/js/admin.js?v=20251017"></script>
<script>
// Generic confirm modal for admin actions (offline Bootstrap)
function askConfirm(msg, href){
  document.getElementById('cfMsg').textContent = msg;
  document.getElementById('cfBtn').href = href;
  new bootstrap.Modal(document.getElementById('cfModal')).show();
  return false;
}
</script>
<div class="modal fade" id="cfModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title serif">Please confirm</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body" id="cfMsg"></div>
<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><a id="cfBtn" href="#" class="btn btn-ocean" style="background:#0A4A7A;color:#fff">Confirm</a></div>
</div></div></div>
</body></html>
<?php }
