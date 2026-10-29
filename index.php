<?php require 'config/db.php'; require 'config/helpers.php'; require 'includes/header.php';
$siteRooms = [];
try { $siteRooms = $conn->query("SELECT * FROM rooms WHERE status='active' ORDER BY id")->fetch_all(MYSQLI_ASSOC); } catch (Exception $e) { $siteRooms = []; }
$nRooms = count($siteRooms);
?>
<!-- HERO (Telly-style full-bleed) -->
<section class="telly-hero">
  <img class="bg" src="assets/images/outside/528041068_122133605528410695_6982286475227622801_n.jpg" alt="Oceanfront homestay">
  <div class="overlay"></div>
  <div class="container content">
    <span class="eyebrow">Best Homestay With Cozy Rooms • Camiguin</span>
    <h1 class="banner-title serif">Just Experience A New Level of Island Calm.</h1>
    <p class="fs-5">Oceanfront stay • Large grassy lawn • <?php echo $nRooms; ?> spacious unit<?php echo $nRooms===1?'':'s'; ?> • Free 1-day scooter on Room 1.</p>
    <a href="rooms.php" class="btn btn-outline-cream btn-pill btn-arrow"><span>Explore Rooms</span></a>
    <a href="booking.php" class="btn btn-warning btn-pill btn-arrow ms-2"><span>Book Now</span></a>
  </div>
</section>

<!-- ABOUT -->
<section id="about" class="section text-center"><div class="container">
  <h2 class="serif">Welcome to EAV's Homestay</h2>
  <p class="col-lg-8 mx-auto">Blue-roof bungalow facing the sea, shaded by palms and a century-old tree, with a wide grassy lawn for sunset gatherings. Warm wood interiors, cream walls, and heartfelt hospitality.</p>
  <div class="row g-2 gallery mt-3">
    <div class="col-6 col-md-3"><img src="assets/images/outside/526532030_122133411140410695_3817852151501581650_n.jpg" alt="Lawn"></div>
    <div class="col-6 col-md-3"><img src="assets/images/outside/471376988_122117293292410695_8723640025538681244_n.jpg" alt="Front"></div>
    <div class="col-6 col-md-3"><img src="assets/images/outside/471327276_122117290112410695_2531146444987430042_n.jpg" alt="Palms"></div>
    <div class="col-6 col-md-3"><img src="assets/images/outside/528041068_122133605528410695_6982286475227622801_n.jpg" alt="Exterior"></div>
  </div>
</div></section>

<!-- ACTIVITIES = AMENITIES -->
<section id="amenities" class="section section-gray"><div class="container-fluid"><div class="row g-0">
  <div class="col-lg-6"><img src="assets/images/room2/538113582_122134519310410695_8735526467688003549_n.jpg" class="w-100 h-100" style="object-fit:cover;min-height:340px" alt="Room comfort"></div>
  <div class="col-lg-6 p-5">
    <h2 class="serif">What We Offer</h2><p>Both units include the essentials for a fuss-free island stay.</p>
    <div class="row mt-4">
      <div class="col-6 col-md-4"><div class="amenity"><div class="ic">❄️</div><b>Air Conditioning</b><br><small>Both units</small></div></div>
      <div class="col-6 col-md-4"><div class="amenity"><div class="ic">📺</div><b>Flat-screen TV</b><br><small>Both units</small></div></div>
      <div class="col-6 col-md-4"><div class="amenity"><div class="ic">💧</div><b>Hot/Cold Dispenser</b><br><small>Both units</small></div></div>
      <div class="col-6 col-md-4"><div class="amenity"><div class="ic">🍽️</div><b>Private Dining</b><br><small>Wood table</small></div></div>
      <div class="col-6 col-md-4"><div class="amenity"><div class="ic">🛵</div><b>Free Scooter</b><br><small>Room 1, 1 day</small></div></div>
      <div class="col-6 col-md-4"><div class="amenity"><div class="ic">🌊</div><b>Oceanfront Lawn</b><br><small>Shared</small></div></div>
    </div>
  </div>
</div></div></section>

