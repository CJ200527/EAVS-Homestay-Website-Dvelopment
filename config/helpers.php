<?php
// Shared helpers: availability, pricing, formatting
function nights_between($in, $out) {
    return (int) ((strtotime($out) - strtotime($in)) / 86400);
}
function peso($n) {
    return '₱' . number_format((float)$n, 2);
}
function is_room_available($conn, $room_id, $check_in, $check_out, $exclude_id = 0) {
    // Overlap rule: existing.check_in < new.check_out AND existing.check_out > new.check_in
    // Only Approved / CheckedIn block inventory. Pending holds do NOT block until approved.
    $sql = "SELECT COUNT(*) c FROM bookings WHERE room_id=? AND booking_status IN ('Approved','CheckedIn')
            AND check_in < ? AND check_out > ? AND id != ?";
    $st = $conn->prepare($sql);
    $st->bind_param('issi', $room_id, $check_out, $check_in, $exclude_id);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    return ((int)$r['c'] === 0);
}
function room_booked_dates($conn, $room_id) {
    $dates = [];
    $st = $conn->prepare("SELECT check_in, check_out FROM bookings WHERE room_id=? AND booking_status IN ('Approved','CheckedIn')");
    $st->bind_param('i', $room_id);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) { $dates[] = $row; }
    return $dates;
}
function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function status_badge($s) {
    $map = ['Pending'=>'secondary','Approved'=>'primary','Declined'=>'danger','Completed'=>'success','CheckedIn'=>'info','CheckedOut'=>'dark',
            'Advance Paid'=>'warning','Fully Paid'=>'success'];
    $c = $map[$s] ?? 'secondary';
    return '<span class="badge bg-'.$c.'">'.esc($s).'</span>';
}
