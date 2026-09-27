<?php require 'auth.php'; require '../config/db.php'; require '../config/helpers.php'; require 'layout.php';
// Actions (all confirm-gated in UI via askConfirm)
$msg='';
if(isset($_GET['action'],$_GET['id'])){
  $id=(int)$_GET['id']; $a=$_GET['action'];
  $allowed=['Pending','Approved','Declined','Completed','CheckedIn','CheckedOut'];
  if(in_array($a,$allowed)){
    if($a==='Approved'){
      $b=$conn->query("SELECT * FROM bookings WHERE id=$id")->fetch_assoc();
      if($b && !is_room_available($conn,$b['room_id'],$b['check_in'],$b['check_out'],$id)) $msg='Cannot approve: overlaps another Approved booking.';
      else { $conn->query("UPDATE bookings SET booking_status='Approved' WHERE id=$id"); header('Location: bookings.php'); exit; }
    } else { $conn->query("UPDATE bookings SET booking_status='".$conn->real_escape_string($a)."' WHERE id=$id"); header('Location: bookings.php'); exit; }
  }
}
if(isset($_GET['pay'],$_GET['id'])){
  $id=(int)$_GET['id']; $p=$_GET['pay'];
  if(in_array($p,['Pending','Advance Paid','Fully Paid'])){ $conn->query("UPDATE bookings SET payment_status='".$conn->real_escape_string($p)."' WHERE id=$id"); header('Location: bookings.php'); exit; }
}
// Edit: dates + guests + pay (overlap re-checked)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_id'])){
  $id=(int)$_POST['edit_id']; $ci=$_POST['check_in']; $co=$_POST['check_out']; $g=(int)$_POST['num_guests'];
  $ps=$_POST['payment_status']; $pt=$_POST['payment_type'];
  $b=$conn->query("SELECT * FROM bookings WHERE id=$id")->fetch_assoc();
  if(!$b) $msg='Booking not found.';
  elseif($ci>=$co) $msg='Check-out must be after check-in.';
  elseif(!is_room_available($conn,$b['room_id'],$ci,$co,$id)) $msg='New dates overlap another Approved booking.';
  else {
    $nights=nights_between($ci,$co);
    $room=$conn->query("SELECT price_per_night FROM rooms WHERE id=".(int)$b['room_id'])->fetch_assoc();
    $total=$nights*(float)$room['price_per_night'];
    $st=$conn->prepare("UPDATE bookings SET check_in=?,check_out=?,num_guests=?,payment_status=?,payment_type=?,total_amount=? WHERE id=?");
    $st->bind_param('ssissdi',$ci,$co,$g,$ps,$pt,$total,$id); $st->execute();
    header('Location: bookings.php'); exit;
  }
}
// Search + filter (prepared, no warnings)
$q=trim($_GET['q']??''); $f=$_GET['filter']??'all'; $rm=(int)($_GET['room']??0);
$allowed_f=['all','Pending','Approved','Declined','Completed','CheckedIn','CheckedOut'];
if(!in_array($f,$allowed_f)) $f='all';
$where=["1"]; $types=''; $params=[];
if($f!=='all'){$where[]="b.booking_status=?";$types.='s';$params[]=$f;}
if($rm>0){$where[]="b.room_id=?";$types.='i';$params[]=$rm;}
if($q!==''){$where[]="(b.guest_name LIKE ? OR b.phone LIKE ? OR b.email LIKE ?)";$types.='sss';$like="%$q%";$params[]=$like;$params[]=$like;$params[]=$like;}
$sql="SELECT b.*,r.room_name FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE ".implode(' AND ',$where)." ORDER BY b.check_in DESC";
$st=$conn->prepare($sql); if($types){$st->bind_param($types,...$params);} $st->execute();
$rows=$st->get_result()->fetch_all(MYSQLI_ASSOC);
$rooms=$conn->query("SELECT id,room_name FROM rooms ORDER BY id")->fetch_all(MYSQLI_ASSOC);
admin_shell_open('Booking','book');
?>
<?php if($msg): ?><div class="alert alert-danger"><?php echo esc($msg); ?></div><?php endif; ?>
<div class="ad-card"><form method="get" class="row g-2">
  <div class="col-md-5"><input name="q" class="form-control" placeholder="Search guest / phone / email" value="<?php echo esc($q); ?>"></div>
  <div class="col-md-3"><select name="filter" class="form-select">
    <?php foreach(['all','Pending','Approved','CheckedIn','CheckedOut','Completed','Declined'] as $o): ?><option value="<?php echo $o; ?>" <?php echo $f===$o?'selected':''; ?>><?php echo $o; ?></option><?php endforeach; ?>
  </select></div>
  <div class="col-md-2"><select name="room" class="form-select"><option value="0">All rooms</option>
    <?php foreach($rooms as $r): ?><option value="<?php echo $r['id']; ?>" <?php echo $rm==$r['id']?'selected':''; ?>><?php echo esc($r['room_name']); ?></option><?php endforeach; ?>
  </select></div>
  <div class="col-md-2"><button class="btn btn-ocean w-100">Search</button></div>
