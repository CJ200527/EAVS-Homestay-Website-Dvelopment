<?php $page = $page ?? ''; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>EAV's Homestay - Camiguin</title>
<link href="assets/vendor/bootstrap.min.css?v=20251001" rel="stylesheet">
<!-- Offline mode: local Bootstrap. CDN fallback (needs internet): https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css -->
<link href="assets/css/style.css?v=20251001" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top eav-nav">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold" href="index.php">🌊 EAV's Homestay</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#about">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#amenities">Activities</a></li>
        <li class="nav-item"><a class="nav-link" href="rooms.php">Rooms</a></li>
        <li class="nav-item"><a class="nav-link" href="booking.php">Booking</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#contact">Contact</a></li>
        <li class="nav-item"><a class="nav-link" href="admin/login.php" onclick="openAdminCurtain();return false;">Admin</a></li>
      </ul>
      <a href="booking.php" class="btn btn-ocean btn-pill btn-arrow"><span>Book Now</span></a>
    </div>
  </div>
</nav>
<main>
