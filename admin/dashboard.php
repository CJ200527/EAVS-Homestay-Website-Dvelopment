<?php require 'auth.php'; require '../config/db.php'; require '../config/helpers.php'; require 'layout.php';
// KPIs
$pending=(int)$conn->query("SELECT COUNT(*) c FROM bookings WHERE booking_status='Confirmed'")->fetch_assoc()['c'];
$checkedIn=(int)$conn->query("SELECT COUNT(*) c FROM bookings WHERE booking_status='CheckedIn'")->fetch_assoc()['c'];
$rev=(float)$conn->query("SELECT COALESCE(SUM(total_amount),0) s FROM bookings WHERE payment_status='Fully Paid'")->fetch_assoc()['s'];
// Absolute MoM change (honest at small n: verifiable counts, never percentages)
$thisM=date('Y-m'); $lastM=date('Y-m',strtotime('first day of last month'));
$revT=(float)$conn->query("SELECT COALESCE(SUM(total_amount),0) s FROM bookings WHERE payment_status='Fully Paid' AND DATE_FORMAT(created_at,'%Y-%m')='$thisM'")->fetch_assoc()['s'];
$revL=(float)$conn->query("SELECT COALESCE(SUM(total_amount),0) s FROM bookings WHERE payment_status='Fully Paid' AND DATE_FORMAT(created_at,'%Y-%m')='$lastM'")->fetch_assoc()['s'];
$revAbs=$revT-$revL;
$bkT=(int)$conn->query("SELECT COUNT(*) c FROM bookings WHERE DATE_FORMAT(created_at,'%Y-%m')='$thisM'")->fetch_assoc()['c'];
$bkL=(int)$conn->query("SELECT COUNT(*) c FROM bookings WHERE DATE_FORMAT(created_at,'%Y-%m')='$lastM'")->fetch_assoc()['c'];
$bkAbs=$bkT-$bkL;
$lastLbl=date('M',strtotime('first day of last month'));
function abs_chip($abs,$fmt){ if($abs==0)return '±0'; $s=$abs>0?'+':'−'; return $s.$fmt(abs($abs)); }
// Analytics filter params (independent of ?start= so nothing collides)
$revM=[3,6,12]; $revN=(int)($_GET['rev']??6); if(!in_array($revN,$revM,true))$revN=6;
$occD=[30,60,90]; $occWin=(int)($_GET['occ']??30); if(!in_array($occWin,$occD,true))$occWin=30;
// Occupancy over $occWin days (booked nights / available nights, non-declined stays)
$rooms=$conn->query("SELECT id,room_name FROM rooms ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$w0=date('Y-m-d',strtotime('-'.($occWin-1).' days')); $w1=date('Y-m-d',strtotime('+1 day'));
$stays=$conn->query("SELECT room_id,check_in,check_out FROM bookings WHERE booking_status IN ('Confirmed','CheckedIn','CheckedOut','Completed') AND check_in<'$w1' AND check_out>'$w0'")->fetch_all(MYSQLI_ASSOC);
$occ=[]; $totBooked=0;
foreach($rooms as $r){
  $bn=0;
  foreach($stays as $s){ if((int)$s['room_id']!==(int)$r['id'])continue;
    $a=max(strtotime($s['check_in']),strtotime($w0)); $b=min(strtotime($s['check_out']),strtotime($w1));
    if($b>$a)$bn+=(int)(($b-$a)/86400);
  }
  $occ[$r['id']]=$bn; $totBooked+=$bn;
}
$avail=count($rooms)*$occWin; $occPct=$avail>0?round(100*$totBooked/$avail):0;
// Available tonight (ops line for demo)
$today=date('Y-m-d'); $tomorrow=date('Y-m-d',strtotime('+1 day'));
$busy=$conn->query("SELECT DISTINCT room_id FROM bookings WHERE booking_status IN ('Confirmed','CheckedIn') AND check_in<'$tomorrow' AND check_out>'$today'")->fetch_all(MYSQLI_ASSOC);
$busyIds=array_map(fn($x)=>(int)$x['room_id'],$busy);
$freeNames=[]; foreach($rooms as $r){ if(!in_array((int)$r['id'],$busyIds))$freeNames[]=$r['room_name']; }
// Revenue per month (range = $revN months, Fully Paid sums by created_at)
$months=[]; for($i=$revN-1;$i>=0;$i--)$months[]=date('Y-m',strtotime("-$i months"));
$revByM=[]; foreach($months as $m)$revByM[$m]=0;
$rbm=$conn->query("SELECT DATE_FORMAT(created_at,'%Y-%m') m,SUM(total_amount) s FROM bookings WHERE payment_status='Fully Paid' AND created_at>=DATE_FORMAT(DATE_SUB(NOW(),INTERVAL $revN MONTH),'%Y-%m-01') GROUP BY m")->fetch_all(MYSQLI_ASSOC);
foreach($rbm as $r)$revByM[$r['m']]=(float)$r['s'];
admin_shell_open('Dashboard','dash',false,true,'ocean');
?>
<?php if (isset($_GET['welcome'])): ?>
<div id="skeleton-overlay" class="show">
  <div class="skeleton-sidebar">
    <div class="skeleton-block" style="height:28px;width:60%"></div>
    <div class="skeleton-block" style="height:36px"></div><div class="skeleton-block" style="height:36px"></div>
    <div class="skeleton-block" style="height:36px"></div><div class="skeleton-block" style="height:36px"></div>
  </div>
  <div class="skeleton-main">
    <div class="skeleton-header"><div class="skeleton-block" style="height:24px;width:220px"></div><div class="skeleton-block" style="height:40px;width:180px"></div></div>
    <div style="padding:1.5rem;display:grid;grid-template-columns:repeat(4,1fr);gap:1rem">
      <div class="skeleton-block" style="height:150px"></div><div class="skeleton-block" style="height:150px"></div>
      <div class="skeleton-block" style="height:150px"></div><div class="skeleton-block" style="height:150px"></div>
    </div>
    <div style="padding:0 1.5rem"><div class="skeleton-block" style="height:320px"></div></div>
  </div>
</div>
<?php endif; ?>
<?php
admin_strip('Dashboard', date('l, F j, Y').' • '.count($rooms).' rooms • ocean-view ops', '');
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3"><div class="kpi-dash glass has-link">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#k-inbox"/></svg></span>
    <?php if($bkT+$bkL>0): ?><span class="delta delta-abs" title="<?php echo $bkT; ?> created in <?php echo date('M'); ?> vs <?php echo $bkL; ?> in <?php echo $lastLbl; ?>"><?php echo abs_chip($bkAbs,fn($v)=>$v.' vs '.$lastLbl); ?></span><?php else: ?><span class="delta delta-abs">—</span><?php endif; ?></div>
    <div class="lbl">Confirmed Bookings</div><div class="big"><?php echo $pending; ?></div>
    <div class="kpi-sub"><span class="sub"><?php echo $bkT; ?> created in <?php echo date('M'); ?></span><span class="kpi-more">Review →</span></div>
    <a class="stretched-link" href="bookings.php?filter=Confirmed" aria-label="View confirmed bookings"></a>
  </div></div>
  <div class="col-6 col-xl-3"><div class="kpi-dash glass has-link">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#k-key"/></svg></span><span class="delta delta-live">● live</span></div>
    <div class="lbl">Checked-In Now</div><div class="big"><?php echo $checkedIn; ?></div>
    <div class="kpi-sub"><span class="sub">guests on site tonight</span><span class="kpi-more">View →</span></div>
    <a class="stretched-link" href="bookings.php?filter=CheckedIn" aria-label="View checked-in bookings"></a>
  </div></div>
  <div class="col-6 col-xl-3"><div class="kpi-dash glass">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#k-peso"/></svg></span>
    <?php if($revT+$revL>0): ?><span class="delta delta-abs" title="<?php echo peso($revT); ?> collected in <?php echo date('M'); ?> vs <?php echo peso($revL); ?> in <?php echo $lastLbl; ?>"><?php echo abs_chip($revAbs,fn($v)=>'₱'.number_format($v).' vs '.$lastLbl); ?></span><?php else: ?><span class="delta delta-abs">—</span><?php endif; ?></div>
    <div class="lbl">Total Revenue</div><div class="big"><?php echo peso($rev); ?></div>
    <div class="kpi-sub"><span class="sub"><?php echo peso($revT); ?> collected in <?php echo date('M'); ?></span></div>
  </div></div>
  <div class="col-6 col-xl-3"><div class="kpi-dash glass">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#k-bed"/></svg></span><span class="delta"><?php echo $totBooked; ?>/<?php echo $avail; ?> nights</span></div>
    <div class="lbl">Occupancy · <?php echo $occWin; ?> days</div><div class="big"><?php echo $occPct; ?>%</div>
    <div class="kpi-sub"><span class="sub">booked room-nights</span></div>
  </div></div>
</div>
<div class="row g-3 mb-3">
<?php
// Timeline: 14-day window
$nDays=14;
$start = $_GET['start'] ?? date('Y-m-d');
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$start))$start=date('Y-m-d');
$days=[]; for($i=0;$i<$nDays;$i++)$days[]=date('Y-m-d',strtotime($start." +$i days"));
$end=end($days); $dayIdx=[]; foreach($days as $i=>$d)$dayIdx[$d]=$i; $dayW=100/$nDays;
$tl=$conn->query("SELECT b.id,b.guest_name,b.room_id,b.check_in,b.check_out,b.booking_status FROM bookings b WHERE b.booking_status IN ('Confirmed','CheckedIn') AND b.check_in<='$end' AND b.check_out>='$days[0]' ORDER BY b.check_in")->fetch_all(MYSQLI_ASSOC);
$tlColors=['Confirmed'=>'#0E6BA8','CheckedIn'=>'#1e8e5a'];
$prev=date('Y-m-d',strtotime($start.' -7 days')); $next=date('Y-m-d',strtotime($start.' +7 days'));
?>
<div class="col-12"><div class="ad-card glass"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
  <h5 class="serif mb-0">Room Timeline · 14 days</h5>
  <div class="d-flex align-items-center gap-2">
    <span class="tl-range"><?php echo date('M j',strtotime($days[0])).' – '.date('M j, Y',strtotime($end)); ?></span>
  <a href="?start=<?php echo $prev; ?><?php echo $revN!==6?'&rev='.$revN:''; ?><?php echo $occWin!==30?'&occ='.$occWin:''; ?>" class="btn btn-sm btn-outline-secondary">← Prev</a>
  <a href="dashboard.php<?php echo $revN!==6?'?rev='.$revN:''; ?><?php echo $occWin!==30?($revN!==6?'&':'?').'occ='.$occWin:''; ?>" class="btn btn-sm btn-outline-secondary">Today</a>
  <a href="?start=<?php echo $next; ?><?php echo $revN!==6?'&rev='.$revN:''; ?><?php echo $occWin!==30?'&occ='.$occWin:''; ?>" class="btn btn-sm btn-outline-secondary">Next →</a></div>
