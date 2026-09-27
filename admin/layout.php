<?php
// Shared admin shell: sidebar + header (title, welcome fullname, role, avatar initial)
function admin_shell_open($title, $active) {
  $u = $_SESSION['admin'] ?? 'admin';
  $role = $_SESSION['admin_role'] ?? 'staff';
  $initial = strtoupper(substr($u, 0, 1));
  $owner = ($role === 'owner');
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($title); ?> - EAV's Admin</title>
<link href="../assets/vendor/bootstrap.min.css?v=20251001" rel="stylesheet">
<link href="../assets/css/admin.css?v=20251001" rel="stylesheet">
</head><body>
<aside class="ad-side">
  <a class="ad-brand serif" href="dashboard.php">🌊 EAV's</a>
  <nav>
    <a href="dashboard.php" class="<?php echo $active==='dash'?'on':''; ?>">📊 Dashboard</a>
    <a href="bookings.php" class="<?php echo $active==='book'?'on':''; ?>">📅 Booking</a>
    <a href="rooms.php" class="<?php echo $active==='rooms'?'on':''; ?>">🛏️ Rooms</a>
    <?php if($owner): ?><a href="users.php" class="<?php echo $active==='users'?'on':''; ?>">👥 Users</a><?php endif; ?>
    <a href="../index.php">🌐 View Site</a>
    <a href="logout.php" class="out">⏻ Logout</a>
  </nav>
</aside>
<div class="ad-main">
<header class="ad-head">
  <div><h2 class="serif mb-0"><?php echo htmlspecialchars($title); ?></h2><small class="text-muted">EAV's Homestay • Admin Panel</small></div>
  <div class="ad-user"><span class="avatar"><?php echo htmlspecialchars($initial); ?></span>
    <span>Welcome, <b><?php echo htmlspecialchars($u); ?></b><br><small class="badge bg-info"><?php echo htmlspecialchars($role); ?></small></span>
  </div>
</header>
<div class="ad-body">
<?php }
function admin_shell_close() { ?>
</div></div>
<script src="../assets/vendor/bootstrap.bundle.min.js?v=20251001"></script>
<script src="../assets/vendor/fullcalendar.min.js?v=20251001"></script>
<script>
// Generic confirm modal for admin actions (offline Bootstrap)
function askConfirm(msg, href){
  document.getElementById('cfMsg').textContent = msg;
  document.getElementById('cfBtn').href = href;
  new bootstrap.Modal(document.getElementById('cfModal')).show();
  return false;
}
</script>
<div class="modal fade" id="cfModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title serif">Please confirm</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body" id="cfMsg"></div>
<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><a id="cfBtn" href="#" class="btn btn-ocean" style="background:#0A4A7A;color:#fff">Confirm</a></div>
</div></div></div>
</body></html>
<?php }
