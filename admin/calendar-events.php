<?php session_start();
if(!isset($_SESSION['admin'])){http_response_code(403);exit;}
require '../config/db.php';
$res=$conn->query("SELECT b.id,b.guest_name,r.room_name,b.check_in,b.check_out,b.booking_status FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.booking_status NOT IN ('Declined')");
$out=[]; $colors=['Pending'=>'#6c757d','Approved'=>'#0d6efd','CheckedIn'=>'#0dcaf0','CheckedOut'=>'#212529','Completed'=>'#198754'];
foreach($res as $b){$out[]= ['title'=>"#$b[id] $b[room_name] - $b[guest_name]",'start'=>$b['check_in'],'end'=>$b['check_out'],'color'=>$colors[$b['booking_status']]??'#0A4A7A'];}
header('Content-Type: application/json'); echo json_encode($out);