</form></div>
<div class="ad-card"><div class="table-responsive"><table class="table table-bordered table-sm align-middle">
<tr><th>#</th><th>Guest</th><th>Room / Dates</th><th>Total / Pay</th><th>Proof</th><th>Status</th><th>Actions</th></tr>
<?php if(!$rows): ?><tr><td colspan="7" class="text-muted">No bookings match.</td></tr><?php endif; ?>
<?php foreach($rows as $b): ?>
<tr>
<td><?php echo $b['id']; ?><br><small><?php echo esc($b['created_at']); ?></small></td>
<td><b><?php echo esc($b['guest_name']); ?></b><br><?php echo esc($b['phone']); ?><br><?php echo esc($b['email']); ?><br><?php echo (int)$b['num_guests']; ?> guests</td>
<td><?php echo esc($b['room_name']); ?><br><?php echo esc($b['check_in']); ?> → <?php echo esc($b['check_out']); ?></td>
<td><?php echo peso($b['total_amount']); ?><br><small><?php echo esc($b['payment_type']); ?> via <?php echo esc($b['payment_method']); ?></small><br><?php echo status_badge($b['payment_status']); ?></td>
<td><?php if($b['proof_image']): ?><a href="../<?php echo esc($b['proof_image']); ?>" target="_blank"><img src="../<?php echo esc($b['proof_image']); ?>" style="width:80px;border-radius:6px"></a><?php else: ?><small class="text-muted">Cash / none</small><?php endif; ?></td>
<td><?php echo status_badge($b['booking_status']); ?></td>
<td>
<div class="btn-group-vertical btn-group-sm">
<button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#v<?php echo $b['id']; ?>">View</button>
<a class="btn btn-primary" onclick="return askConfirm('Approve booking #<?php echo $b['id']; ?>?','?action=Approved&id=<?php echo $b['id']; ?>')">Approve</a>
<a class="btn btn-warning" onclick="return askConfirm('Move booking #<?php echo $b['id']; ?> back to Pending?','?action=Pending&id=<?php echo $b['id']; ?>')">Unapprove</a>
<button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#e<?php echo $b['id']; ?>">Edit</button>
<a class="btn btn-dark" onclick="return askConfirm('Check-in booking #<?php echo $b['id']; ?>?','?action=CheckedIn&id=<?php echo $b['id']; ?>')">Check-in</a>
<a class="btn btn-dark" onclick="return askConfirm('Check-out booking #<?php echo $b['id']; ?>?','?action=CheckedOut&id=<?php echo $b['id']; ?>')">Check-out</a>
<a class="btn btn-success" onclick="return askConfirm('Mark booking #<?php echo $b['id']; ?> Completed?','?action=Completed&id=<?php echo $b['id']; ?>')">Complete</a>
<a class="btn btn-danger" onclick="return askConfirm('Decline booking #<?php echo $b['id']; ?>?','?action=Declined&id=<?php echo $b['id']; ?>')">Decline</a>
</div>
<div class="btn-group btn-group-sm mt-1">
<a class="btn btn-outline-warning" onclick="return askConfirm('Set Advance Paid?','?pay=Advance+Paid&id=<?php echo $b['id']; ?>')">Adv</a>
<a class="btn btn-outline-success" onclick="return askConfirm('Set Fully Paid? (counts to Revenue)','?pay=Fully+Paid&id=<?php echo $b['id']; ?>')">Full</a>
</div>
<!-- View modal -->
<div class="modal fade" id="v<?php echo $b['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title serif">Booking #<?php echo $b['id']; ?></h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p><b><?php echo esc($b['guest_name']); ?></b> (<?php echo (int)$b['num_guests']; ?>) — <?php echo esc($b['phone']); ?> — <?php echo esc($b['email']); ?></p>
<p><?php echo esc($b['room_name']); ?>: <?php echo esc($b['check_in']); ?> → <?php echo esc($b['check_out']); ?> (<?php echo nights_between($b['check_in'],$b['check_out']); ?> nights)</p>
<p>Total <?php echo peso($b['total_amount']); ?> • <?php echo esc($b['payment_type']); ?> via <?php echo esc($b['payment_method']); ?> • <?php echo status_badge($b['payment_status']); ?> • <?php echo status_badge($b['booking_status']); ?></p>
<?php if($b['proof_image']): ?><img src="../<?php echo esc($b['proof_image']); ?>" class="w-100" style="border-radius:8px"><?php endif; ?></div>
</div></div></div>
<!-- Edit modal: dates + guests + pay -->
<div class="modal fade" id="e<?php echo $b['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title serif">Edit #<?php echo $b['id']; ?></h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="post"><div class="modal-body">
<input type="hidden" name="edit_id" value="<?php echo $b['id']; ?>">
<label class="form-label">Check-in</label><input type="date" name="check_in" class="form-control" value="<?php echo esc($b['check_in']); ?>" required>
<label class="form-label mt-2">Check-out</label><input type="date" name="check_out" class="form-control" value="<?php echo esc($b['check_out']); ?>" required>
<label class="form-label mt-2">Guests</label><input type="number" name="num_guests" class="form-control" min="1" max="12" value="<?php echo (int)$b['num_guests']; ?>" required>
<label class="form-label mt-2">Payment status</label><select name="payment_status" class="form-select"><?php foreach(['Pending','Advance Paid','Fully Paid'] as $o): ?><option <?php echo $b['payment_status']===$o?'selected':''; ?>><?php echo $o; ?></option><?php endforeach; ?></select>
<label class="form-label mt-2">Payment type</label><select name="payment_type" class="form-select"><?php foreach(['Advance','Full'] as $o): ?><option <?php echo $b['payment_type']===$o?'selected':''; ?>><?php echo $o; ?></option><?php endforeach; ?></select>
</div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-ocean">Save</button></div></form>
</div></div></div>
</td></tr>
<?php endforeach; ?>
</table></div></div>
<?php admin_shell_close(); ?>
