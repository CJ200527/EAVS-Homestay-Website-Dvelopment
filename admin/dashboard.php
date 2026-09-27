<?php require 'auth.php'; require '../config/db.php'; require '../config/helpers.php'; require 'layout.php';
$pending=(int)$conn->query("SELECT COUNT(*) c FROM bookings WHERE booking_status='Pending'")->fetch_assoc()['c'];
$checkedIn=(int)$conn->query("SELECT COUNT(*) c FROM bookings WHERE booking_status='CheckedIn'")->fetch_assoc()['c'];
$totalIn=(int)$conn->query("SELECT COUNT(*) c FROM bookings WHERE booking_status IN ('CheckedIn','CheckedOut','Completed')")->fetch_assoc()['c'];
$rev=$conn->query("SELECT COALESCE(SUM(total_amount),0) s FROM bookings WHERE payment_status='Fully Paid'")->fetch_assoc()['s'];
$byStatus=$conn->query("SELECT booking_status s,COUNT(*) c FROM bookings GROUP BY s")->fetch_all(MYSQLI_ASSOC);
$maxS=1; foreach($byStatus as $r){$maxS=max($maxS,(int)$r['c']);}
$byMonth=$conn->query("SELECT DATE_FORMAT(created_at,'%Y-%m') m,SUM(total_amount) s FROM bookings WHERE payment_status='Fully Paid' GROUP BY m ORDER BY m DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
$maxM=1; foreach($byMonth as $r){$maxM=max($maxM,(float)$r['s']);}
admin_shell_open('Dashboard','dash');
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3"><div class="kpi"><small>Pending Booking</small><div class="n"><?php echo $pending; ?></div></div></div>
  <div class="col-6 col-md-3"><div class="kpi"><small>Checked-In Now</small><div class="n"><?php echo $checkedIn; ?></div></div></div>
  <div class="col-6 col-md-3"><div class="kpi"><small>Total Check-In</small><div class="n"><?php echo $totalIn; ?></div></div></div>
  <div class="col-6 col-md-3"><div class="kpi"><small>Revenue (Fully Paid)</small><div class="n"><?php echo peso($rev); ?></div></div></div>
</div>
<div class="row g-3 mb-3">
  <div class="col-md-6"><div class="ad-card"><h5 class="serif">Bookings by Status</h5>
    <?php if(!$byStatus): ?><p class="text-muted">No bookings yet.</p><?php endif; ?>
    <?php foreach($byStatus as $r): $w=round(100*(int)$r['c']/$maxS); ?>
    <div class="bar-row"><span class="lbl"><?php echo status_badge($r['s']); ?></span><span class="bar"><i style="width:<?php echo $w; ?>%"></i></span><span class="v"><?php echo (int)$r['c']; ?></span></div>
    <?php endforeach; ?>
  </div></div>
  <div class="col-md-6"><div class="ad-card"><h5 class="serif">Revenue by Month (Fully Paid)</h5>
    <?php if(!$byMonth): ?><p class="text-muted">No collected revenue yet.</p><?php endif; ?>
    <?php foreach($byMonth as $r): $w=round(100*(float)$r['s']/$maxM); ?>
    <div class="bar-row"><span class="lbl"><?php echo esc($r['m']); ?></span><span class="bar"><i style="width:<?php echo $w; ?>%"></i></span><span class="v"><?php echo peso($r['s']); ?></span></div>
    <?php endforeach; ?>
  </div></div>
</div>
<div class="ad-card"><h5 class="serif">Booking Calendar</h5>
<div id="cal"></div>
<div id="calFallback" style="display:none"><p class="text-muted">Calendar library unavailable — upcoming approved stays:</p>
<table class="table table-sm table-bordered">
<tr><th>Room</th><th>Guest</th><th>Dates</th><th>Status</th></tr>
<?php $fb=$conn->query("SELECT b.guest_name,b.check_in,b.check_out,b.booking_status,r.room_name FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.booking_status IN ('Approved','CheckedIn') ORDER BY b.check_in LIMIT 20");
while($f=$fb->fetch_assoc()): ?><tr><td><?php echo esc($f['room_name']); ?></td><td><?php echo esc($f['guest_name']); ?></td><td><?php echo esc($f['check_in']); ?> → <?php echo esc($f['check_out']); ?></td><td><?php echo status_badge($f['booking_status']); ?></td></tr><?php endwhile; ?>
</table></div>
<p class="mt-2"><a href="bookings.php" class="btn btn-ocean btn-sm">Manage bookings →</a></p>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  try{
    if(typeof FullCalendar==='undefined') throw 'no-fc';
    new FullCalendar.Calendar(document.getElementById('cal'),{initialView:'dayGridMonth',height:450,events:'calendar-events.php'}).render();
  }catch(e){ document.getElementById('calFallback').style.display='block'; }
});
</script>
<?php admin_shell_close(); ?>
