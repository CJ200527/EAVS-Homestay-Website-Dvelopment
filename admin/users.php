<?php require 'auth.php'; require_owner(); require '../config/db.php'; require '../config/helpers.php'; require 'layout.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['new_user'])){
  $u=trim($_POST['username']); $p=$_POST['password']; $r=$_POST['role']==='owner'?'owner':'staff';
  if(strlen($u)<3)$msg='Username min 3 chars.';
  elseif(strlen($p)<3)$msg='Password min 3 chars.';
  else{ $h=password_hash($p,PASSWORD_DEFAULT);
    $st=$conn->prepare("INSERT INTO admins (username,password_hash,role,active) VALUES (?,?,?,1)");
    $st->bind_param('sss',$u,$h,$r);
    $msg=$st->execute()?'Staff account created.':'Username already exists.';
    if(strpos($msg,'created')!==false){ flash($msg); header('Location: users.php'); exit; } }
}
if(isset($_GET['toggle'])){
  $id=(int)$_GET['toggle'];
  if($id!==($_SESSION['admin_id']??-1)) $conn->query("UPDATE admins SET active=1-active WHERE id=$id");
  flash('Account updated.'); header('Location: users.php'); exit;
}
if(isset($_GET['del'])){
  $id=(int)$_GET['del']; $c=$conn->query("SELECT COUNT(*) c FROM admins WHERE role='owner' AND active=1")->fetch_assoc()['c'];
  $t=$conn->query("SELECT * FROM admins WHERE id=$id")->fetch_assoc();
  if($t&&!($t['role']==='owner'&&$c<=1)) $conn->query("DELETE FROM admins WHERE id=$id");
  flash('Account deleted.'); header('Location: users.php'); exit;
}
$q=trim($_GET['q']??''); $fs=trim($_GET['fstart']??''); $fe=trim($_GET['fend']??'');
$fa=$_GET['factive']??'all'; $fr=$_GET['frole']??'all';
if(!in_array($fa,['all','1','0']))$fa='all'; if(!in_array($fr,['all','owner','staff']))$fr='all';
$where=['1']; $types=''; $params=[];
if($q!==''){$where[]="username LIKE ?";$types.='s';$params[]="%$q%";}
if($fs!==''&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$fs)){$where[]="DATE(created_at)>=?";$types.='s';$params[]=$fs;}
if($fe!==''&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$fe)){$where[]="DATE(created_at)<=?";$types.='s';$params[]=$fe;}
if($fa!=='all'){$where[]="active=?";$types.='i';$params[]=(int)$fa;}
if($fr!=='all'){$where[]="role=?";$types.='s';$params[]=$fr;}
$st=$conn->prepare("SELECT id,username,role,active,created_at FROM admins WHERE ".implode(' AND ',$where)." ORDER BY id");
if($types)$st->bind_param($types,...$params);
$st->execute(); $users=$st->get_result()->fetch_all(MYSQLI_ASSOC);
// User KPIs (global accounts — ignore search/filter; every card links to its exact slice)
$uTot=(int)$conn->query("SELECT COUNT(*) c FROM admins")->fetch_assoc()['c'];
$uAct=(int)$conn->query("SELECT COUNT(*) c FROM admins WHERE active=1")->fetch_assoc()['c'];
$uOwn=(int)$conn->query("SELECT COUNT(*) c FROM admins WHERE role='owner'")->fetch_assoc()['c'];
$uStaff=(int)$conn->query("SELECT COUNT(*) c FROM admins WHERE role='staff'")->fetch_assoc()['c'];
admin_shell_open('Users','users',false,true,'ocean');
admin_strip('Users','Staff and owner accounts', '');
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3"><div class="kpi-dash glass has-link">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#i-users"/></svg></span><span class="delta delta-abs">all accounts</span></div>
    <div class="lbl">Total Accounts</div><div class="big"><?php echo $uTot; ?></div>
    <div class="kpi-sub"><span class="sub"><?php echo $uOwn; ?> owner · <?php echo $uStaff; ?> staff</span><span class="kpi-more">View all →</span></div>
    <a class="stretched-link" href="users.php" aria-label="View all accounts"></a>
  </div></div>
  <div class="col-6 col-xl-3"><div class="kpi-dash glass has-link">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#i-check"/></svg></span><span class="delta delta-live">● can log in</span></div>
    <div class="lbl">Active Accounts</div><div class="big"><?php echo $uAct; ?></div>
    <div class="kpi-sub"><span class="sub"><?php echo $uTot-$uAct; ?> deactivated</span><span class="kpi-more">View →</span></div>
    <a class="stretched-link" href="users.php?factive=1" aria-label="View active accounts"></a>
  </div></div>
  <div class="col-6 col-xl-3"><div class="kpi-dash glass has-link">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#k-key"/></svg></span><span class="delta delta-abs">protected</span></div>
    <div class="lbl">Owners</div><div class="big"><?php echo $uOwn; ?></div>
    <div class="kpi-sub"><span class="sub">last active owner kept</span><span class="kpi-more">View →</span></div>
    <a class="stretched-link" href="users.php?frole=owner" aria-label="View owner accounts"></a>
  </div></div>
  <div class="col-6 col-xl-3"><div class="kpi-dash glass has-link">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#i-users"/></svg></span><span class="delta delta-abs"><?php echo $uStaff>0?'team':'empty'; ?></span></div>
    <div class="lbl">Staff</div><div class="big"><?php echo $uStaff; ?></div>
    <div class="kpi-sub"><span class="sub"><?php echo $uStaff>0?'login-enabled team':'no staff yet — create one'; ?></span><span class="kpi-more">View →</span></div>
    <a class="stretched-link" href="users.php?frole=staff" aria-label="View staff accounts"></a>
  </div></div>
