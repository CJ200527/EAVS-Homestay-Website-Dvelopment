<?php require 'auth.php'; require '../config/db.php'; require '../config/helpers.php'; require 'layout.php';
// Actions (all confirm-gated in UI via askConfirm)
$msg='';
if(isset($_GET['action'],$_GET['id'])){
  $id=(int)$_GET['id']; $a=$_GET['action'];
  // v0.12: no approval step. Confirmed is created automatically and blocks dates.
  $allowed=['Declined','Completed','CheckedIn','CheckedOut'];
  $labels=['Declined'=>'Booking declined — dates released.','Completed'=>'Marked Completed.','CheckedIn'=>'Guest checked in.','CheckedOut'=>'Guest checked out.'];
  if(in_array($a,$allowed)){
    // CheckedIn re-validates dates so a stale edit cannot create an overlap.
    if($a==='CheckedIn'){
      $b=$conn->query("SELECT * FROM bookings WHERE id=$id")->fetch_assoc();
      if($b && !is_room_available($conn,$b['room_id'],$b['check_in'],$b['check_out'],$id)) $msg='Cannot check in: dates now overlap another confirmed booking.';
      else { $conn->query("UPDATE bookings SET booking_status='CheckedIn' WHERE id=$id"); flash($labels[$a]); header('Location: bookings.php'); exit; }
    } else { $conn->query("UPDATE bookings SET booking_status='".$conn->real_escape_string($a)."' WHERE id=$id"); flash($labels[$a] ?? 'Updated.'); header('Location: bookings.php'); exit; }
  }
}
// Mark Fully Paid: requires the admin to attach proof of the full payment.
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['mark_paid'])){
  $id=(int)$_POST['mark_paid'];
  $b=$conn->query("SELECT * FROM bookings WHERE id=$id")->fetch_assoc();
  if(!$b) $msg='Booking not found.';
  elseif(empty($_FILES['full_proof']['name'])) $msg='Attach a photo of the full payment before marking it paid.';
  else{
    $ext=strtolower(pathinfo($_FILES['full_proof']['name'],PATHINFO_EXTENSION));
    if(!in_array($ext,['jpg','jpeg','png','webp']))$msg='Proof must be jpg/png/webp.';
    elseif($_FILES['full_proof']['size']>2*1024*1024)$msg='Proof must be under 2MB.';
    else{
      $dir=__DIR__.'/../uploads/proofs/'; if(!is_dir($dir))mkdir($dir,0755,true);
      $fname=time().'_full_'.preg_replace('/[^a-z0-9._-]/i','',basename($_FILES['full_proof']['name']));
      if(!move_uploaded_file($_FILES['full_proof']['tmp_name'],$dir.$fname))$msg='Failed to save the proof photo.';
      else{
        $conn->query("UPDATE bookings SET amount_paid=total_amount, payment_status='Fully Paid', proof_image='".$conn->real_escape_string('uploads/proofs/'.$fname)."' WHERE id=$id");
        flash('Marked Fully Paid — proof attached.'); header('Location: bookings.php'); exit;
      }
    }
  }
}
// Walk-in booking (same instant-confirm rules, race-guarded like the guest form)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['new_booking'])){
  $room_id=(int)$_POST['room_id']; $name=trim($_POST['guest_name']); $phone=trim($_POST['phone']);
  $g=(int)$_POST['num_guests']; $ci=$_POST['check_in']; $co=$_POST['check_out'];
  $ptype=in_array($_POST['payment_type']??'Advance',['Advance','Full'])?$_POST['payment_type']:'Advance';
  $pm=in_array($_POST['payment_method']??'Cash',['Cash','GCash','Maya'])?$_POST['payment_method']:'Cash';
  $rm=$conn->query("SELECT * FROM rooms WHERE id=$room_id")->fetch_assoc();
  if(strlen($name)<2)$msg='Enter guest name.';
  elseif(strlen($phone)<7)$msg='Enter valid phone.';
  elseif($ci>=$co)$msg='Check-out must be after check-in.';
  elseif(!$rm||($rm['status']??'active')!=='active')$msg='Room is not active.';
  else {
    $total=nights_between($ci,$co)*(float)$rm['price_per_night'];
    $fee=RESERVATION_FEE;   // bind_param needs a variable, not a constant
    $amountPaid = ($ptype==='Advance') ? min($fee,$total) : $total;
    $payStatus = ($amountPaid<=0) ? 'Unpaid' : (($amountPaid+0.005 >= $total) ? 'Fully Paid' : 'Part Paid');
    // Proof of payment is required for every method, cash included -- the guest
    // may already be at the homestay, so the owner records it on their behalf.
    $proof=null;
    if(empty($_FILES['walkin_proof']['name']))$msg='Attach a photo of the payment as evidence.';
    else{
      $ext=strtolower(pathinfo($_FILES['walkin_proof']['name'],PATHINFO_EXTENSION));
      if(!in_array($ext,['jpg','jpeg','png','webp']))$msg='Proof must be jpg/png/webp.';
      elseif($_FILES['walkin_proof']['size']>2*1024*1024)$msg='Proof must be under 2MB.';
      else{
        $dir=__DIR__.'/../uploads/proofs/'; if(!is_dir($dir))mkdir($dir,0755,true);
        $fname=time().'_walkin_'.preg_replace('/[^a-z0-9._-]/i','',basename($_FILES['walkin_proof']['name']));
        if(!move_uploaded_file($_FILES['walkin_proof']['tmp_name'],$dir.$fname))$msg='Failed to save the proof photo.';
        else $proof='uploads/proofs/'.$fname;
      }
    }
    if($msg){
      // proof/validation already failed - nothing to insert
    } else {
      // Serialised check-then-insert, same as the guest form (see v0.12 notes).
      $conn->begin_transaction();
      $lock=$conn->prepare('SELECT id FROM rooms WHERE id=? FOR UPDATE'); $lock->bind_param('i',$room_id);
      $lock->execute(); $lock->get_result()->fetch_assoc();
      if(!is_room_available($conn,$room_id,$ci,$co)){
        $conn->rollback(); $msg='Those dates are already booked for that room.';
      } else {
        // i s s i s s d d d s s s s
        $st=$conn->prepare("INSERT INTO bookings (room_id,guest_name,phone,num_guests,check_in,check_out,total_amount,reservation_fee,amount_paid,payment_type,payment_method,payment_status,proof_image,booking_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'Confirmed')");
        $st->bind_param('ississdddssss',$room_id,$name,$phone,$g,$ci,$co,$total,$fee,$amountPaid,$ptype,$pm,$payStatus,$proof);
        $st->execute(); $conn->commit();
        flash('Walk-in booking created (Confirmed).'); header('Location: bookings.php'); exit;
      }
    }
  }
}
// Edit: dates + guests + pay (overlap re-checked)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_id'])){
  $id=(int)$_POST['edit_id']; $ci=$_POST['check_in']; $co=$_POST['check_out']; $g=(int)$_POST['num_guests'];
  $ps=$_POST['payment_status']; $pt=$_POST['payment_type'];
  $b=$conn->query("SELECT * FROM bookings WHERE id=$id")->fetch_assoc();
  if(!$b) $msg='Booking not found.';
  elseif($ci>=$co) $msg='Check-out must be after check-in.';
  elseif($b['booking_status']!=='Declined' && !is_room_available($conn,$b['room_id'],$ci,$co,$id)) $msg='New dates overlap another confirmed booking.';
  else {
    $nights=nights_between($ci,$co);
    $room=$conn->query("SELECT price_per_night FROM rooms WHERE id=".(int)$b['room_id'])->fetch_assoc();
    $total=$nights*(float)$room['price_per_night'];
    // amount_paid stays authoritative; only Fully Paid clears it to the total.
    $amountPaid = ($ps==='Fully Paid') ? $total : (float)$b['amount_paid'];
    if($ps==='Unpaid') $amountPaid=0.0;
    $st=$conn->prepare("UPDATE bookings SET check_in=?,check_out=?,num_guests=?,payment_status=?,payment_type=?,total_amount=?,amount_paid=? WHERE id=?");
    $st->bind_param('ssissddi',$ci,$co,$g,$ps,$pt,$total,$amountPaid,$id); $st->execute();
    flash('Booking updated.'); header('Location: bookings.php'); exit;
  }
}
// Search + filter (prepared, no warnings)
$q=trim($_GET['q']??''); $f=$_GET['filter']??'all'; $rm=(int)($_GET['room']??0);
$allowed_f=['all','Confirmed','Declined','Completed','CheckedIn','CheckedOut'];
if(!in_array($f,$allowed_f)) $f='all';
$where=["1"]; $types=''; $params=[];
if($f!=='all'){$where[]="b.booking_status=?";$types.='s';$params[]=$f;}
if($rm>0){$where[]="b.room_id=?";$types.='i';$params[]=$rm;}
if($q!==''){$where[]="(b.guest_name LIKE ? OR b.phone LIKE ?)";$types.='ss';$like="%$q%";$params[]=$like;$params[]=$like;}
$sql="SELECT b.*,r.room_name FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE ".implode(' AND ',$where)." ORDER BY b.check_in DESC";
$st=$conn->prepare($sql); if($types){$st->bind_param($types,...$params);} $st->execute();
$rows=$st->get_result()->fetch_all(MYSQLI_ASSOC);
$rooms=$conn->query("SELECT id,room_name FROM rooms ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$activeRooms=$conn->query("SELECT id,room_name FROM rooms WHERE status='active' ORDER BY id")->fetch_all(MYSQLI_ASSOC);
admin_shell_open('Booking','book',false,true,'ocean');
admin_strip('Booking','Manage reservations', '');
// Booking KPIs (global pipeline — ignore search/filter, they describe all reservations)
$kToday=date('Y-m-d');
// "Needs review" is gone with the approval step -> Arriving Today is the useful signal now.
$kArriving=(int)$conn->query("SELECT COUNT(*) c FROM bookings WHERE booking_status='Confirmed' AND check_in='$kToday'")->fetch_assoc()['c'];
$kConfirmed=(int)$conn->query("SELECT COUNT(*) c FROM bookings WHERE booking_status='Confirmed'")->fetch_assoc()['c'];
$kIn=(int)$conn->query("SELECT COUNT(*) c FROM bookings WHERE booking_status IN ('Confirmed','CheckedIn') AND check_in='$kToday'")->fetch_assoc()['c'];
$kOut=(int)$conn->query("SELECT COUNT(*) c FROM bookings WHERE booking_status IN ('Confirmed','CheckedIn') AND check_out='$kToday'")->fetch_assoc()['c'];
$kMove=$kIn+$kOut;
// Outstanding = balance still owed (total - amount_paid) on anything not yet Fully Paid.
$kUnpaid=$conn->query("SELECT COUNT(*) c, COALESCE(SUM(total_amount-amount_paid),0) s FROM bookings WHERE payment_status<>'Fully Paid' AND booking_status<>'Declined'")->fetch_assoc();
$kUnpaidN=(int)$kUnpaid['c']; $kUnpaidS=(float)$kUnpaid['s'];
$kPaidN=(int)$conn->query("SELECT COUNT(*) c FROM bookings WHERE payment_status='Fully Paid'")->fetch_assoc()['c'];
$kTot=(int)$conn->query("SELECT COUNT(*) c FROM bookings")->fetch_assoc()['c'];
$kBySt=$conn->query("SELECT booking_status s,COUNT(*) c FROM bookings GROUP BY booking_status")->fetch_all(MYSQLI_ASSOC);
$kStParts=[]; foreach($kBySt as $r)$kStParts[]=(int)$r['c'].' '.$r['s'];
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3"><div class="kpi-dash glass has-link">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#k-inbox"/></svg></span><span class="delta delta-live">● today</span></div>
    <div class="lbl">Arriving Today</div><div class="big"><?php echo $kArriving; ?></div>
    <div class="kpi-sub"><span class="sub"><?php echo $kConfirmed; ?> confirmed &amp; upcoming</span><span class="kpi-more">View →</span></div>
    <a class="stretched-link" href="bookings.php?filter=Confirmed" aria-label="View confirmed bookings"></a>
  </div></div>
  <div class="col-6 col-xl-3"><div class="kpi-dash glass">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#i-bell"/></svg></span><span class="delta delta-live">● today</span></div>
    <div class="lbl">Today's Movements</div><div class="big"><?php echo $kMove; ?></div>
    <div class="kpi-sub"><span class="sub"><?php echo $kIn; ?> arrivals · <?php echo $kOut; ?> departures</span></div>
  </div></div>
  <div class="col-6 col-xl-3"><div class="kpi-dash glass">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#k-peso"/></svg></span><span class="delta delta-abs">₱<?php echo number_format($kUnpaidS); ?> outstanding</span></div>
    <div class="lbl">Awaiting Payment</div><div class="big"><?php echo $kUnpaidN; ?></div>
    <div class="kpi-sub"><span class="sub">balance due · <?php echo $kPaidN; ?> fully paid</span></div>
  </div></div>
  <div class="col-6 col-xl-3"><div class="kpi-dash glass has-link">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#i-book"/></svg></span><span class="delta delta-abs">all time</span></div>
    <div class="lbl">Total Bookings</div><div class="big"><?php echo $kTot; ?></div>
    <div class="kpi-sub"><span class="sub"><?php echo esc(implode(' · ',$kStParts)); ?></span><span class="kpi-more">View all →</span></div>
    <a class="stretched-link" href="bookings.php" aria-label="View all bookings"></a>
  </div></div>
</div>
<?php if($msg): ?><div class="alert alert-danger"><?php echo esc($msg); ?></div><?php endif; ?>
<div class="ad-card glass tool-card"><div class="toolbelt">
  <form method="get" data-toast-form class="tool-left">
    <input name="q" class="form-control" placeholder="Search guest / phone" value="<?php echo esc($q); ?>">
    <input type="hidden" name="filter" value="<?php echo esc($f); ?>"><input type="hidden" name="room" value="<?php echo $rm; ?>">
    <button class="icon-btn" title="Search"><svg class="nav-ic"><use href="#i-search"/></svg></button>
    <a href="bookings.php" title="Refresh" class="icon-btn"><svg class="nav-ic"><use href="#i-refresh"/></svg></a>
  </form>
  <div class="tool-right">
  <div class="filter-wrap">
    <button type="button" class="icon-btn filter-btn" aria-expanded="false" title="Filter"><svg class="nav-ic"><use href="#i-filter"/></svg></button>
    <div class="filter-panel"><form method="get" class="row g-2">
      <input type="hidden" name="q" value="<?php echo esc($q); ?>">
      <div class="col-12"><label class="form-label">Status</label><select name="filter" class="form-select">
        <?php foreach(['all','Confirmed','CheckedIn','CheckedOut','Completed','Declined'] as $o): ?><option value="<?php echo $o; ?>" <?php echo $f===$o?'selected':''; ?>><?php echo $o; ?></option><?php endforeach; ?>
      </select></div>
      <div class="col-12"><label class="form-label">Room</label><select name="room" class="form-select"><option value="0">All rooms</option>
        <?php foreach($rooms as $r): ?><option value="<?php echo $r['id']; ?>" <?php echo $rm==$r['id']?'selected':''; ?>><?php echo esc($r['room_name']); ?></option><?php endforeach; ?>
      </select></div>
      <div class="col-6"><button class="btn btn-ocean w-100">Apply</button></div>
      <div class="col-6"><a href="bookings.php" class="btn btn-secondary w-100">Reset</a></div>
    </form></div>
  </div>
  <button class="btn btn-ocean" data-bs-toggle="modal" data-bs-target="#newBook"><svg class="nav-ic"><use href="#i-plus"/></svg> New booking</button>
  </div>
</div></div>
<!-- Walk-in booking modal (same validation + overlap as guest form) -->
<div class="modal fade" id="newBook" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title serif">New walk-in booking</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="post" enctype="multipart/form-data"><div class="modal-body row g-2">
<input type="hidden" name="new_booking" value="1">
<div class="col-md-6"><label class="form-label">Room</label><select name="room_id" class="form-select"><?php foreach($activeRooms as $r): ?><option value="<?php echo $r['id']; ?>"><?php echo esc($r['room_name']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Check-in</label><input type="date" name="check_in" class="form-control" required value="<?php echo date('Y-m-d'); ?>"></div>
<div class="col-md-3"><label class="form-label">Check-out</label><input type="date" name="check_out" class="form-control" required value="<?php echo date('Y-m-d',strtotime('+1 day')); ?>"></div>
<div class="col-md-4"><label class="form-label">Guest name</label><input name="guest_name" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Guests</label><input type="number" name="num_guests" class="form-control" min="1" max="12" value="2" required></div>
<div class="col-md-4"><label class="form-label">Payment</label><select name="payment_type" class="form-select"><option>Advance</option><option>Full</option></select></div>
<div class="col-md-4"><label class="form-label">Method</label><select name="payment_method" class="form-select"><option>Cash</option><option>GCash</option><option>Maya</option></select></div>
<div class="col-md-12"><label class="form-label">Payment proof <span class="text-danger">*</span></label><input type="file" name="walkin_proof" accept="image/*" class="form-control" required>
  <small class="text-muted">Required for every method, cash included — use this when the guest is already at the homestay. jpg/png/webp, under 2MB.</small></div>
</div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-ocean">Create (Confirmed)</button></div></form>
</div></div></div>
<div class="ad-card glass book-table"><div class="table-responsive"><table class="table table-sm align-middle book-table">
<thead><tr><th>#</th><th>Guest</th><th>Room / Dates</th><th>Total / Pay</th><th>Proof</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php if(!$rows): ?><tr><td colspan="7" class="text-muted">No bookings match.</td></tr><?php endif; ?>
<?php foreach($rows as $b): $gi=strtoupper(substr($b['guest_name'],0,1)); $nn=nights_between($b['check_in'],$b['check_out']); ?>
<tr>
<td class="text-muted">#<?php echo $b['id']; ?><br><small><?php echo esc(substr($b['created_at'],0,10)); ?></small></td>
<td><span class="guest-cell"><span class="guest-av"><?php echo esc($gi); ?></span><span><b><?php echo esc($b['guest_name']); ?></b><small><?php echo esc($b['phone']); ?><br><?php echo (int)$b['num_guests']; ?> guests</small></span></span></td>
<td><?php echo esc($b['room_name']); ?><br><small><?php echo esc($b['check_in']); ?> → <?php echo esc($b['check_out']); ?></small><br><span class="nights-chip"><?php echo $nn; ?> night<?php echo $nn===1?'':'s'; ?></span></td>
<td><b><?php echo peso($b['total_amount']); ?></b><br><small>paid <?php echo peso($b['amount_paid']); ?> of <?php echo peso($b['total_amount']); ?></small><br><small><?php echo esc($b['payment_type']); ?> via <?php echo esc($b['payment_method']); ?></small><br><?php echo status_badge($b['payment_status']); ?></td>
<td><?php if($b['proof_image']): ?><a href="../<?php echo esc($b['proof_image']); ?>" target="_blank" class="proof-thumb"><img src="../<?php echo esc($b['proof_image']); ?>" alt="proof"></a><?php else: ?><small class="text-muted">Cash / none</small><?php endif; ?></td>
<td><?php echo status_badge($b['booking_status']); ?></td>
<td>
<div class="act-row nowrap">
<button class="btn-act a-view" title="View details" data-bs-toggle="modal" data-bs-target="#v<?php echo $b['id']; ?>"><svg class="act-icon"><use href="#a-view"/></svg></button>
<button class="btn-act a-edit" title="Edit dates / guests / pay" data-bs-toggle="modal" data-bs-target="#e<?php echo $b['id']; ?>"><svg class="act-icon"><use href="#a-edit"/></svg></button>
<?php if($b['payment_status']!=='Fully Paid'): ?>
<button class="btn-act a-ok" title="Mark fully paid (attach proof)" data-bs-toggle="modal" data-bs-target="#p<?php echo $b['id']; ?>"><svg class="act-icon"><use href="#a-check"/></svg></button>
<?php endif; ?>
<div class="dropdown">
<button class="btn-act a-dark" title="More actions" data-bs-toggle="dropdown" aria-expanded="false">⋯</button>
<ul class="dropdown-menu dropdown-menu-end">
<li><a class="dropdown-item" onclick="return askConfirm('Check-in booking #<?php echo $b['id']; ?>?','?action=CheckedIn&id=<?php echo $b['id']; ?>')">Check-in</a></li>
<li><a class="dropdown-item" onclick="return askConfirm('Check-out booking #<?php echo $b['id']; ?>?','?action=CheckedOut&id=<?php echo $b['id']; ?>')">Check-out</a></li>
<li><a class="dropdown-item" onclick="return askConfirm('Mark booking #<?php echo $b['id']; ?> Completed?','?action=Completed&id=<?php echo $b['id']; ?>')">Complete</a></li>
<li><a class="dropdown-item text-danger" onclick="return askConfirm('Decline booking #<?php echo $b['id']; ?>? This releases the dates so the room can be rebooked.','?action=Declined&id=<?php echo $b['id']; ?>')">Decline</a></li>
</ul>
</div>
</div>
<!-- Mark fully paid: proof upload is mandatory -->
<div class="modal fade" id="p<?php echo $b['id']; ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title serif">Mark #<?php echo $b['id']; ?> fully paid</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="post" enctype="multipart/form-data"><div class="modal-body">
<input type="hidden" name="mark_paid" value="<?php echo $b['id']; ?>">
<p class="small text-muted">Balance to collect: <b><?php echo peso($b['total_amount']-$b['amount_paid']); ?></b> (total <?php echo peso($b['total_amount']); ?> · already paid <?php echo peso($b['amount_paid']); ?>)</p>
<label class="form-label">Photo of full payment <span class="text-danger">*</span></label>
<input type="file" name="full_proof" accept="image/*" class="form-control" required>
<small class="text-muted">jpg/png/webp, under 2MB. This replaces the current proof.</small>
</div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-ocean">Confirm fully paid</button></div></form>
</div></div></div>
<!-- View modal -->
<div class="modal fade" id="v<?php echo $b['id']; ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title serif">Booking #<?php echo $b['id']; ?></h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p><b><?php echo esc($b['guest_name']); ?></b> (<?php echo (int)$b['num_guests']; ?>) — <?php echo esc($b['phone']); ?></p>
<p><?php echo esc($b['room_name']); ?>: <?php echo esc($b['check_in']); ?> → <?php echo esc($b['check_out']); ?> (<?php echo nights_between($b['check_in'],$b['check_out']); ?> nights)</p>
<p>Total <?php echo peso($b['total_amount']); ?> · reservation fee (non-refundable) <?php echo peso($b['reservation_fee']); ?> · paid <?php echo peso($b['amount_paid']); ?> · balance <?php echo peso($b['total_amount']-$b['amount_paid']); ?></p>
<p>Refundable if cancelled now: <b><?php echo peso(refundable_on_cancel($b['total_amount'],$b['amount_paid'])); ?></b> (fee is never refunded)</p>
<p><?php echo esc($b['payment_type']); ?> via <?php echo esc($b['payment_method']); ?> • <?php echo status_badge($b['payment_status']); ?> • <?php echo status_badge($b['booking_status']); ?></p>
<?php if($b['proof_image']): ?><img src="../<?php echo esc($b['proof_image']); ?>" class="w-100" style="border-radius:8px"><?php endif; ?></div>
</div></div></div>
<!-- Edit modal: dates + guests + pay -->
<div class="modal fade" id="e<?php echo $b['id']; ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title serif">Edit #<?php echo $b['id']; ?></h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="post"><div class="modal-body">
<input type="hidden" name="edit_id" value="<?php echo $b['id']; ?>">
<label class="form-label">Check-in</label><input type="date" name="check_in" class="form-control" value="<?php echo esc($b['check_in']); ?>" required>
<label class="form-label mt-2">Check-out</label><input type="date" name="check_out" class="form-control" value="<?php echo esc($b['check_out']); ?>" required>
<label class="form-label mt-2">Guests</label><input type="number" name="num_guests" class="form-control" min="1" max="12" value="<?php echo (int)$b['num_guests']; ?>" required>
<label class="form-label mt-2">Payment status</label><select name="payment_status" class="form-select"><?php foreach(['Unpaid','Part Paid','Fully Paid'] as $o): ?><option <?php echo $b['payment_status']===$o?'selected':''; ?>><?php echo $o; ?></option><?php endforeach; ?></select>
<label class="form-label mt-2">Payment type</label><select name="payment_type" class="form-select"><?php foreach(['Advance','Full'] as $o): ?><option <?php echo $b['payment_type']===$o?'selected':''; ?>><?php echo $o; ?></option><?php endforeach; ?></select>
</div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-ocean">Save</button></div></form>
</div></div></div>
</td></tr>
<?php endforeach; ?>
</table></div></div>
<?php admin_shell_close(); ?>
