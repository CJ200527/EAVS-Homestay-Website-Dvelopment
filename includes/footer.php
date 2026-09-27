</main>
<footer class="eav-footer mt-0" id="contact">
  <div class="container py-5">
    <div class="row">
      <div class="col-md-3"><h5 class="serif">About us</h5><p>Oceanfront 2-room homestay in Camiguin. Ocean blue comfort, warm wood hospitality.</p></div>
      <div class="col-md-3"><h5 class="serif">Quick Links</h5><p class="mb-1"><a href="index.php#about">About</a></p><p class="mb-1"><a href="index.php#amenities">Activities</a></p><p class="mb-1"><a href="rooms.php">Rooms</a></p><p class="mb-1"><a href="booking.php">Booking</a></p></div>
      <div class="col-md-3"><h5 class="serif">Contact</h5><p class="mb-1">📍 Catohugan, Mahinog Camiguin, 9101 Phil.</p><p class="mb-1">📞 <a href="tel:+639664197812">+63 966 419 7812</a></p><p class="mb-1">✉️ <a href="mailto:ricardo_macarine82@yahoo.com">ricardo_macarine82@yahoo.com</a></p></div>
      <div class="col-md-3"><h5 class="serif">Payment</h5><p>Cash • GCash • Maya<br><small>Advance or Full payment.</small></p><a href="booking.php" class="btn btn-light btn-pill btn-sm btn-arrow"><span>Book Direct</span></a></div>
    </div>
    <hr><small>© <?php echo date('Y'); ?> EAV's Homestay • BSIT SIA 101 Project • Offline-ready</small>
  </div>
</footer>
<script src="assets/vendor/bootstrap.bundle.min.js?v=20251001"></script>
<!-- Admin curtain: roll-down login card (same Welcome Back design, centered). Back to site rolls it up. -->
<div id="adminCurtain" aria-hidden="true">
  <div class="curtain-bg"></div><div class="curtain-shade"></div>
  <div class="curtain-card">
    <p class="eyebrow" style="color:var(--ocean2)">EAV's Homestay • Owner Only</p>
    <h2 class="serif text-center">🌊 Welcome Back</h2>
    <p class="text-muted text-center">Sign in to manage bookings.</p>
    <form method="post" action="admin/login.php" autocomplete="off">
      <label class="form-label">Username</label><input name="username" class="form-control" placeholder="Username" required autocomplete="off" value="">
      <label class="form-label mt-2">Password</label><input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
      <button class="btn btn-ocean btn-pill btn-arrow w-100 mt-3"><span>Login</span></button>
    </form>
    <a href="#" onclick="closeAdminCurtain();return false;" class="d-block text-center mt-3">← Back to site</a>
  </div>
</div>
<!-- Offline mode: local bundle. CDN fallback: https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js -->
<script src="assets/js/main.js?v=20251001"></script>
</body>
</html>