</div>
<?php if($msg): ?><div class="alert alert-info"><?php echo esc($msg); ?></div><?php endif; ?>
<div class="ad-card glass tool-card"><div class="toolbelt">
<form method="get" data-toast-form class="tool-left">
<input name="q" class="form-control" placeholder="Search username" value="<?php echo esc($q); ?>">
<?php foreach(['fstart'=>$fs,'fend'=>$fe,'factive'=>$fa,'frole'=>$fr] as $k=>$v): ?><input type="hidden" name="<?php echo $k; ?>" value="<?php echo esc($v); ?>"><?php endforeach; ?>
<button class="icon-btn" title="Search"><svg class="nav-ic"><use href="#i-search"/></svg></button>
<a href="users.php" title="Refresh" class="icon-btn"><svg class="nav-ic"><use href="#i-refresh"/></svg></a>
</form>
<div class="tool-right">
<div class="filter-wrap">
<button type="button" class="icon-btn filter-btn" aria-expanded="false" title="Filter"><svg class="nav-ic"><use href="#i-filter"/></svg></button>
<div class="filter-panel"><form method="get" class="row g-2">
<input type="hidden" name="q" value="<?php echo esc($q); ?>">
<div class="col-6"><label class="form-label">Start date</label><input type="date" name="fstart" class="form-control" value="<?php echo esc($fs); ?>"></div>
<div class="col-6"><label class="form-label">End date</label><input type="date" name="fend" class="form-control" value="<?php echo esc($fe); ?>"></div>
<div class="col-6"><label class="form-label">Status</label><select name="factive" class="form-select">
<option value="all">All</option><option value="1" <?php echo $fa==='1'?'selected':''; ?>>Active</option><option value="0" <?php echo $fa==='0'?'selected':''; ?>>Inactive</option></select></div>
<div class="col-6"><label class="form-label">Role</label><select name="frole" class="form-select">
<option value="all">All</option><option value="owner" <?php echo $fr==='owner'?'selected':''; ?>>owner</option><option value="staff" <?php echo $fr==='staff'?'selected':''; ?>>staff</option></select></div>
<div class="col-6"><button class="btn btn-ocean w-100">Apply</button></div>
<div class="col-6"><a href="users.php" class="btn btn-secondary w-100">Reset</a></div>
</form></div>
</div>
<button class="btn btn-ocean" data-bs-toggle="modal" data-bs-target="#newUser"><svg class="nav-ic"><use href="#i-plus"/></svg> New account</button>
</div>
</div></div>
<!-- Create account modal -->
<div class="modal fade" id="newUser" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title serif">Create staff / admin</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="post"><div class="modal-body row g-2">
<input type="hidden" name="new_user" value="1">
<div class="col-md-6"><label class="form-label">Username</label><input name="username" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
<div class="col-12"><label class="form-label">Role</label><select name="role" class="form-select"><option value="staff">staff</option><option value="owner">owner</option></select></div>
</div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-ocean">Create account</button></div></form>
</div></div></div>
<div class="ad-card glass book-table"><div class="table-responsive"><table class="table table-sm align-middle book-table">
<thead><tr><th>User</th><th>Role</th><th>Active</th><th>Created</th><th>Actions</th></tr></thead>
<tbody>
<?php if(!$users): ?><tr><td colspan="5" class="text-muted">No users match.</td></tr><?php endif; ?>
<?php foreach($users as $x): ?><tr>
<td><span class="guest-cell"><span class="guest-av"><?php echo esc(strtoupper(substr($x['username'],0,1))); ?></span><span><b><?php echo esc($x['username']); ?></b></span></span></td><td><span class="badge <?php echo $x['role']==='owner'?'bg-primary':'bg-secondary'; ?>"><?php echo esc($x['role']); ?></span></td><td><?php echo $x['active']?'<span class="nights-chip">Yes</span>':'<span class="badge bg-secondary">No</span>'; ?></td><td><small class="text-muted"><?php echo esc(substr($x['created_at'],0,10)); ?></small></td>
<td><div class="act-row nowrap"><a class="btn-act a-dark" title="Toggle active on/off" onclick="return askConfirm('Toggle active for <?php echo esc($x['username']); ?>?','?toggle=<?php echo $x['id']; ?>')"><svg class="act-icon"><use href="#a-undo"/></svg></a>
<a class="btn-act a-bad" title="Delete account" onclick="return askConfirm('Delete <?php echo esc($x['username']); ?>? Last active owner is protected.','?del=<?php echo $x['id']; ?>')"><svg class="act-icon"><use href="#a-trash"/></svg></a></div></td>
</tr><?php endforeach; ?>
</tbody>
</table></div></div>
<?php admin_shell_close(); ?>
