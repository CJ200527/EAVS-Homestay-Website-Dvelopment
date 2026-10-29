<?php require 'auth.php'; require '../config/db.php'; require '../config/helpers.php'; require 'layout.php';
$msg=''; $edit=null;
function room_cover_upload($file, &$err) {
  if(empty($file['name'])){ $err='Cover photo is required.'; return null; }
  $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
  if(!in_array($ext,['jpg','jpeg','png','webp'])){ $err='Photo must be jpg/png/webp.'; return null; }
  if(($file['size']??0)>2*1024*1024){ $err='Photo max 2MB.'; return null; }
  $dir=__DIR__.'/../assets/images/rooms/'; if(!is_dir($dir))mkdir($dir,0755,true);
  $name='room_'.time().'_'.preg_replace('/[^a-z0-9._-]/i','',basename($file['name']));
  if(!move_uploaded_file($file['tmp_name'],$dir.$name)){ $err='Failed to save photo.'; return null; }
  return 'assets/images/rooms/'.$name;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim($_POST['room_name']); $color=trim($_POST['color']); $cap=(int)$_POST['capacity'];
  $price=(float)$_POST['price']; $desc=trim($_POST['description']);
  if(strlen($name)<2)$msg='Room name required.';
  else{
    if(!empty($_POST['id'])){ $id=(int)$_POST['id'];
      $old=$conn->query("SELECT image_urls FROM rooms WHERE id=$id")->fetch_assoc();
      $img=$old['image_urls']??'';
      if(!empty($_FILES['cover']['name'])){ $img=room_cover_upload($_FILES['cover'],$msg)??$img; }
      if(!$msg){
        $st=$conn->prepare("UPDATE rooms SET room_name=?,color=?,capacity=?,price_per_night=?,description=?,image_urls=? WHERE id=?");
        $st->bind_param('ssidssi',$name,$color,$cap,$price,$desc,$img,$id); $st->execute(); flash('Room updated.');
        header('Location: rooms.php'); exit;
      }
    } else {
      $img=room_cover_upload($_FILES['cover']??[],$msg);
      if($img){
        $st=$conn->prepare("INSERT INTO rooms (room_name,color,capacity,price_per_night,description,image_urls,status) VALUES (?,?,?,?,?,?,'pending')");
        $st->bind_param('ssidss',$name,$color,$cap,$price,$desc,$img); $st->execute(); flash('Room submitted for approval.');
        header('Location: rooms.php'); exit;
      }
    }
  }
}
if(isset($_GET['edit'])){ $id=(int)$_GET['edit'];
  $st=$conn->prepare("SELECT * FROM rooms WHERE id=?"); $st->bind_param('i',$id); $st->execute();
  $edit=$st->get_result()->fetch_assoc();
}
if(isset($_GET['approve'])){ $id=(int)$_GET['approve'];
  $conn->query("UPDATE rooms SET status='active' WHERE id=$id"); flash('Room approved — now live on the site.'); header('Location: rooms.php'); exit;
}
if(isset($_GET['deactivate'])){ $id=(int)$_GET['deactivate'];
  $conn->query("UPDATE rooms SET status='pending' WHERE id=$id"); flash('Room hidden from the site.'); header('Location: rooms.php'); exit;
}
if(isset($_GET['del'])){ $id=(int)$_GET['del'];
  $c=$conn->query("SELECT COUNT(*) c FROM bookings WHERE room_id=$id AND booking_status IN ('Confirmed','CheckedIn')")->fetch_assoc()['c'];
  if($c>0) $msg="Cannot delete: $c active/pending booking(s) use this room.";
  else { $conn->query("DELETE FROM rooms WHERE id=$id"); flash('Room deleted.'); header('Location: rooms.php'); exit; }
}
// Search (name/color) + filter (created range + status)
$q=trim($_GET['q']??''); $fs=trim($_GET['fstart']??''); $fe=trim($_GET['fend']??''); $fst=$_GET['fstatus']??'all';
if(!in_array($fst,['all','pending','active']))$fst='all';
$where=['1']; $types=''; $params=[];
if($q!==''){$where[]="(room_name LIKE ? OR color LIKE ?)";$types.='ss';$params[]="%$q%";$params[]="%$q%";}
if($fs!==''&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$fs)){$where[]="DATE(created_at)>=?";$types.='s';$params[]=$fs;}
if($fe!==''&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$fe)){$where[]="DATE(created_at)<=?";$types.='s';$params[]=$fe;}
if($fst!=='all'){$where[]="status=?";$types.='s';$params[]=$fst;}
$st=$conn->prepare("SELECT r.*, (SELECT COUNT(*) FROM bookings b WHERE b.room_id=r.id AND b.booking_status IN ('Confirmed','CheckedIn')) AS active_n FROM rooms r WHERE ".implode(' AND ',$where)." ORDER BY id");
if($types)$st->bind_param($types,...$params);
$st->execute(); $rooms=$st->get_result()->fetch_all(MYSQLI_ASSOC);
// Room KPIs (global inventory — ignore search/filter, they describe the property)
$kUnits=(int)$conn->query("SELECT COUNT(*) c FROM rooms")->fetch_assoc()['c'];
$kLive=(int)$conn->query("SELECT COUNT(*) c FROM rooms WHERE status='active'")->fetch_assoc()['c'];
$kPendAppr=$kUnits-$kLive;
$kToday=date('Y-m-d'); $kTomorrow=date('Y-m-d',strtotime('+1 day'));
$kBusy=$conn->query("SELECT DISTINCT room_id FROM bookings WHERE booking_status IN ('Confirmed','CheckedIn') AND check_in<'$kTomorrow' AND check_out>'$kToday'")->fetch_all(MYSQLI_ASSOC);
$kBusyIds=array_map(fn($x)=>(int)$x['room_id'],$kBusy);
$kRoomsAll=$conn->query("SELECT id,room_name FROM rooms ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$kFreeNames=[]; foreach($kRoomsAll as $kr){ if(!in_array((int)$kr['id'],$kBusyIds))$kFreeNames[]=$kr['room_name']; }
$kFree=count($kFreeNames);
$kActRows=$conn->query("SELECT booking_status,COUNT(*) c FROM bookings WHERE booking_status IN ('Confirmed','CheckedIn') AND check_out>='$kToday' GROUP BY booking_status")->fetch_all(MYSQLI_ASSOC);
$kActive=0; $kActIn=0; $kActAp=0;
foreach($kActRows as $a){ $kActive+=(int)$a['c']; if($a['booking_status']==='CheckedIn')$kActIn=(int)$a['c']; if($a['booking_status']==='Confirmed')$kActAp=(int)$a['c']; }
$kStayRows=$conn->query("SELECT room_id,COUNT(*) c FROM bookings WHERE booking_status IN ('Confirmed','CheckedIn','CheckedOut','Completed') GROUP BY room_id")->fetch_all(MYSQLI_ASSOC);
$kStays=[]; foreach($kRoomsAll as $kr)$kStays[$kr['id']]=0;
foreach($kStayRows as $sr){ $kStays[$sr['room_id']]=(int)$sr['c']; }
$kStayTot=array_sum($kStays);
$kStayParts=[]; foreach($kRoomsAll as $kr){ $short=explode(' - ',$kr['room_name'])[0]; $kStayParts[]=esc($short).' · '.($kStays[$kr['id']]??0); }
admin_shell_open('Rooms','rooms',false,true,'ocean');
admin_strip('Rooms','Check, add and edit units', '');
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3"><div class="kpi-dash glass has-link">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#i-bed"/></svg></span><span class="delta delta-abs"><?php echo $kLive; ?> live</span></div>
    <div class="lbl">Total Units</div><div class="big"><?php echo $kUnits; ?></div>
    <div class="kpi-sub"><span class="sub"><?php echo $kLive; ?> live · <?php echo $kPendAppr; ?> awaiting approval</span><span class="kpi-more">View all →</span></div>
    <a class="stretched-link" href="rooms.php" aria-label="View all rooms"></a>
  </div></div>
  <div class="col-6 col-xl-3"><div class="kpi-dash glass">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#k-key"/></svg></span><span class="delta delta-live">● tonight</span></div>
    <div class="lbl">Free Tonight</div><div class="big"><?php echo $kFree; ?></div>
    <div class="kpi-sub"><span class="sub"><?php echo $kFreeNames ? esc(implode(' · ',$kFreeNames)).' free' : 'Fully booked tonight'; ?></span><a class="kpi-more" href="dashboard.php">Timeline →</a></div>
  </div></div>
  <div class="col-6 col-xl-3"><div class="kpi-dash glass">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#i-book"/></svg></span><span class="delta delta-abs">on the books</span></div>
    <div class="lbl">Active Stays</div><div class="big"><?php echo $kActive; ?></div>
    <div class="kpi-sub"><span class="sub"><?php echo $kActIn; ?> checked-in · <?php echo $kActAp; ?> approved</span><a class="kpi-more" href="bookings.php">Open →</a></div>
  </div></div>
  <div class="col-6 col-xl-3"><div class="kpi-dash glass">
    <div class="top"><span class="kpi-ic"><svg class="act-icon"><use href="#k-bed"/></svg></span><span class="delta delta-abs" title="Non-declined stays per unit, all time">all time</span></div>
    <div class="lbl">Total Stays</div><div class="big"><?php echo $kStayTot; ?></div>
    <div class="kpi-sub"><span class="sub"><?php echo implode(' | ',$kStayParts); ?></span></div>
  </div></div>
</div>
<?php if($msg): ?><div class="alert alert-info"><?php echo esc($msg); ?></div><?php endif; ?>
<div class="ad-card glass tool-card"><div class="toolbelt">
<form method="get" data-toast-form class="tool-left">
<input name="q" class="form-control" placeholder="Search name / color" value="<?php echo esc($q); ?>">
<?php foreach(['fstart'=>$fs,'fend'=>$fe,'fstatus'=>$fst] as $k=>$v): ?><input type="hidden" name="<?php echo $k; ?>" value="<?php echo esc($v); ?>"><?php endforeach; ?>
<button class="icon-btn" title="Search"><svg class="nav-ic"><use href="#i-search"/></svg></button>
<a href="rooms.php" title="Refresh" class="icon-btn"><svg class="nav-ic"><use href="#i-refresh"/></svg></a>
</form>
<div class="tool-right">
<div class="filter-wrap">
<button type="button" class="icon-btn filter-btn" aria-expanded="false" title="Filter"><svg class="nav-ic"><use href="#i-filter"/></svg></button>
<div class="filter-panel"><form method="get" class="row g-2">
<input type="hidden" name="q" value="<?php echo esc($q); ?>">
<div class="col-6"><label class="form-label">Start date</label><input type="date" name="fstart" class="form-control" value="<?php echo esc($fs); ?>"></div>
<div class="col-6"><label class="form-label">End date</label><input type="date" name="fend" class="form-control" value="<?php echo esc($fe); ?>"></div>
<div class="col-12"><label class="form-label">Status</label><select name="fstatus" class="form-select">
<option value="all">All</option><option value="pending" <?php echo $fst==='pending'?'selected':''; ?>>Pending approval</option><option value="active" <?php echo $fst==='active'?'selected':''; ?>>Active (live)</option></select></div>
<div class="col-6"><button class="btn btn-ocean w-100">Apply</button></div>
<div class="col-6"><a href="rooms.php" class="btn btn-secondary w-100">Reset</a></div>
</form></div>
</div>
<button class="btn btn-ocean" data-bs-toggle="modal" data-bs-target="#newRoom"><svg class="nav-ic"><use href="#i-plus"/></svg> New room</button>
</div>
</div></div>
<!-- Add room modal -->
<div class="modal fade" id="newRoom" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title serif">Add room</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="post" enctype="multipart/form-data"><div class="modal-body row g-2">
<div class="col-md-6"><label class="form-label">Room name</label><input name="room_name" class="form-control" required placeholder="Room 3 - Green Unit"></div>
<div class="col-md-3"><label class="form-label">Color</label><input name="color" class="form-control" placeholder="Green"></div>
<div class="col-md-3"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" min="1" max="20" value="6"></div>
<div class="col-md-3"><label class="form-label">Price/night</label><input type="number" step="0.01" name="price" class="form-control" value="3000"></div>
<div class="col-md-9"><label class="form-label">Description</label><input name="description" class="form-control"></div>
<div class="col-12"><label class="form-label">Cover photo (required, jpg/png/webp ≤2MB)</label><input type="file" name="cover" accept="image/*" class="form-control" required></div>
</div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-ocean">Submit for approval</button></div></form>
</div></div></div>
<?php if($edit): ?>
<div class="ad-card glass"><h5 class="serif">Edit room #<?php echo $edit['id']; ?></h5>
<form method="post" enctype="multipart/form-data" class="row g-2">
<input type="hidden" name="id" value="<?php echo $edit['id']; ?>">
<div class="col-md-4"><label class="form-label">Room name</label><input name="room_name" class="form-control" required value="<?php echo esc($edit['room_name']); ?>"></div>
<div class="col-md-2"><label class="form-label">Color</label><input name="color" class="form-control" value="<?php echo esc($edit['color']); ?>"></div>
<div class="col-md-2"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" min="1" max="20" value="<?php echo esc($edit['capacity']); ?>"></div>
<div class="col-md-2"><label class="form-label">Price/night</label><input type="number" step="0.01" name="price" class="form-control" value="<?php echo esc($edit['price_per_night']); ?>"></div>
<div class="col-md-8"><label class="form-label">Description</label><input name="description" class="form-control" value="<?php echo esc($edit['description']); ?>"></div>
<div class="col-md-4"><label class="form-label">Replace cover (optional)</label><input type="file" name="cover" accept="image/*" class="form-control"></div>
<div class="col-12"><button class="btn btn-ocean">Save changes</button>
<a href="rooms.php" class="btn btn-secondary">Cancel</a></div>
</form></div>
<?php endif; ?>
<div class="ad-card glass book-table"><div class="table-responsive"><table class="table table-sm align-middle book-table">
<thead><tr><th>#</th><th>Cover</th><th>Room</th><th>Cap / Price</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($rooms as $r): ?><tr>
<td class="text-muted">#<?php echo $r['id']; ?></td>
<td><a href="../<?php echo esc($r['image_urls']); ?>" target="_blank" class="proof-thumb"><img src="../<?php echo esc($r['image_urls']); ?>" alt="cover"></a></td>
<td><b><?php echo esc($r['room_name']); ?></b> (<?php echo esc($r['color']); ?>)<br><small class="text-muted"><?php echo esc(substr($r['description'],0,120)); ?></small></td>
<td><?php echo (int)$r['capacity']; ?> guests<br><b><?php echo peso($r['price_per_night']); ?></b>/night</td>
<td><span class="nights-chip"><?php echo (int)$r['active_n']; ?> active</span><br><?php echo ($r['status']??'pending')==='active'?'<span class="badge bg-success">Active</span>':'<span class="badge bg-warning text-dark">Pending</span>'; ?></td>
<td><div class="act-row nowrap">
<?php if(($r['status']??'pending')==='active'): ?>
<a class="btn-act a-warn" title="Hide from site (deactivate)" onclick="return askConfirm('Hide <?php echo esc($r['room_name']); ?> from the site?','?deactivate=<?php echo $r['id']; ?>')"><svg class="act-icon"><use href="#a-undo"/></svg></a>
<?php else: ?>
<a class="btn-act a-ok" title="Approve — show on site" onclick="return askConfirm('Approve <?php echo esc($r['room_name']); ?>? It will appear on the landing page.','?approve=<?php echo $r['id']; ?>')"><svg class="act-icon"><use href="#a-up"/></svg></a>
<?php endif; ?>
<a class="btn-act a-edit" title="Edit room" href="?edit=<?php echo $r['id']; ?>"><svg class="act-icon"><use href="#a-edit"/></svg></a>
<a class="btn-act a-bad" title="Delete room" onclick="return askConfirm('Delete <?php echo esc($r['room_name']); ?>? Blocked if active bookings exist.','?del=<?php echo $r['id']; ?>')"><svg class="act-icon"><use href="#a-trash"/></svg></a>
<a class="btn-act a-view" title="View guest page" href="../room-details.php?id=<?php echo $r['id']; ?>" target="_blank"><svg class="act-icon"><use href="#a-view"/></svg></a></div></td>
</tr><?php endforeach; ?>
</tbody>
</table></div></div>
<?php admin_shell_close(); ?>
