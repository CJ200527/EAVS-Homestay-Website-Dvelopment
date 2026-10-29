<?php require 'config/db.php'; require 'config/helpers.php';
$today=date('Y-m-d');
$rooms = $conn->query("SELECT * FROM rooms WHERE status='active'")->fetch_all(MYSQLI_ASSOC);
$errors=[];
$pref=['room_id'=>$_GET['room_id']??1,'check_in'=>$_GET['check_in']??'','check_out'=>$_GET['check_out']??'',
       'guest_name'=>'','phone'=>'','num_guests'=>2,
       'payment_type'=>'Advance','payment_method'=>'Cash','agree'=>''];
$createdId=0;
if($_SERVER['REQUEST_METHOD']==='POST'){
  // Keep every submitted value so a validation failure never wipes the form.
  $pref = array_merge($pref, [
    'room_id'=>(int)($_POST['room_id']??1),
    'check_in'=>trim($_POST['check_in']??''),
    'check_out'=>trim($_POST['check_out']??''),
    'guest_name'=>trim($_POST['guest_name']??''),
    'phone'=>trim($_POST['phone']??''),
    'num_guests'=>(int)($_POST['num_guests']??2),
    'payment_type'=>($_POST['payment_type']??'Advance'),
    'payment_method'=>($_POST['payment_method']??'Cash'),
    'agree'=>!empty($_POST['agree_terms'])?'1':'',
  ]);
  $room_id=(int)$pref['room_id']; $name=$pref['guest_name']; $phone=$pref['phone'];
  $guests=$pref['num_guests']; $ci=$pref['check_in']; $co=$pref['check_out'];
  $ptype=$pref['payment_type']; $pmethod=$pref['payment_method'];

  if(strlen($name)<2)$errors[]='Enter full name.';
  if(strlen($phone)<7)$errors[]='Enter valid phone number.';
  if($ci===''||$co==='')$errors[]='Select your check-in and check-out dates.';
  elseif($ci<$today)$errors[]='Check-in date cannot be in the past.';
  elseif($ci>=$co)$errors[]='Check-out must be after check-in.';
  if($guests<1||$guests>12)$errors[]='Number of guests must be between 1 and 12.';
  if(!in_array($ptype,['Advance','Full']))$errors[]='Invalid payment option.';
  if(!in_array($pmethod,['Cash','GCash','Maya']))$errors[]='Invalid payment method.';
  // Terms gate is enforced SERVER-SIDE; the checkbox attribute alone is bypassable.
  if(empty($pref['agree']))$errors[]='You must agree to the Terms & Conditions to continue.';

  $st=$conn->prepare("SELECT * FROM rooms WHERE id=?");$st->bind_param('i',$room_id);$st->execute();
  $room=$st->get_result()->fetch_assoc(); if(!$room||($room['status']??'active')!=='active')$errors[]='Room not available.';

  // Proof of payment is required for EVERY method, cash included: a face-to-face
  // handover is still evidence, and it settles disputes about what was paid.
  $proof=null;
  if(!$errors){
    if(empty($_FILES['proof']['name']))$errors[]='Upload a photo or screenshot of your payment.';
    else{
      $ext=strtolower(pathinfo($_FILES['proof']['name'],PATHINFO_EXTENSION));
      if(!in_array($ext,['jpg','jpeg','png','webp']))$errors[]='Proof must be jpg/png/webp.';
      elseif($_FILES['proof']['size']>2*1024*1024)$errors[]='Proof screenshot must be under 2MB.';
      else{
        $dir=__DIR__.'/uploads/proofs/'; if(!is_dir($dir))mkdir($dir,0755,true);
        $fname=time().'_'.preg_replace('/[^a-z0-9._-]/i','',basename($_FILES['proof']['name']));
        if(!move_uploaded_file($_FILES['proof']['tmp_name'],$dir.$fname))$errors[]='Failed to save the payment screenshot.';
        else $proof='uploads/proofs/'.$fname;
      }
    }
  }

  if(!$errors){
    $nights=nights_between($ci,$co); $total=$nights*(float)$room['price_per_night'];
    // Money model: proof was uploaded, so money changed hands for every method
    // (cash included). Advance pays the non-refundable reservation fee; Full pays
    // the total. There is no longer a Cash = "collect nothing up front" case.
    $fee=RESERVATION_FEE;   // bind_param needs a variable, not a constant
    $amountPaid = ($ptype==='Advance') ? min($fee, $total) : $total;
    $payStatus = ($amountPaid<=0) ? 'Unpaid' : (($amountPaid+0.005 >= $total) ? 'Fully Paid' : 'Part Paid');

    // Serialised check-then-insert: prevents two guests claiming the same night.
    $conn->begin_transaction();
    $lock=$conn->prepare('SELECT id FROM rooms WHERE id=? FOR UPDATE');
    $lock->bind_param('i',$room_id); $lock->execute(); $lock->get_result()->fetch_assoc();

    if(!is_room_available($conn,$room_id,$ci,$co)){
      $conn->rollback();
      $errors[]='Sorry, those dates were just taken. Please pick different dates.';
    } else {
      // i s s i s s d d d s s s s
      $ins=$conn->prepare("INSERT INTO bookings (room_id,guest_name,phone,num_guests,check_in,check_out,total_amount,reservation_fee,amount_paid,payment_type,payment_method,payment_status,proof_image,booking_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'Confirmed')");
      $ins->bind_param('ississdddssss',$room_id,$name,$phone,$guests,$ci,$co,$total,$fee,$amountPaid,$ptype,$pmethod,$payStatus,$proof);
      if(!$ins->execute()){ $conn->rollback(); $errors[]='Could not save your booking. Please try again.'; }
      else { $conn->commit(); header("Location: booking-success.php?id=".$ins->insert_id); exit; }
    }
  }
}
$selId=(int)($pref['room_id']??1);
require 'includes/header.php';
$roomMap=[]; foreach($rooms as $r){$roomMap[(int)$r['id']]=$r;}
$sel=$roomMap[$selId]??($rooms[0]??null);
$selPrice = $sel ? (float)$sel['price_per_night'] : 0;
?>
<!-- TELLY BANNER -->
<section class="telly-hero" style="min-height:44vh">
  <img class="bg" src="assets/images/outside/526532030_122133411140410695_3817852151501581650_n.jpg" alt="Booking banner">
  <div class="overlay"></div>
  <div class="container content text-center">
    <h1 class="banner-title serif">Booking</h1>
    <p><a href="index.php" class="text-light">Home</a> &nbsp;/&nbsp; Booking — No Account Needed</p>
  </div>
