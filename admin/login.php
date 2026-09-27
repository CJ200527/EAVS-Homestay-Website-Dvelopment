<?php session_start();
if(isset($_SESSION['admin'])){header('Location: dashboard.php');exit;}
require '../config/db.php';
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $u=$_POST['username']??'';$p=$_POST['password']??'';
  $st=$conn->prepare("SELECT * FROM admins WHERE username=?");$st->bind_param('s',$u);$st->execute();
  $a=$st->get_result()->fetch_assoc();
  if($a&&($a['active']??1)==1&&password_verify($p,$a['password_hash'])){$_SESSION['admin']=$a['username'];$_SESSION['admin_role']=$a['role']??'staff';$_SESSION['admin_id']=$a['id'];header('Location: dashboard.php');exit;}
  $err='Invalid username or password.';
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login - EAV's Homestay</title>
<link href="../assets/vendor/bootstrap.min.css?v=20251001" rel="stylesheet">
<link href="../assets/css/style.css?v=20251001" rel="stylesheet">
<style>
.login-bg{position:fixed;inset:0;background:url('../assets/images/outside/528041068_122133605528410695_6982286475227622801_n.jpg') center/cover;filter:blur(6px) brightness(.75);transform:scale(1.05);}
.login-overlay{position:fixed;inset:0;background:linear-gradient(120deg,rgba(6,38,62,.75),rgba(10,74,122,.45));}
.login-wrap{position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;}
.login-card{background:#fff;border-radius:1rem;box-shadow:0 20px 60px rgba(0,0,0,.3);max-width:420px;width:100%;padding:2.2rem;}
</style>
</head>
<body>
<div class="login-bg"></div><div class="login-overlay"></div>
<div class="login-wrap"><div class="login-card">
<p class="eyebrow" style="color:var(--ocean2)">EAV's Homestay • Owner Only</p>
<h2 class="serif text-center">🌊 Welcome Back</h2>
<p class="text-muted text-center">Sign in to manage bookings.</p>
<?php if($err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
<form method="post" autocomplete="off">
<label class="form-label">Username</label><input name="username" class="form-control" placeholder="Username" required autocomplete="off" value="">
<label class="form-label mt-2">Password</label><input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
<button class="btn btn-ocean btn-pill btn-arrow w-100 mt-3"><span>Login</span></button></form>
<a href="../index.php" class="d-block text-center mt-3">← Back to site</a>
</div></div>
<script src="../assets/vendor/bootstrap.bundle.min.js"></script>
</body></html>
