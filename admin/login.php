<?php session_start();
if(isset($_SESSION['admin'])){header('Location: dashboard.php');exit;}
require '../config/db.php'; require '../config/helpers.php';
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $u=$_POST['username']??'';$p=$_POST['password']??'';
  $st=$conn->prepare("SELECT * FROM admins WHERE username=?");$st->bind_param('s',$u);$st->execute();
  $a=$st->get_result()->fetch_assoc();
  if($a&&($a['active']??1)==1&&password_verify($p,$a['password_hash'])){$_SESSION['admin']=$a['username'];$_SESSION['admin_role']=$a['role']??'staff';$_SESSION['admin_id']=$a['id'];flash('Welcome back, '.$a['username'].'.');header('Location: dashboard.php?welcome=1');exit;}
  $err='Invalid username or password.';
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login - EAV's Homestay</title>
<link rel="icon" href="../assets/images/logo.jpg">
<link href="../assets/vendor/bootstrap.min.css?v=20251008" rel="stylesheet">
<link href="../assets/css/style.css?v=20251008" rel="stylesheet">
<style>
.login-bg{position:fixed;inset:0;background:url('../assets/images/outside/528041068_122133605528410695_6982286475227622801_n.jpg') center/cover;filter:blur(6px) brightness(.75);transform:scale(1.05);}
.login-overlay{position:fixed;inset:0;background:linear-gradient(120deg,rgba(6,38,62,.75),rgba(10,74,122,.45));}
.login-wrap{position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;}
.login-card{background:#fff;border-radius:1rem;box-shadow:0 20px 60px rgba(0,0,0,.3);max-width:420px;width:100%;padding:2.2rem;}
.login-logo{width:72px;height:72px;border-radius:50%;object-fit:cover;display:block;margin:0 auto .6rem;border:3px solid #fff;box-shadow:0 4px 14px rgba(10,74,122,.35);}
/* Floating toast (CPSC-style, never shifts the card) */
.toast-container{position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:9999;display:flex;flex-direction:column;align-items:center;gap:10px;pointer-events:none;}
.toast-card{pointer-events:auto;display:flex;align-items:center;gap:12px;min-width:300px;max-width:450px;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;box-shadow:0 8px 20px rgba(0,0,0,.18);background:#fff;border:1px solid #e0e0e0;animation:toastIn .4s cubic-bezier(.175,.885,.32,1.275),toastOut .4s ease 3.5s forwards;}
.toast-card.toast-success{background:#d4edda;border-color:#c3e6cb;color:#155724;}
.toast-card.toast-error,.toast-card.toast-danger{background:#fdf7f7;border-color:#f5c6cb;color:#721c24;}
.toast-icon{font-size:16px;flex-shrink:0;}
.toast-message{flex:1;text-align:left;}
.toast-close{background:transparent;border:none;color:inherit;font-size:14px;cursor:pointer;opacity:.7;padding:0 4px;line-height:1;}
@keyframes toastIn{from{opacity:0;transform:translateY(-30px) scale(.9);}to{opacity:1;transform:none;}}
@keyframes toastOut{from{opacity:1;}to{opacity:0;transform:translateY(-20px) scale(.9);}}
</style>
</head>
<body>
<div class="login-bg"></div><div class="login-overlay"></div>
<div class="toast-container" id="toast-container"><?php if($err): ?><div class="toast-card toast-error"><span class="toast-icon">✕</span><span class="toast-message"><?php echo htmlspecialchars($err); ?></span><button type="button" class="toast-close" onclick="this.parentNode.remove()">✕</button></div><?php endif; ?></div>
<div class="login-wrap"><div class="login-card">
<img src="../assets/images/logo.jpg" alt="EAV's Homestay logo" class="login-logo">
<p class="eyebrow text-center" style="color:var(--ocean2)">EAV's Homestay • Owner Only</p>
<h2 class="serif text-center">Welcome Back</h2>
<p class="text-muted text-center">Sign in to manage bookings.</p>
<form method="post" autocomplete="off">
<label class="form-label">Username</label><input name="username" class="form-control" placeholder="Username" required autocomplete="off" value="">
<label class="form-label mt-2">Password</label><input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
<button class="btn btn-ocean btn-pill btn-arrow w-100 mt-3"><span>Login</span></button></form>
<a href="../index.php" class="d-block text-center mt-3">← Back to site</a>
</div></div>
<script src="../assets/vendor/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('#toast-container .toast-card').forEach(function(t){setTimeout(function(){t.style.transition='opacity .4s ease,transform .4s ease';t.style.opacity='0';t.style.transform='translateY(-20px) scale(.9)';setTimeout(function(){if(t.parentNode)t.remove();},400);},4000);});
</script>
</body></html>