</section>

<div class="container section">
<div id="toastWrap" class="toast-wrap" aria-live="polite" aria-atomic="true"></div>
<?php if($errors): ?><script>window.__EAV_TOASTS=<?php echo json_encode(array_map(fn($e)=>['m'=>$e,'t'=>'danger'], $errors), JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;</script><?php endif; ?>
<div id="availMsg"></div>
<form method="post" enctype="multipart/form-data" id="bookForm">
<input type="hidden" name="agree_terms" value="<?php echo $pref['agree']==='1'?'1':''; ?>">
<div class="row g-4">
  <!-- LEFT: input textboxes -->
  <div class="col-lg-7"><div class="room-card p-4">
    <h3 class="serif text-center">Your Details</h3>
    <div class="row g-3">
      <div class="col-md-12"><label class="form-label">Room</label><select id="room_id" name="room_id" class="form-select">
        <?php foreach($rooms as $r): ?><option value="<?php echo $r['id']; ?>" <?php echo ($pref['room_id']==$r['id'])?'selected':''; ?>><?php echo esc($r['room_name']); ?> — <?php echo peso($r['price_per_night']); ?>/night</option><?php endforeach; ?>
      </select></div>
      <div class="col-md-6"><label class="form-label">Check-in</label><input id="check_in" type="date" name="check_in" class="form-control" required value="<?php echo esc($pref['check_in']); ?>" min="<?php echo $today; ?>"></div>
      <div class="col-md-6"><label class="form-label">Check-out</label><input id="check_out" type="date" name="check_out" class="form-control" required value="<?php echo esc($pref['check_out']); ?>" min="<?php echo $today; ?>"></div>
      <div class="col-md-12"><label class="form-label">Full Name</label><input name="guest_name" class="form-control" required value="<?php echo esc($pref['guest_name']); ?>"></div>
      <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" required placeholder="+63 ..." value="<?php echo esc($pref['phone']); ?>"></div>
      <div class="col-md-6"><label class="form-label">Guests</label><input type="number" name="num_guests" min="1" max="12" class="form-control" required value="<?php echo (int)$pref['num_guests']; ?>"></div>
      <div class="col-md-6"><label class="form-label">Payment Option</label><select name="payment_type" id="payment_type" class="form-select">
        <option value="Advance" <?php echo $pref['payment_type']==='Advance'?'selected':''; ?>>Advance — reservation fee only</option>
        <option value="Full" <?php echo $pref['payment_type']==='Full'?'selected':''; ?>>Full — pay everything now</option>
      </select></div>
      <div class="col-md-6"><label class="form-label">Pay Via</label><select id="payment_method" name="payment_method" class="form-select">
        <option value="Cash" <?php echo $pref['payment_method']==='Cash'?'selected':''; ?>>Cash</option>
        <option value="GCash" <?php echo $pref['payment_method']==='GCash'?'selected':''; ?>>GCash</option>
        <option value="Maya" <?php echo $pref['payment_method']==='Maya'?'selected':''; ?>>Maya</option>
      </select></div>
      <div class="col-12"><label class="form-label">Payment Proof</label><input type="file" name="proof" accept="image/*" class="form-control" required>
        <small class="text-muted">Required for every payment method, cash included. jpg/png/webp, under 2MB.</small></div>
      <div class="col-12"><div id="totalHint" class="text-primary small"></div></div>
      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="agreeChk" value="1" <?php echo $pref['agree']==='1'?'checked':''; ?>>
          <label class="form-check-label" for="agreeChk">
            I have read and agree to the
            <button type="button" class="btn btn-link p-0 align-baseline text-decoration-underline" data-bs-toggle="modal" data-bs-target="#termsModal" id="openTerms">Terms &amp; Conditions</button>.
          </label>
        </div>
      </div>
      <div class="col-12 text-center">
        <button type="submit" class="btn btn-warning btn-pill btn-lg btn-arrow" id="bookBtn"><span>Book Now</span></button>
        <small class="text-muted d-block mt-2">Dates are reserved instantly. ₱<?php echo number_format(RESERVATION_FEE,0); ?> reservation fee is non-refundable.</small>
      </div>
    </div>
  </div></div>
  <!-- RIGHT: room picture card -->
  <?php if($sel): ?>
  <div class="col-lg-5"><div class="room-card" id="bookRoomCard">
    <img id="bookRoomImg" src="<?php echo esc($sel['image_urls']); ?>" alt="Selected room">
    <div class="p-3">
      <h3 class="serif" id="bookRoomName"><?php echo esc($sel['room_name']); ?></h3>
      <p class="price" id="bookRoomPrice"><?php echo peso($sel['price_per_night']); ?> / night</p>
      <div id="bookRoomDesc" class="text-muted small"><?php echo esc($sel['description']); ?></div>
    </div>
  </div></div>
  <?php endif; ?>
</div>
<!-- BELOW: room details (same spec-table as Rooms/Details) -->
<div class="room-card p-4 mt-4" id="bookRoomDetails">
  <h3 class="serif">Room Details</h3>
  <table class="spec-table" style="max-width:560px" id="bookSpecTable"></table>
  <div class="row g-1 gallery mt-2" id="bookThumbs"></div>
  <div class="alert alert-warning mt-2" id="bookScooter" style="display:none"><b>Base package includes FREE 1-day scooter rental!</b></div>
</div>
</form></div>

<!-- Terms & Conditions modal: opens over the form so nothing typed is lost.
     Placed AFTER </form> to keep it out of the booking form's submit scope. -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title serif" id="termsModalLabel">Terms &amp; Conditions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php require 'includes/terms-content.php'; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-ocean" id="agreeTermsBtn">I Agree &amp; Continue</button>
      </div>
    </div>
  </div>
</div>
<script>
// Offline room data (local, no fetch needed for card swap; availability still uses local PHP endpoint)
const ROOMS = <?php echo json_encode(array_map(fn($r)=>['id'=>(int)$r['id'],'name'=>$r['room_name'],'price'=>(float)$r['price_per_night'],'price_fmt'=>peso($r['price_per_night']),'desc'=>$r['description'],'img'=>$r['image_urls'],'bed'=>((int)$r['id']===1?'Wooden bunk beds':'Queen + bunks'),'cap'=>(int)$r['capacity'],'scooter'=>((int)$r['id']===1)], $rooms)); ?>;
const GALS = {1: <?php echo json_encode(array_values(glob('assets/images/room1/*.jpg'))); ?>, 2: <?php echo json_encode(array_values(glob('assets/images/room2/*.jpg'))); ?>};
const RESFEE = <?php echo (float)RESERVATION_FEE; ?>;
const money = n => '₱' + Number(n).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
function selRoom(){ const v=document.getElementById('room_id').value; return ROOMS.find(x=>x.id==v)||ROOMS[0]; }
function paintRoom(){
  const r=selRoom(); if(!r) return;
  document.getElementById('bookRoomImg').src=r.img;
  document.getElementById('bookRoomName').textContent=r.name;
  document.getElementById('bookRoomPrice').textContent=r.price_fmt+' / night';
  document.getElementById('bookRoomDesc').textContent=r.desc;
  document.getElementById('bookSpecTable').innerHTML=`<tr><td><b>Price:</b></td><td class="price"><b>${r.price_fmt}</b> /night</td></tr><tr><td><b>Capacity:</b></td><td>Up to ${r.cap} guests</td></tr><tr><td><b>Bed:</b></td><td>${r.bed}</td></tr><tr><td><b>Check-in:</b></td><td>From ${<?php echo json_encode(CHECK_IN_TIME); ?>}</td></tr><tr><td><b>Check-out:</b></td><td>By ${<?php echo json_encode(CHECK_OUT_TIME); ?>}</td></tr><tr><td><b>Services:</b></td><td>AC, TV, dispenser, dining, free Wi-Fi${r.scooter?' + FREE scooter':''}</td></tr>`;
  document.getElementById('bookThumbs').innerHTML=(GALS[r.id]||[]).slice(0,3).map(s=>`<div class="col-4"><img src="${s}" alt="thumb"></div>`).join('');
  document.getElementById('bookScooter').style.display=r.scooter?'block':'none';
  paintHint();
}
function paintHint(){
  const r=selRoom(); if(!r) return;
  const ci=document.getElementById('check_in').value, co=document.getElementById('check_out').value;
  const pt=document.getElementById('payment_type').value;
  const el=document.getElementById('totalHint');
  if(!ci||!co||ci>=co){ el.textContent='Choose your dates to see the price breakdown.'; return; }
  const nights=Math.round((new Date(co)-new Date(ci))/86400000);
  const total=nights*r.price, fee=Math.min(RESFEE,total);
  const bal=Math.max(0,total-fee);
  el.innerHTML = `<b>${nights} night${nights===1?'':'s'} × ${r.price_fmt}</b> = <b>${money(total)}</b><br>`
    + `Reservation fee (non-refundable): ${money(fee)}<br>`
    + (pt==='Advance'
        ? `Pay now: <b>${money(fee)}</b> · Balance due on arrival: <b>${money(bal)}</b>`
        : `Pay now (full): <b>${money(total)}</b>`);
}
document.getElementById('room_id').addEventListener('change',paintRoom);
document.getElementById('payment_type').addEventListener('change',paintHint);
['check_in','check_out'].forEach(id=>document.getElementById(id).addEventListener('change',paintHint));
// Live availability hint (uses the local PHP endpoint)
function checkAvail(){
  const r=selRoom(); const ci=document.getElementById('check_in').value, co=document.getElementById('check_out').value;
  const box=document.getElementById('availMsg');
  if(!r||!ci||!co||ci>=co){ box.innerHTML=''; return; }
  fetch('check-availability.php?room_id='+r.id+'&check_in='+ci+'&check_out='+co)
    .then(x=>x.json()).then(d=>{
      box.className='alert alert-'+(d.available?'success':'warning');
      box.innerHTML = d.available
        ? `<strong>Available.</strong> ${d.nights} night${d.nights===1?'':'s'} · ${d.total_formatted} total`
        : `<strong>Not available.</strong> ${escJS(d.message)}`;
    }).catch(()=>{ box.className='alert alert-secondary'; box.innerHTML='Could not check availability right now — we will still validate your dates on submit.'; });
}
function escJS(s){ const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
['check_in','check_out'].forEach(id=>document.getElementById(id).addEventListener('change',checkAvail));
document.getElementById('room_id').addEventListener('change',checkAvail);
// ---- Notifications: centered pop-up toasts -------------------------------
// Errors linger longer (8s) than info/success (4s) so a guest can actually read
// and act on them before they vanish.
const TOAST_MS = { danger: 8000, info: 4000, success: 4000 };
function showToast(msg, type){
  const wrap = document.getElementById('toastWrap');
  if(!wrap) return;
  const kind = type || 'info';
  const t = document.createElement('div');
  t.className = 'eav-toast eav-toast-' + kind;
  t.setAttribute('role', kind === 'danger' ? 'alert' : 'status');
  const body = document.createElement('span');
  body.textContent = msg;
  t.appendChild(body);
  const x = document.createElement('button');
  x.type = 'button'; x.className = 'x'; x.setAttribute('aria-label','Dismiss');
  x.textContent = '\u00D7';
  t.appendChild(x);
  wrap.appendChild(t);
  let gone = false;
  function dismiss(){
    if(gone) return; gone = true;
    t.classList.add('out');
    setTimeout(()=>{ if(t.parentNode) t.remove(); }, 350);
  }
  x.addEventListener('click', dismiss);
  setTimeout(dismiss, TOAST_MS[kind] || 4000);
}
(window.__EAV_TOASTS || []).forEach(function(t){ showToast(t.m, t.t); });
// Terms gate: friendly toast reminder, but the server re-checks anyway.
document.getElementById('bookForm').addEventListener('submit',function(e){
  const chk=document.getElementById('agreeChk');
  if(!chk.checked){
    e.preventDefault();
    chk.classList.add('is-invalid');
    showToast('Please agree to the Terms & Conditions before booking.','danger');
    chk.scrollIntoView({behavior:'smooth',block:'center'});
  }
});
// The checkbox has NO name attribute (it is not submitted by itself) -- the hidden
// agree_terms input is what actually reaches the server. Previously nothing kept
// the two in sync, so ticking the box by hand still failed validation.
function syncAgree(){
  const chk=document.getElementById('agreeChk');
  const hidden=document.querySelector('input[name="agree_terms"]');
  if(chk&&hidden) hidden.value = chk.checked ? '1' : '';
  if(chk) chk.classList.remove('is-invalid');
}
document.getElementById('agreeChk').addEventListener('change',syncAgree);
// "I Agree & Continue" in the terms modal ticks the box for the guest.
document.getElementById('agreeTermsBtn').addEventListener('click',function(){
  const chk=document.getElementById('agreeChk');
  chk.checked=true;
  syncAgree();
  bootstrap.Modal.getInstance(document.getElementById('termsModal')).hide();
  const btn=document.getElementById('bookBtn');
  if(btn) btn.scrollIntoView({behavior:'smooth',block:'center'});
});
paintRoom();
paintHint(); checkAvail();
</script>
<?php require 'includes/footer.php'; ?>