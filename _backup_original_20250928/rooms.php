<?php require 'config/db.php'; require 'config/helpers.php'; require 'includes/header.php';
$rooms = $conn->query("SELECT * FROM rooms ORDER BY id")->fetch_all(MYSQLI_ASSOC);
?>
<h2>Rooms & Rates</h2>
<p class="text-muted">Both units: ₱3,000/night • Large capacity • AC • TV • Water dispenser • Dining setup</p>
<div class="row g-3">
<?php foreach($rooms as $r): ?>
<div class="col-md-6"><div class="card room-card">
  <img src="<?php echo esc($r['image_urls']); ?>" alt="<?php echo esc($r['room_name']); ?>">
  <div class="card-body">
    <h5><?php echo esc($r['room_name']); ?></h5>
    <p>Capacity: <?php echo (int)$r['capacity']; ?> guests • <span class="price"><?php echo peso($r['price_per_night']); ?>/night</span></p>
    <p><?php echo esc(substr($r['description'],0,140)); ?>...</p>
    <a href="room-details.php?id=<?php echo $r['id']; ?>" class="btn btn-ocean">View Details</a>
    <a href="booking.php?room_id=<?php echo $r['id']; ?>" class="btn btn-warning">Book Now</a>
  </div>
</div></div>
<?php endforeach; ?>
</div>
<?php require 'includes/footer.php'; ?>
