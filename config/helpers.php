<?php
// Shared helpers: availability, pricing, formatting, booking state machine
// v0.12 "Instant Reservation": a booking reserves its dates the moment it is
// created, so 'Confirmed' now BLOCKS inventory (previously only Approved/CheckedIn
// blocked, which let two guests pay for the same night before admin approval).
const RESERVATION_FEE = 1000.00;
const CHECK_IN_TIME  = '2:00 PM';
const CHECK_OUT_TIME = '11:00 AM';
// Statuses that hold inventory. MUST be rendered as a quoted SQL list
// ('Confirmed','CheckedIn') -- never as one quoted string ('Confirmed,CheckedIn'),
// which is a single value that matches no row and silently disables the guard.
const BLOCKING_STATUSES = ['Confirmed', 'CheckedIn'];
function blocking_status_sql() { return "'" . implode("','", BLOCKING_STATUSES) . "'"; }

function nights_between($in, $out) {
    return (int) ((strtotime($out) - strtotime($in)) / 86400);
}
function peso($n) {
    return '₱' . number_format((float)$n, 2);
}
// Convenience wrapper so every page uses the same money math.
function peso_compact($n) {
    $n = (float)$n;
    if ($n >= 1000) return '₱' . rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k';
    return '₱' . number_format($n);
}
function is_room_available($conn, $room_id, $check_in, $check_out, $exclude_id = 0) {
    // Overlap rule: existing.check_in < new.check_out AND existing.check_out > new.check_in
    // Confirmed / CheckedIn block inventory. A confirmed booking already paid, so
    // the hold is what stops a second guest from paying for the same night.
    $sql = "SELECT COUNT(*) c FROM bookings WHERE room_id=? AND booking_status IN (" . blocking_status_sql() . ")
            AND check_in < ? AND check_out > ? AND id != ?";
    $st = $conn->prepare($sql);
    $st->bind_param('issi', $room_id, $check_out, $check_in, $exclude_id);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    return ((int)$r['c'] === 0);
}
function room_booked_dates($conn, $room_id) {
    $dates = [];
    $st = $conn->prepare("SELECT check_in, check_out FROM bookings WHERE room_id=? AND booking_status IN (" . blocking_status_sql() . ")");
    $st->bind_param('i', $room_id);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) { $dates[] = $row; }
    return $dates;
}
// ---- Concurrency: serialise check-then-insert for a single room ------------
// A plain SELECT-then-INSERT lets two simultaneous requests both pass the
// overlap check before either row exists (double booking). Locking the room row
// with FOR UPDATE inside a transaction makes the second request wait, then see
// the first one's row and correctly reject.
function reserve_booking($conn, $room_id, $check_in, $check_out, $exclude_id = 0) {
    $conn->begin_transaction();
    // Lock the room row for the duration of the transaction.
    $lock = $conn->prepare('SELECT id FROM rooms WHERE id=? FOR UPDATE');
    $lock->bind_param('i', $room_id);
    $lock->execute();
    $lock->get_result()->fetch_assoc();
    $ok = is_room_available($conn, $room_id, $check_in, $check_out, $exclude_id);
    if (!$ok) { $conn->rollback(); return false; }
    return $ok; // caller must commit after inserting, or rollback()
}
function commit_or_rollback($conn, $ok) {
    if ($ok) { $conn->commit(); } else { $conn->rollback(); }
}
function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
// Flash toast (survives one redirect, rendered by admin shell as a toast card)
function flash($msg, $cat='success') {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $_SESSION['flash'] = ['msg'=>$msg, 'cat'=>$cat];
}
function flash_html() {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['flash'])) return '';
$f = $_SESSION['flash']; unset($_SESSION['flash']);
  $cat = in_array($f['cat'],['success','error','danger','info']) ? $f['cat'] : 'info';
  return '<div class="toast-card toast-'.$cat.'">'
    . '<span class="toast-message">'.esc($f['msg']).'</span>'
    . '<button type="button" class="toast-close" onclick="this.parentNode.remove()">✕</button></div>';
}
function status_badge($s) {
    $map = ['Confirmed'=>'primary','Declined'=>'danger','Completed'=>'success','CheckedIn'=>'info','CheckedOut'=>'dark',
            'Unpaid'=>'secondary','Part Paid'=>'warning','Fully Paid'=>'success'];
    $c = $map[$s] ?? 'secondary';
    return '<span class="badge bg-'.$c.'">'.esc($s).'</span>';
}
// Refund preview per the T&C: the ₱1,000 reservation fee is never refunded.
function refundable_on_cancel($total, $paid) {
    return max(0, (float)$paid - RESERVATION_FEE);
}