</div>
<div class="legend mb-2"><span><i style="background:#0E6BA8"></i>Confirmed</span><span><i style="background:#1e8e5a"></i>Checked-In</span></div>
<div class="tl-scroll"><div class="tl-grid">
  <div class="tl-row head"><div class="tl-room">Room</div><div class="tl-track" style="height:22px"><?php foreach($days as $d): ?><div class="tl-day" style="left:<?php echo $dayIdx[$d]*$dayW; ?>%;width:<?php echo $dayW; ?>%"><span><?php echo date('j',strtotime($d)); ?></span></div><?php endforeach; ?></div></div>
  <?php foreach($rooms as $r): ?>
  <div class="tl-row"><div class="tl-room"><?php echo esc($r['room_name']); ?></div>
  <div class="tl-track"><?php foreach($days as $d): $isToday=$d===date('Y-m-d'); ?><div class="tl-day<?php echo $isToday?' today':''; ?>" style="left:<?php echo $dayIdx[$d]*$dayW; ?>%;width:<?php echo $dayW; ?>%"><?php if($isToday): ?><span>●</span><?php endif; ?></div><?php endforeach; ?>
  <?php foreach($tl as $b): if((int)$b['room_id']!==(int)$r['id'])continue;
    $s=max(strtotime($b['check_in']),strtotime($days[0])); $e=min(strtotime($b['check_out']),strtotime($end.' +1 day'));
    if($e<=$s)continue;
    $left=100*($s-strtotime($days[0]))/($nDays*86400); $wd=100*($e-$s)/($nDays*86400); ?>
    <a class="tl-bar" title="<?php echo esc($b['guest_name'].' · '.$b['check_in'].' → '.$b['check_out']); ?>" href="bookings.php?q=<?php echo urlencode($b['guest_name']); ?>" style="left:<?php echo max($left,0); ?>%;width:<?php echo max($wd,3); ?>%;background:<?php echo $tlColors[$b['booking_status']]??'#0E6BA8'; ?>"><?php echo esc($b['guest_name']); ?></a>
  <?php endforeach; ?></div></div>
  <?php endforeach; ?>
