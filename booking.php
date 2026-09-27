<?php require 'config/db.php'; require 'config/helpers.php';
$rooms = $conn->query("SELECT * FROM rooms")->fetch_all(MYSQLI_ASSOC);
$errors=[]; $pref=['room_id'=>$_GET['room_id']??1,'check_in'=>$_GET['check_in']??'','check_out'=>$_GET['check_out']??''];
if($_SERVER['REQUEST_METHOD']==='POST'){
  $room_id=(int)$_POST['room_id']; $name=trim($_POST['guest_name']); $phone=trim($_POST['phone']);
  $email=trim($_POST['email']); $guests=(int)$_POST['num_guests']; $ci=$_POST['check_in']; $co=$_POST['check_out'];
  $ptype=$_POST['payment_type']; $pmethod=$_POST['payment_method'];
  if(strlen($name)<2)$errors[]='Enter full name.';
  if(strlen($phone)<7)$errors[]='Enter valid phone.';
  if(!filter_var($email,FILTER_VALIDATE_EMAIL))$errors[]='Enter valid email.';
  if($ci>=$co)$errors[]='Check-out must be after check-in.';
  if(!in_array($ptype,['Advance','Full']))$errors[]='Invalid payment type.';
  if(!in_array($pmethod,['Cash','GCash','Maya']))$errors[]='Invalid payment method.';
  $st=$conn->prepare("SELECT * FROM rooms WHERE id=?");$st->bind_param('i',$room_id);$st->execute();
  $room=$st->get_result()->fetch_assoc(); if(!$room)$errors[]='Room not found.';
  if(!$errors && !is_room_available($conn,$room_id,$ci,$co))$errors[]='Sorry, room is already booked for those dates.';
  $proof=null;
  if(!$errors && $pmethod!=='Cash'){
    if(empty($_FILES['proof']['name']))$errors[]='Upload GCash/Maya payment proof screenshot.';
    else{
      $ext=strtolower(pathinfo($_FILES['proof']['name'],PATHINFO_EXTENSION));
      if(!in_array($ext,['jpg','jpeg','png','webp']))$errors[]='Proof must be jpg/png/webp.';
      else{ $proof='uploads/proofs/'.time().'_'.preg_replace('/[^a-z0-9._-]/i','',$_FILES['proof']['name']);
        if(!move_uploaded_file($_FILES['proof']['tmp_name'],$proof))$errors[]='Failed to save proof.'; }
    }
  }
  if(!$errors){
    $nights=nights_between($ci,$co); $total=$nights*(float)$room['price_per_night'];
    $st=$conn->prepare("INSERT INTO bookings (room_id,guest_name,phone,email,num_guests,check_in,check_out,total_amount,payment_type,payment_method,proof_image) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $st->bind_param('isssissdsss',$room_id,$name,$phone,$email,$guests,$ci,$co,$total,$ptype,$pmethod,$proof);
    $st->execute(); $bid=$st->insert_id;
    header("Location: booking-success.php?id=$bid"); exit;
  }
  $pref=$_POST;
}
$selId=(int)($pref['room_id']??1);
require 'includes/header.php';
$roomMap=[]; foreach($rooms as $r){$roomMap[(int)$r['id']]=$r;}
$sel=$roomMap[$selId]??$rooms[0];
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
<div id="availMsg"></div>
<?php foreach($errors as $e): ?><div class="alert alert-danger"><?php echo esc($e); ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data">
<div class="row g-4">
  <!-- LEFT: input textboxes -->
  <div class="col-lg-7"><div class="room-card p-4">
    <h3 class="serif">Your Details</h3>
    <div class="row g-3">
      <div class="col-md-12"><label class="form-label">Room</label><select id="room_id" name="room_id" class="form-select">
        <?php foreach($rooms as $r): ?><option value="<?php echo $r['id']; ?>" <?php echo ($pref['room_id']==$r['id'])?'selected':''; ?>><?php echo esc($r['room_name']); ?> — <?php echo peso($r['price_per_night']); ?>/night</option><?php endforeach; ?>
      </select></div>
      <div class="col-md-6"><label class="form-label">Check-in</label><input id="check_in" type="date" name="check_in" class="form-control" required value="<?php echo esc($pref['check_in']); ?>" min="<?php echo date('Y-m-d'); ?>"></div>
      <div class="col-md-6"><label class="form-label">Check-out</label><input id="check_out" type="date" name="check_out" class="form-control" required value="<?php echo esc($pref['check_out']); ?>"></div>
      <div class="col-md-12"><label class="form-label">Full Name</label><input name="guest_name" class="form-control" required value="<?php echo esc($pref['guest_name']??''); ?>"></div>
      <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" required placeholder="+63 ..." value="<?php echo esc($pref['phone']??''); ?>"></div>
      <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required value="<?php echo esc($pref['email']??''); ?>"></div>
      <div class="col-md-4"><label class="form-label">Guests</label><input type="number" name="num_guests" min="1" max="10" class="form-control" required value="<?php echo esc($pref['num_guests']??2); ?>"></div>
      <div class="col-md-4"><label class="form-label">Payment Option</label><select name="payment_type" class="form-select"><option>Advance</option><option>Full</option></select><small id="totalHint" class="text-primary"></small></div>
      <div class="col-md-4"><label class="form-label">Pay Via</label><select id="payment_method" name="payment_method" class="form-select"><option>Cash</option><option>GCash</option><option>Maya</option></select></div>
      <div class="col-12" id="proofWrap"><label class="form-label">Proof Screenshot</label><input type="file" name="proof" accept="image/*" class="form-control"></div>
      <div class="col-12"><button class="btn btn-warning btn-pill btn-lg btn-arrow"><span>Submit Booking Request</span></button>
      <small class="text-muted ms-2">Cash on arrival, or GCash/Maya + proof. Admin verifies.</small></div>
    </div>
  </div></div>
  <!-- RIGHT: room picture card -->
  <div class="col-lg-5"><div class="room-card" id="bookRoomCard">
    <img id="bookRoomImg" src="<?php echo esc($sel['image_urls']); ?>" alt="Selected room">
    <div class="p-3">
      <h3 class="serif" id="bookRoomName"><?php echo esc($sel['room_name']); ?></h3>
      <p class="price" id="bookRoomPrice"><?php echo peso($sel['price_per_night']); ?> / night</p>
      <div id="bookRoomDesc" class="text-muted small"><?php echo esc($sel['description']); ?></div>
    </div>
  </div></div>
</div>
<!-- BELOW: room details (same spec-table as Rooms/Details) -->
<div class="room-card p-4 mt-4" id="bookRoomDetails">
  <h3 class="serif">Room Details</h3>
  <table class="spec-table" style="max-width:560px" id="bookSpecTable"></table>
  <div class="row g-1 gallery mt-2" id="bookThumbs"></div>
  <div class="alert alert-warning mt-2" id="bookScooter" style="display:none">🛵 <b>Base package includes FREE 1-day scooter rental!</b></div>
</div>
</form></div>
<script>
// Offline room data (local, no fetch needed for card swap; availability still uses local PHP endpoint)
const ROOMS = <?php echo json_encode(array_map(fn($r)=>['id'=>(int)$r['id'],'name'=>$r['room_name'],'price'=>(float)$r['price_per_night'],'price_fmt'=>peso($r['price_per_night']),'desc'=>$r['description'],'img'=>$r['image_urls'],'bed'=>((int)$r['id']===1?'Wooden bunk beds':'Queen + bunks'),'cap'=>(int)$r['capacity'],'scooter'=>((int)$r['id']===1)], $rooms)); ?>;
const GALS = {1: <?php echo json_encode(array_values(glob('assets/images/room1/*.jpg'))); ?>, 2: <?php echo json_encode(array_values(glob('assets/images/room2/*.jpg'))); ?>};
function paintRoom(){
  const id=document.getElementById('room_id').value, r=ROOMS.find(x=>x.id==id)||ROOMS[0];
  document.getElementById('bookRoomImg').src=r.img;
  document.getElementById('bookRoomName').textContent=r.name;
  document.getElementById('bookRoomPrice').textContent=r.price_fmt+' / night';
  document.getElementById('bookRoomDesc').textContent=r.desc;
  document.getElementById('bookSpecTable').innerHTML=`<tr><td><b>Price:</b></td><td class="price"><b>${r.price_fmt}</b> /night</td></tr><tr><td><b>Capacity:</b></td><td>Up to ${r.cap} guests</td></tr><tr><td><b>Bed:</b></td><td>${r.bed}</td></tr><tr><td><b>Services:</b></td><td>AC, TV, dispenser, dining${r.scooter?' + FREE scooter':''}</td></tr>`;
  document.getElementById('bookThumbs').innerHTML=(GALS[id]||[]).slice(0,3).map(s=>`<div class="col-4"><img src="${s}" alt="thumb"></div>`).join('');
  document.getElementById('bookScooter').style.display=r.scooter?'block':'none';
}
document.getElementById('room_id').addEventListener('change',paintRoom); paintRoom();
</script>
<?php require 'includes/footer.php'; ?>
