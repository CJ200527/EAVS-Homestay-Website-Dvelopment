<?php require 'auth.php'; require_owner(); require '../config/db.php'; require '../config/helpers.php'; require 'layout.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['new_user'])){
  $u=trim($_POST['username']); $p=$_POST['password']; $r=$_POST['role']==='owner'?'owner':'staff';
  if(strlen($u)<3)$msg='Username min 3 chars.';
  elseif(strlen($p)<3)$msg='Password min 3 chars.';
  else{ $h=password_hash($p,PASSWORD_DEFAULT);
    $st=$conn->prepare("INSERT INTO admins (username,password_hash,role,active) VALUES (?,?,?,1)");
    $st->bind_param('sss',$u,$h,$r);
    $msg=$st->execute()?'Staff account created.':'Username already exists.'; }
}
if(isset($_GET['toggle'])){
  $id=(int)$_GET['toggle'];
  if($id!==($_SESSION['admin_id']??-1)) $conn->query("UPDATE admins SET active=1-active WHERE id=$id");
  header('Location: users.php'); exit;
}
if(isset($_GET['del'])){
  $id=(int)$_GET['del']; $c=$conn->query("SELECT COUNT(*) c FROM admins WHERE role='owner' AND active=1")->fetch_assoc()['c'];
  $t=$conn->query("SELECT * FROM admins WHERE id=$id")->fetch_assoc();
  if($t&&!($t['role']==='owner'&&$c<=1)) $conn->query("DELETE FROM admins WHERE id=$id");
  header('Location: users.php'); exit;
}
$q=trim($_GET['q']??'');
if($q!==''){ $st=$conn->prepare("SELECT id,username,role,active,created_at FROM admins WHERE username LIKE ? ORDER BY id"); $like="%$q%"; $st->bind_param('s',$like); $st->execute(); $users=$st->get_result()->fetch_all(MYSQLI_ASSOC); }
else $users=$conn->query("SELECT id,username,role,active,created_at FROM admins ORDER BY id")->fetch_all(MYSQLI_ASSOC);
admin_shell_open('Users','users');
?>
<?php if($msg): ?><div class="alert alert-info"><?php echo esc($msg); ?></div><?php endif; ?>
<div class="ad-card"><h5 class="serif">Create staff / admin (owner only, no public signup)</h5>
<form method="post" class="row g-2">
<input type="hidden" name="new_user" value="1">
<div class="col-md-4"><input name="username" class="form-control" placeholder="Username" required></div>
<div class="col-md-3"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
<div class="col-md-2"><select name="role" class="form-select"><option value="staff">staff</option><option value="owner">owner</option></select></div>
<div class="col-md-3"><button class="btn btn-ocean w-100">Create account</button></div>
</form></div>
<div class="ad-card"><form method="get" class="row g-2 mb-2">
<div class="col-md-9"><input name="q" class="form-control" placeholder="Search username" value="<?php echo esc($q); ?>"></div>
<div class="col-md-3"><button class="btn btn-ocean w-100">Search</button></div>
</form>
<div class="table-responsive"><table class="table table-bordered align-middle">
<tr><th>User</th><th>Role</th><th>Active</th><th>Created</th><th>Actions</th></tr>
<?php if(!$users): ?><tr><td colspan="5" class="text-muted">No users match.</td></tr><?php endif; ?>
<?php foreach($users as $x): ?><tr>
<td><b><?php echo esc($x['username']); ?></b></td><td><span class="badge <?php echo $x['role']==='owner'?'bg-primary':'bg-secondary'; ?>"><?php echo esc($x['role']); ?></span></td><td><?php echo $x['active']?'Yes':'No'; ?></td><td><?php echo esc($x['created_at']); ?></td>
<td><a class="btn btn-sm btn-secondary" onclick="return askConfirm('Toggle active for <?php echo esc($x['username']); ?>?','?toggle=<?php echo $x['id']; ?>')">On/Off</a>
<a class="btn btn-sm btn-danger" onclick="return askConfirm('Delete <?php echo esc($x['username']); ?>? Last active owner is protected.','?del=<?php echo $x['id']; ?>')">Delete</a></td>
</tr><?php endforeach; ?>
</table></div></div>
<?php admin_shell_close(); ?>