</div></div>
</div></div>
  <div class="col-md-7 d-flex"><div class="ad-card glass h-100 w-100"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><h5 class="serif mb-0">Revenue · last <?php echo $revN; ?> months</h5>
    <div class="d-flex align-items-center gap-2">
      <span class="seg"><?php foreach($revM as $o): ?><a class="<?php echo $o===$revN?'on':''; ?>" href="?rev=<?php echo $o; ?><?php echo $occWin!==30?'&occ='.$occWin:''; ?>"><?php echo $o; ?>m</a><?php endforeach; ?></span>
      <span class="rev-total"><?php echo peso($rev); ?> total</span></div></div>
    <?php
    $W=640;$H=250;$Pl=56;$Pr=16;$Pt=14;$Pb=32;
    $revMax=max(array_values($revByM)); if($revMax<=0)$revMax=1;
    function fmtk($v){ if($v>=1000)return '₱'.rtrim(rtrim(number_format($v/1000,1), '0'),'.').'k'; return '₱'.number_format($v); }
    $mnames=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $n=count($months); $step=($W-$Pl-$Pr)/max($n-1,1);
    // Pointy vertices: straight-line segments only (no C curves, no smoothing) = crypto style
    $pts=[]; foreach($months as $i=>$m){ $pts[]=[$Pl+$i*$step, $H-$Pb-((float)$revByM[$m]/$revMax)*($H-$Pt-$Pb)]; }
    $line=''; foreach($pts as $i=>$p){ $line.=($i?'L':'M').round($p[0],1).','.round($p[1],1); }
    $area=$line.'L'.round($pts[$n-1][0],1).','.($H-$Pb).'L'.round($pts[0][0],1).','.($H-$Pb).'Z';
    ?>
    <svg viewBox="0 0 <?php echo $W; ?> <?php echo $H; ?>" style="width:100%;height:auto" role="img" aria-label="Monthly revenue area chart">
      <defs><linearGradient id="revArea" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#2dd4bf" stop-opacity=".55"/><stop offset="1" stop-color="#2dd4bf" stop-opacity="0"/>
      </linearGradient></defs>
      <?php for($g=0;$g<=4;$g++): $gy=$Pt+$g*(($H-$Pt-$Pb)/4); $pv=$revMax*(1-$g/4); ?>
      <line x1="<?php echo $Pl; ?>" y1="<?php echo $gy; ?>" x2="<?php echo $W-$Pr; ?>" y2="<?php echo $gy; ?>" stroke="rgba(255,255,255,.16)" stroke-width="1"/>
      <text x="<?php echo $Pl-8; ?>" y="<?php echo $gy+4; ?>" text-anchor="end" font-size="12" fill="#9fb3b8"><?php echo fmtk($pv); ?></text>
      <?php endfor; ?>
      <path d="<?php echo $area; ?>" fill="url(#revArea)" stroke="none"/>
      <path d="<?php echo $line; ?>" fill="none" stroke="#2dd4bf" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      <?php foreach($months as $i=>$m):
        $v=$revByM[$m]; $x=$pts[$i][0]; $y=$pts[$i][1];
        $isCur=($m===date('Y-m'));
        $tip=$mnames[(int)substr($m,5)-1].' '.substr($m,0,4).': '.peso($v);
      ?>
      <circle cx="<?php echo round($x,1); ?>" cy="<?php echo round($y,1); ?>" r="4" fill="<?php echo $isCur?'#fff':'#2dd4bf'; ?>" stroke="#2dd4bf" stroke-width="2"><title><?php echo esc($tip); ?></title></circle>
      <?php if($v>0): ?><text x="<?php echo round($x,1); ?>" y="<?php echo round($y-11,1); ?>" text-anchor="middle" font-size="11" fill="#dfeeef"><?php echo fmtk($v); ?></text><?php endif; ?>
      <text x="<?php echo round($x,1); ?>" y="<?php echo $H-10; ?>" text-anchor="middle" font-size="12" fill="#9fb3b8"><?php echo $mnames[(int)substr($m,5)-1]; ?></text>
      <?php endforeach; ?>
    </svg>
    <small class="text-muted">Fully Paid sums per month (created date). Hover a point for the exact figure.</small>
  </div></div>
  <div class="col-md-5 d-flex"><div class="ad-card glass h-100 w-100"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><h5 class="serif mb-0">Occupancy · <?php echo $occWin; ?> days</h5>
    <span class="seg"><?php foreach($occD as $o): ?><a class="<?php echo $o===$occWin?'on':''; ?>" href="?occ=<?php echo $o; ?><?php echo $revN!==6?'&rev='.$revN:''; ?>"><?php echo $o; ?>d</a><?php endforeach; ?></span></div>
    <div class="donut-wrap">
      <?php $C=2*M_PI*56; ?>
      <div class="donut-hole"><svg class="donut" viewBox="0 0 140 140"><circle class="bgc" cx="70" cy="70" r="56"/><circle class="fgc" cx="70" cy="70" r="56" stroke-dasharray="<?php echo round($C*$occPct/100); ?> <?php echo $C; ?>"/></svg><span class="donut-c"><?php echo $occPct; ?>%</span></div>
      <div><?php foreach($rooms as $r): $rn=$occ[$r['id']]??0; $rp=round(100*$rn/$occWin); ?>
        <div class="bar-row"><span class="lbl"><?php echo esc($r['room_name']); ?></span><span class="bar"><i style="width:<?php echo $rp; ?>%"></i></span><span class="v"><?php echo $rn; ?>/<?php echo $occWin; ?></span></div>
      <?php endforeach; ?></div>
    </div>
    <div class="avail-line"><?php echo $freeNames ? 'Available tonight: <b>'.esc(implode(' · ',$freeNames)).'</b>' : 'Fully booked tonight.'; ?></div>
  </div></div>
