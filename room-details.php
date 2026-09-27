<?php require 'config/db.php'; require 'config/helpers.php';
$id = (int)($_GET['id'] ?? 1);
$st = $conn->prepare("SELECT * FROM rooms WHERE id=?"); $st->bind_param('i',$id); $st->execute();
$room = $st->get_result()->fetch_assoc(); if(!$room) die('Room not found');
require 'includes/header.php';
$g1 = glob('assets/images/room1/*.jpg'); $g2 = glob('assets/images/room2/*.jpg');
$gal = ($id==1)?$g1:$g2;
$cover = esc($room['image_urls']);
?>
<!-- TELLY-STYLE BANNER (same as Rooms) -->
<section class="telly-hero" style="min-height:44vh">
  <img class="bg" src="assets/images/outside/526532030_122133411140410695_3817852151501581650_n.jpg" alt="Room details banner">
  <div class="overlay"></div>
  <div class="container content text-center">
    <h1 class="banner-title serif">Room Details</h1>
    <p><a href="index.php" class="text-light">Home</a> &nbsp;/&nbsp; <a href="rooms.php" class="text-light">Rooms</a> &nbsp;/&nbsp; <?php echo esc($room['room_name']); ?></p>
  </div>
</section>

<div class="container section">
<!-- LARGE CARD (same style as rooms.php cards, larger cover) -->
<div class="room-card mb-4">
  <a href="<?php echo $cover; ?>" target="_blank"><img class="cover-full" src="<?php echo $cover; ?>" alt="<?php echo esc($room['room_name']); ?>"></a>
  <div class="p-4">
    <h2 class="serif"><?php echo esc($room['room_name']); ?></h2>
    <p><span class="price fs-4"><?php echo peso($room['price_per_night']); ?> / night</span> • Up to <?php echo (int)$room['capacity']; ?> guests</p>
    <!-- DETAILS BELOW (same spec-table as Rooms) -->
    <table class="spec-table mb-3" style="max-width:560px">
      <tr><td><b>Price:</b></td><td class="price"><b><?php echo peso($room['price_per_night']); ?></b> /night</td></tr>
      <tr><td><b>Capacity:</b></td><td>Up to <?php echo (int)$room['capacity']; ?> guests</td></tr>
      <tr><td><b>Bed:</b></td><td><?php echo $id==1?'Wooden bunk beds':'Queen + bunks'; ?></td></tr>
      <tr><td><b>Services:</b></td><td>AC, TV, dispenser, private dining<?php echo $id==1?' + FREE 1-day scooter':''; ?></td></tr>
    </table>
    <p><?php echo esc($room['description']); ?></p>
    <?php if($id==1): ?><div class="alert alert-warning">🛵 <b>Base package includes FREE 1-day scooter rental!</b></div><?php endif; ?>
    <div class="row g-2 gallery mb-3">
      <?php foreach(array_slice($gal,0,6) as $img): ?><div class="col-6 col-md-4"><a href="<?php echo esc($img); ?>" target="_blank"><img src="<?php echo esc($img); ?>" alt="room photo"></a></div><?php endforeach; ?>
    </div>
    <h4 class="serif">Features & Amenities</h4>
    <ul><li>❄️ Air conditioning</li><li>📺 Flat-screen TV</li><li>💧 Hot/cold water dispenser</li><li>🍽️ Private dining setup (wooden table & chairs)</li><li>🧊 Mini-fridge (Room 2) / Electric fan backup</li></ul>
    <a href="booking.php?room_id=<?php echo $room['id']; ?>" class="btn btn-warning btn-pill btn-lg btn-arrow"><span>Book This Room</span></a>
    <a href="rooms.php" class="btn btn-outline-ink btn-pill ms-2">Back to Rooms</a>
  </div>
</div>
</div>
<?php require 'includes/footer.php'; ?>
