<?php require 'config/db.php'; require 'config/helpers.php';
$id = (int)($_GET['id'] ?? 1);
$st = $conn->prepare("SELECT * FROM rooms WHERE id=?"); $st->bind_param('i',$id); $st->execute();
$room = $st->get_result()->fetch_assoc(); if(!$room) die('Room not found');
require 'includes/header.php';
$g1 = glob('assets/images/room1/*.jpg'); $g2 = glob('assets/images/room2/*.jpg');
$gal = ($id==1)?$g1:$g2;
?>
<h2><?php echo esc($room['room_name']); ?></h2>
<p><span class="price fs-4"><?php echo peso($room['price_per_night']); ?> / night</span> • Capacity: <?php echo (int)$room['capacity']; ?> guests</p>
<p><?php echo esc($room['description']); ?></p>
<?php if($id==1): ?><div class="alert alert-warning">🛵 <b>Base package includes FREE 1-day scooter rental!</b></div><?php endif; ?>
<div class="row g-2 gallery mb-3">
<?php foreach(array_slice($gal,0,6) as $img): ?><div class="col-6 col-md-4"><img src="<?php echo esc($img); ?>" alt="room photo"></div><?php endforeach; ?>
</div>
<h4>Amenities</h4>
<ul><li>❄️ Air conditioning</li><li>📺 Flat-screen TV</li><li>💧 Hot/cold water dispenser</li><li>🍽️ Private dining setup (wooden table & chairs)</li><li>🧊 Mini-fridge (Room 2) / Electric fan backup</li></ul>
<a href="booking.php?room_id=<?php echo $room['id']; ?>" class="btn btn-warning btn-lg">Book This Room — No Account Needed</a>
<a href="rooms.php" class="btn btn-secondary">Back</a>
<?php require 'includes/footer.php'; ?>
