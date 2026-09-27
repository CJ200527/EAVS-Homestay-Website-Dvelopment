<?php require 'includes/header.php'; ?>
<div class="hero text-center">
  <h1 class="display-5 fw-bold">EAV's Homestay — Camiguin</h1>
  <p class="lead">Oceanfront stay • Large grassy lawn • 2 spacious units • Free scooter on Room 1</p>
  <a href="booking.php" class="btn btn-warning btn-lg fw-bold">Book Direct — No Account Needed</a>
  <a href="rooms.php" class="btn btn-light btn-lg">View Rooms ₱3,000/night</a>
</div>

<div class="row mt-4">
  <div class="col-md-8">
    <h3>Oceanfront Exterior & Grassy Lawn</h3>
    <div class="row g-2 gallery">
      <div class="col-6"><img src="assets/images/outside/528041068_122133605528410695_6982286475227622801_n.jpg" alt="Oceanfront exterior"></div>
      <div class="col-6"><img src="assets/images/outside/526532030_122133411140410695_3817852151501581650_n.jpg" alt="Grassy lawn"></div>
      <div class="col-6"><img src="assets/images/outside/471376988_122117293292410695_8723640025538681244_n.jpg" alt="Homestay front"></div>
      <div class="col-6"><img src="assets/images/outside/471327276_122117290112410695_2531146444987430042_n.jpg" alt="Palm trees"></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="p-3 hero-cream">
      <h4>Quick Booking Check</h4>
      <form action="booking.php" method="get">
        <label class="form-label">Check-in</label><input type="date" name="check_in" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
        <label class="form-label mt-2">Check-out</label><input type="date" name="check_out" class="form-control" required>
        <button class="btn btn-ocean w-100 mt-3">Check Availability</button>
      </form>
      <hr><small>📍 Catohugan, Mahinog Camiguin<br>📞 +63 966 419 7812<br>✉️ ricardo_macarine82@yahoo.com</small>
    </div>
  </div>
</div>

<h3 class="mt-4">Our 2 Units — ₱3,000 / Night each</h3>
<div class="row g-3">
  <div class="col-md-6"><div class="card room-card">
    <img src="assets/images/room1/537256849_122134520288410695_3908963705729607760_n.jpg" class="card-img-top" alt="Room 1">
    <div class="card-body"><h5>Room 1 — Brown Unit</h5><p>Wooden bunks, large capacity. <b>Includes FREE 1-day scooter rental.</b></p><p class="price">₱3,000 / night</p><a href="room-details.php?id=1" class="btn btn-ocean">Details</a> <a href="booking.php?room_id=1" class="btn btn-warning">Book</a></div>
  </div></div>
  <div class="col-md-6"><div class="card room-card">
    <img src="assets/images/room2/538113582_122134519310410695_8735526467688003549_n.jpg" class="card-img-top" alt="Room 2">
    <div class="card-body"><h5>Room 2 — Blue Unit</h5><p>Queen bed + dining setup, mini-fridge. Package TBD — same price for now.</p><p class="price">₱3,000 / night</p><a href="room-details.php?id=2" class="btn btn-ocean">Details</a> <a href="booking.php?room_id=2" class="btn btn-warning">Book</a></div>
  </div></div>
</div>

<h3 class="mt-4">Key Amenities</h3>
<div class="row g-2">
  <div class="col-6 col-md-3"><div class="amenity">❄️<br><b>Air Conditioning</b><br><small>Both units</small></div></div>
  <div class="col-6 col-md-3"><div class="amenity">📺<br><b>Flat-screen TVs</b><br><small>Both units</small></div></div>
  <div class="col-6 col-md-3"><div class="amenity">💧<br><b>Hot/Cold Water Dispenser</b><br><small>Both units</small></div></div>
  <div class="col-6 col-md-3"><div class="amenity">🍽️<br><b>Private Dining Setup</b><br><small>Wooden table & chairs</small></div></div>
</div>
<?php require 'includes/footer.php'; ?>
