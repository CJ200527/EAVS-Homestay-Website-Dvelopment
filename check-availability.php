<?php require 'config/db.php'; require 'config/helpers.php';
header('Content-Type: application/json');
$room_id=(int)($_GET['room_id']??0); $ci=$_GET['check_in']??''; $co=$_GET['check_out']??'';
if(!$room_id||!$ci||!$co){echo json_encode(['available'=>false,'message'=>'Select room and dates.']);exit;}
if($ci>=$co){echo json_encode(['available'=>false,'message'=>'Check-out must be after check-in.']);exit;}
$st=$conn->prepare("SELECT price_per_night FROM rooms WHERE id=?");$st->bind_param('i',$room_id);$st->execute();
$room=$st->get_result()->fetch_assoc(); if(!$room){echo json_encode(['available'=>false,'message'=>'Room not found.']);exit;}
$nights=nights_between($ci,$co); $total=$nights*(float)$room['price_per_night'];
$ok=is_room_available($conn,$room_id,$ci,$co);
echo json_encode(['available'=>$ok,'nights'=>$nights,'total'=>(float)$total,'total_formatted'=>peso($total),
 'message'=>$ok?'Available':'Already booked for these dates. Try other dates or the other room.']);
