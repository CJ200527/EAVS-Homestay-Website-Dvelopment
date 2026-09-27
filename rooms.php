<?php require 'config/db.php'; require 'config/helpers.php'; require 'includes/header.php';
$rooms = $conn->query("SELECT * FROM rooms ORDER BY id")->fetch_all(MYSQLI_ASSOC);
?>
<!-- TELLY-STYLE BANNER -->
<section class="telly-hero" style="min-height:44vh">
  <img class="bg" src="assets/images/outside/526532030_122133411140410695_3817852151501581650_n.jpg" alt="Rooms banner">
  <div class="overlay"></div>
  <div class="container content text-center">
    <h1 class="banner-title serif">Rooms</h1>
    <p><a href="index.php" class="text-light">Home</a> &nbsp;/&nbsp; Rooms</p>
  </div>
</section>

<div class="container section">
<p class="text-muted">Showing <?php echo count($rooms); ?> units • Both ₱3,000/night • AC • TV • Dispenser • Dining</p>
<div class="row g-4">
<?php foreach($rooms as $r):
  $gal = $r['id']==1 ? glob('assets/images/room1/*.jpg') : glob('assets/images/room2/*.jpg');
?>
<div class="col-md-6"><div class="room-card">
  <a href="room-details.php?id=<?php echo $r['id']; ?>"><img src="<?php echo esc($r['image_urls']); ?>" alt="<?php echo esc($r['room_name']); ?>"></a>
  <div class="p-3"><h3 class="serif"><a href="room-details.php?id=<?php echo $r['id']; ?>" class="text-dark text-decoration-none"><?php echo esc($r['room_name']); ?></a></h3>
    <table class="spec-table">
      <tr><td><b>Price:</b></td><td class="price"><b><?php echo peso($r['price_per_night']); ?></b> /night</td></tr>
      <tr><td><b>Capacity:</b></td><td>Up to <?php echo (int)$r['capacity']; ?> guests</td></tr>
      <tr><td><b>Bed:</b></td><td><?php echo $r['id']==1?'Wooden bunk beds':'Queen + bunks'; ?></td></tr>
      <tr><td><b>Services:</b></td><td>AC, TV, dispenser, dining<?php echo $r['id']==1?' + FREE 1-day scooter':''; ?></td></tr>
    </table>
    <p class="mt-2"><?php echo esc(substr($r['description'],0,150)); ?>...</p>
    <div class="row g-1 gallery mb-2">
      <?php foreach(array_slice($gal,1,3) as $th): ?><div class="col-4"><img src="<?php echo esc($th); ?>" alt="thumb"></div><?php endforeach; ?>
    </div>
    <a href="room-details.php?id=<?php echo $r['id']; ?>" class="btn btn-dark btn-pill btn-sm">More Details</a>
    <a href="booking.php?room_id=<?php echo $r['id']; ?>" class="btn btn-ocean btn-pill btn-sm">Book Now</a>
  </div>
</div></div>
<?php endforeach; ?>
</div></div>
<?php require 'includes/footer.php'; ?>
