<?php require 'auth.php'; require '../config/db.php'; require '../config/helpers.php'; require 'layout.php';
$msg=''; $edit=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim($_POST['room_name']); $color=trim($_POST['color']); $cap=(int)$_POST['capacity'];
  $price=(float)$_POST['price']; $desc=trim($_POST['description']); $img=trim($_POST['image_urls']);
  if(strlen($name)<2)$msg='Room name required.';
  else{
    if(!empty($_POST['id'])){ $id=(int)$_POST['id'];
      $st=$conn->prepare("UPDATE rooms SET room_name=?,color=?,capacity=?,price_per_night=?,description=?,image_urls=? WHERE id=?");
      $st->bind_param('ssidssi',$name,$color,$cap,$price,$desc,$img,$id); $st->execute(); $msg='Room updated.';
    } else {
      $st=$conn->prepare("INSERT INTO rooms (room_name,color,capacity,price_per_night,description,image_urls) VALUES (?,?,?,?,?,?)");
      $st->bind_param('ssidss',$name,$color,$cap,$price,$desc,$img); $st->execute(); $msg='Room added.';
    }
  }
}
if(isset($_GET['edit'])){ $id=(int)$_GET['edit'];
  $st=$conn->prepare("SELECT * FROM rooms WHERE id=?"); $st->bind_param('i',$id); $st->execute();
  $edit=$st->get_result()->fetch_assoc();
}
if(isset($_GET['del'])){ $id=(int)$_GET['del'];
  $c=$conn->query("SELECT COUNT(*) c FROM bookings WHERE room_id=$id AND booking_status IN ('Approved','CheckedIn','Pending')")->fetch_assoc()['c'];
  if($c>0) $msg="Cannot delete: $c active/pending booking(s) use this room.";
  else { $conn->query("DELETE FROM rooms WHERE id=$id"); header('Location: rooms.php'); exit; }
}
$rooms=$conn->query("SELECT r.*, (SELECT COUNT(*) FROM bookings b WHERE b.room_id=r.id AND b.booking_status IN ('Approved','CheckedIn')) AS active_n FROM rooms r ORDER BY id")->fetch_all(MYSQLI_ASSOC);
admin_shell_open('Rooms','rooms');
?>
<?php if($msg): ?><div class="alert alert-info"><?php echo esc($msg); ?></div><?php endif; ?>
<div class="ad-card"><h5 class="serif"><?php echo $edit?'Edit room #'.$edit['id']:'Add room (future expansion)'; ?></h5>
<form method="post" class="row g-2">
<input type="hidden" name="id" value="<?php echo $edit['id']??''; ?>">
<div class="col-md-4"><label class="form-label">Room name</label><input name="room_name" class="form-control" required value="<?php echo esc($edit['room_name']??''); ?>" placeholder="Room 3 - Green Unit"></div>
<div class="col-md-2"><label class="form-label">Color</label><input name="color" class="form-control" value="<?php echo esc($edit['color']??''); ?>" placeholder="Green"></div>
<div class="col-md-2"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" min="1" max="20" value="<?php echo esc($edit['capacity']??6); ?>"></div>
<div class="col-md-2"><label class="form-label">Price/night</label><input type="number" step="0.01" name="price" class="form-control" value="<?php echo esc($edit['price_per_night']??3000); ?>"></div>
<div class="col-md-8"><label class="form-label">Description</label><input name="description" class="form-control" value="<?php echo esc($edit['description']??''); ?>"></div>
<div class="col-md-4"><label class="form-label">Cover image path</label><input name="image_urls" class="form-control" value="<?php echo esc($edit['image_urls']??'assets/images/room1/537256849_122134520288410695_3908963705729607760_n.jpg'); ?>"></div>
<div class="col-12"><button class="btn btn-ocean"><?php echo $edit?'Save changes':'Add room'; ?></button>
<?php if($edit): ?><a href="rooms.php" class="btn btn-secondary">Cancel</a><?php endif; ?></div>
</form></div>
<div class="ad-card"><div class="table-responsive"><table class="table table-bordered align-middle">
<tr><th>#</th><th>Cover</th><th>Room</th><th>Cap / Price</th><th>Active bookings</th><th>Actions</th></tr>
<?php foreach($rooms as $r): ?><tr>
<td><?php echo $r['id']; ?></td>
<td><img src="../<?php echo esc($r['image_urls']); ?>" style="width:110px;border-radius:8px"></td>
<td><b><?php echo esc($r['room_name']); ?></b> (<?php echo esc($r['color']); ?>)<br><small><?php echo esc(substr($r['description'],0,120)); ?></small></td>
<td><?php echo (int)$r['capacity']; ?> guests<br><b><?php echo peso($r['price_per_night']); ?></b>/night</td>
<td><?php echo (int)$r['active_n']; ?></td>
<td><a class="btn btn-sm btn-info" href="?edit=<?php echo $r['id']; ?>">Edit</a>
<a class="btn btn-sm btn-danger" onclick="return askConfirm('Delete <?php echo esc($r['room_name']); ?>? Blocked if active bookings exist.','?del=<?php echo $r['id']; ?>')">Delete</a>
<a class="btn btn-sm btn-secondary" href="../room-details.php?id=<?php echo $r['id']; ?>" target="_blank">View</a></td>
</tr><?php endforeach; ?>
</table></div></div>
<?php admin_shell_close(); ?>