</div>
<div class="row g-3 mb-3">
<div class="col-12"><div class="ad-card glass h-100 w-100"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><h5 class="serif mb-0">Recent Activity</h5><a class="kpi-more" href="bookings.php">Open bookings →</a></div>
<?php
$recent=$conn->query("SELECT id,guest_name,room_id,check_in,check_out,booking_status,created_at FROM bookings ORDER BY created_at DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
if(!$recent): ?><p class="text-muted mb-0">No activity yet.</p><?php endif; ?>
<?php foreach($recent as $rc): ?>
<div class="d-flex justify-content-between align-items-center border-bottom py-2">
  <div><b>#<?php echo $rc['id']; ?> <?php echo esc($rc['guest_name']); ?></b><br><small class="text-muted"><?php echo esc($rc['check_in']); ?> → <?php echo esc($rc['check_out']); ?> • <?php echo esc($rc['created_at']); ?></small></div>
  <div class="d-flex gap-2 align-items-center"><?php echo status_badge($rc['booking_status']); ?><a class="btn-act a-view" title="Open in bookings" href="bookings.php?q=<?php echo urlencode($rc['guest_name']); ?>"><svg class="act-icon"><use href="#a-view"/></svg></a></div>
</div>
<?php endforeach; ?>
</div>
</div>
<?php admin_shell_close(); ?>