<!-- ACHIEVEMENTS -->
<section class="section"><div class="container text-center">
  <h2 class="serif">Our Place in Numbers</h2><p>Small, family-run, guest-focused.</p>
  <div class="row mt-4">
    <div class="col-6 col-md-3"><div class="stat-num">2</div><small class="text-uppercase">Cozy Units</small></div>
    <div class="col-6 col-md-3"><div class="stat-num">₱3k</div><small class="text-uppercase">Per Night</small></div>
    <div class="col-6 col-md-3"><div class="stat-num">6+6</div><small class="text-uppercase">Guests Capacity</small></div>
    <div class="col-6 col-md-3"><div class="stat-num">100%</div><small class="text-uppercase">Direct Booking</small></div>
  </div>
</div></section>

<!-- ROOMS (Telly cards with spec table) -->
<section id="our-rooms" class="section section-gray"><div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="serif">Explore Our Rooms</h2><a href="rooms.php" class="btn btn-outline-ink btn-pill btn-arrow btn-sm"><span>View All Rooms</span></a></div>
  <div class="row g-3">
    <?php foreach($siteRooms as $sr): ?>
    <div class="col-md-6"><div class="room-card">
      <img src="<?php echo esc($sr['image_urls']); ?>" alt="<?php echo esc($sr['room_name']); ?>">
      <div class="p-3"><h3 class="serif"><?php echo esc($sr['room_name']); ?></h3>
        <table class="spec-table"><tr><td><b>Price:</b></td><td class="price"><b><?php echo peso($sr['price_per_night']); ?></b> /night</td></tr><tr><td><b>Capacity:</b></td><td>Up to <?php echo (int)$sr['capacity']; ?> guests</td></tr></table>
        <a href="room-details.php?id=<?php echo $sr['id']; ?>" class="btn btn-dark btn-pill btn-sm mt-2">More Details</a> <a href="booking.php?room_id=<?php echo $sr['id']; ?>" class="btn btn-ocean btn-pill btn-sm mt-2">Book</a>
      </div>
    </div></div>
    <?php endforeach; ?>
    <?php if(!$siteRooms): ?><p class="text-muted">Rooms opening soon — contact us to book.</p><?php endif; ?>
  </div>
</div></section>

<!-- BOOKING BAR (Telly-style) -->
<section id="booking-wrap" class="section"><div class="container">
  <form action="booking.php" method="get" class="booking-bar d-flex flex-wrap align-items-end justify-content-around p-4 gap-3">
    <div><label>Arrival</label><input type="date" name="check_in" class="form-control" required min="<?php echo date('Y-m-d'); ?>"></div>
    <div><label>Departure</label><input type="date" name="check_out" class="form-control" required></div>
    <div><label>Room</label><select name="room_id" class="form-select"><?php foreach($siteRooms as $sr): ?><option value="<?php echo $sr['id']; ?>"><?php echo esc($sr['room_name']); ?></option><?php endforeach; ?></select></div>
    <div><label>Guests</label><input type="number" name="guests" value="2" min="1" max="10" class="form-control"></div>
    <button class="btn btn-dark btn-pill btn-arrow"><span>Check availability</span></button>
  </form>
</div></section>

<!-- CONTACT SPLIT -->
<section class="section section-gray p-0"><div class="container-fluid"><div class="row g-0">
  <div class="col-lg-6 p-5"><h2 class="serif">Get In Touch</h2><p>Message us for direct bookings and island tips.</p>
    <p><b>Address:</b> Catohugan, Mahinog Camiguin, 9101 Phil.<br><b>Phone:</b> +63 966 419 7812<br><b>Email:</b> ricardo_macarine82@yahoo.com</p>
    <a href="booking.php" class="btn btn-dark btn-pill btn-arrow"><span>Book Direct</span></a>
  </div>
  <div class="col-lg-6"><img src="assets/images/outside/526532030_122133411140410695_3817852151501581650_n.jpg" class="w-100 h-100" style="object-fit:cover;min-height:300px" alt="Lawn"></div>
</div></div></section>
<?php require 'includes/footer.php'; ?>
