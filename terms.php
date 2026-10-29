<?php require 'config/helpers.php';
$back = isset($_GET['from']) && $_GET['from'] === 'booking' ? 'booking.php' : 'index.php';
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Terms &amp; Conditions - EAV's Homestay</title>
<link rel="icon" href="assets/images/logo.jpg">
<link href="assets/vendor/bootstrap.min.css?v=20251017" rel="stylesheet">
<link href="assets/css/style.css?v=20251017" rel="stylesheet">
</head><body>
<nav class="navbar navbar-expand bg-white sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php"><img src="assets/images/logo.jpg" alt="EAV's Homestay logo" class="brand-logo"> EAV's Homestay</a>
    <a class="btn btn-ocean btn-pill btn-sm" href="<?php echo esc($back); ?>">Back</a>
  </div>
</nav>

<header class="telly-hero" style="min-height:34vh">
  <img class="bg" src="assets/images/outside/526532030_122133411140410695_3817852151501581650_n.jpg" alt="EAV's Homestay">
  <div class="overlay"></div>
  <div class="container content text-center">
    <h1 class="banner-title serif">Terms &amp; Conditions</h1>
    <p class="text-light">EAV's Homestay · Catohugan, Mahinog, Camiguin</p>
  </div>
</header>

<div class="container section">
  <div class="room-card p-4">
    <?php require 'includes/terms-content.php'; ?>
    <div class="d-flex gap-2 mt-4">
      <a href="<?php echo esc($back); ?>" class="btn btn-ocean btn-pill btn-arrow"><span>Go back</span></a>
    </div>
  </div>
</div>

<footer class="footer mt-5"><div class="container text-center small text-muted">
  © <?php echo date('Y'); ?> EAV's Homestay · Catohugan, Mahinog, Camiguin, Philippines
</div></footer>
<script src="assets/vendor/bootstrap.bundle.min.js?v=20251017"></script>
</body></html>