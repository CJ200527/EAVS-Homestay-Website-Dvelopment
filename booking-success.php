<?php require 'config/db.php'; require 'config/helpers.php';
$id=(int)($_GET['id']??0);
$st=$conn->prepare("SELECT b.*, r.room_name, r.image_urls FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=?");
$st->bind_param('i',$id);$st->execute();$b=$st->get_result()->fetch_assoc();
require 'includes/header.php';
if(!$b){echo "<div class='container section'><div class='alert alert-danger'>Booking not found.</div></div>"; require 'includes/footer.php'; exit;}
$nn=nights_between($b['check_in'],$b['check_out']);
?>
<div class="container section text-center">
<p class="text-muted">Reference #<?php echo $b['id']; ?> • Details below — receipt popup included.</p>
<div class="card mx-auto" style="max-width:560px"><div class="card-body">
<p><b><?php echo esc($b['guest_name']); ?></b> • <?php echo esc($b['room_name']); ?><br>
<?php echo esc($b['check_in']); ?> → <?php echo esc($b['check_out']); ?> (<?php echo $nn; ?> night<?php echo $nn===1?'':'s'; ?>) • <?php echo peso($b['total_amount']); ?></p>
<p><?php echo status_badge($b['payment_status']); ?> <?php echo status_badge($b['booking_status']); ?></p>
<button class="btn btn-ocean btn-pill" data-bs-toggle="modal" data-bs-target="#receiptModal">Show receipt</button>
<a href="index.php" class="btn btn-outline-ink btn-pill ms-2">Back to Home</a>
<a href="booking.php" class="btn btn-warning btn-pill ms-2">Book another stay</a>
</div></div>
</div>
<!-- Receipt modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content" id="receiptCard">
<div class="modal-header" style="border-bottom:2px solid var(--wood)">
<img src="assets/images/logo.jpg" alt="EAV's Homestay logo" class="brand-logo" style="width:48px;height:48px">
<div class="ms-2"><h5 class="serif mb-0">EAV's Homestay</h5><small class="text-muted">Catohugan, Mahinog Camiguin, 9101 Phil.</small></div>
</div>
<div class="modal-body"><div class="row g-3">
<div class="col-md-6">
<h4 class="serif">Booking Successful ✓</h4>
<p class="text-muted">Reference #<?php echo $b['id']; ?> — <b>Confirmed</b>. Your dates are reserved.<br>We will contact you at <?php echo esc($b['phone']); ?>.</p>
<table class="spec-table">
<tr><td><b>Guest:</b></td><td><?php echo esc($b['guest_name']); ?> (<?php echo (int)$b['num_guests']; ?>)</td></tr>
<tr><td><b>Room:</b></td><td><?php echo esc($b['room_name']); ?></td></tr>
<tr><td><b>Dates:</b></td><td><?php echo esc($b['check_in']); ?> → <?php echo esc($b['check_out']); ?> (<?php echo $nn; ?>)</td></tr>
<tr><td><b>Total:</b></td><td><?php echo peso($b['total_amount']); ?> • <?php echo esc($b['payment_type']); ?> via <?php echo esc($b['payment_method']); ?></td></tr>
<tr><td><b>Reservation fee:</b></td><td><?php echo peso($b['reservation_fee']); ?> (non-refundable)</td></tr>
<tr><td><b>Paid now:</b></td><td><?php echo peso($b['amount_paid']); ?> · Balance on arrival: <?php echo peso($b['total_amount']-$b['amount_paid']); ?></td></tr>
<tr><td><b>Check-in:</b></td><td>From <?php echo CHECK_IN_TIME; ?> · Check-out by <?php echo CHECK_OUT_TIME; ?></td></tr>
<tr><td><b>Status:</b></td><td><?php echo status_badge($b['payment_status']); ?> <?php echo status_badge($b['booking_status']); ?></td></tr>
</table>
</div>
<div class="col-md-6"><img id="receiptImg" src="<?php echo esc($b['image_urls']); ?>" alt="room" style="width:100%;aspect-ratio:4/3;object-fit:contain;background:var(--gray);border-radius:.6rem;border:1px solid #e9e2d5"></div>
</div></div>
<div class="modal-footer">
<button id="saveReceipt" class="btn btn-pill btn-receipt-save">Save</button>
<a href="index.php" class="btn btn-pill btn-receipt-confirm">Confirm</a>
<button class="btn btn-pill btn-receipt-close" data-bs-dismiss="modal">Close</button>
</div>
</div></div></div>
<script>
// Auto-open receipt on arrival; Save renders an offline canvas PNG proof.
document.addEventListener('DOMContentLoaded',()=>{new bootstrap.Modal(document.getElementById('receiptModal')).show();});
const BK={id:'<?php echo $b['id']; ?>',guest:'<?php echo addslashes($b['guest_name']); ?>',room:'<?php echo addslashes($b['room_name']); ?>',ci:'<?php echo $b['check_in']; ?>',co:'<?php echo $b['check_out']; ?>',nights:'<?php echo $nn; ?>',total:'<?php echo peso($b['total_amount']); ?>',fee:'<?php echo peso($b['reservation_fee']); ?>',paid:'<?php echo peso($b['amount_paid']); ?>',bal:'<?php echo peso($b['total_amount']-$b['amount_paid']); ?>',hours:'<?php echo addslashes(CHECK_IN_TIME.' / '.CHECK_OUT_TIME); ?>',pay:'<?php echo addslashes($b['payment_type'].' via '.$b['payment_method']); ?>',status:'<?php echo addslashes($b['booking_status'].' · '.$b['payment_status']); ?>',img:'<?php echo $b['image_urls']; ?>',logo:'assets/images/logo.jpg'};
function loadImg(src){return new Promise(res=>{const i=new Image();i.onload=()=>res(i);i.onerror=()=>res(null);i.src=src;});}
document.getElementById('saveReceipt').addEventListener('click',async ev=>{
  const btn=ev.currentTarget; btn.disabled=true; btn.textContent='Preparing…';
  const cv=document.createElement('canvas'); cv.width=900; cv.height=1100;
  const x=cv.getContext('2d');
  x.fillStyle='#ffffff'; x.fillRect(0,0,900,1100);
  x.fillStyle='#0A4A7A'; x.fillRect(0,0,900,150);
  const logo=await loadImg(BK.logo);
  if(logo)x.drawImage(logo,40,30,90,90);
  x.fillStyle='#ffffff'; x.font='bold 44px Georgia,serif'; x.fillText("EAV's Homestay",150,75);
  x.font='22px Arial'; x.fillText('Catohugan, Mahinog Camiguin, 9101 Phil.',150,108);
  x.fillStyle='#1a2a3a'; x.font='bold 36px Georgia,serif'; x.fillText('Booking Receipt  #'+BK.id,40,215);
  x.font='24px Arial';
  const rows=[['Guest',BK.guest],['Phone','<?php echo esc($b['phone']); ?>'],['Room',BK.room],['Check-in',BK.ci+' (from '+BK.hours.split(' / ')[0]+')'],['Check-out',BK.co+' ('+BK.nights+' night(s))'],['Total',BK.total],['Reservation fee',BK.fee+' (non-refundable)'],['Paid now',BK.paid],['Balance on arrival',BK.bal],['Payment',BK.pay],['Status',BK.status]];
  let y=270; rows.forEach(r=>{x.fillStyle='#6b7a8d';x.fillText(r[0],40,y);x.fillStyle='#1a2a3a';x.fillText(String(r[1]).slice(0,52),230,y);y+=48;});
  const photo=await loadImg(BK.img);
  if(photo){const r=photo.width/photo.height;let w=820,h=820/r;if(h>380){h=380;w=380*r;}x.drawImage(photo,(900-w)/2,y+10,w,h);y+=h;}
  x.fillStyle='#6b7a8d'; x.font='20px Arial'; x.fillText('Present this receipt on arrival. Dates are confirmed.',40,1060);
  const a=document.createElement('a'); a.download='booking-'+BK.id+'.png'; a.href=cv.toDataURL('image/png'); a.click();
  btn.disabled=false; btn.textContent='Save';
});
</script>
<?php require 'includes/footer.php'; ?>
