<?php require 'config/db.php'; require 'config/helpers.php';
$id=(int)($_GET['id']??0);
$st=$conn->prepare("SELECT b.*, r.room_name FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=?");
$st->bind_param('i',$id);$st->execute();$b=$st->get_result()->fetch_assoc();
require 'includes/header.php';
if(!$b){echo "<div class='alert alert-danger'>Booking not found.</div>"; require 'includes/footer.php'; exit;}
?>
<div class="alert alert-success"><h4>Booking Received! 🎉</h4>Reference #<?php echo $b['id']; ?> — Status: Pending approval. We will contact you at <?php echo esc($b['phone']); ?> / <?php echo esc($b['email']); ?>.</div>
<div class="card"><div class="card-body">
<p><b>Guest:</b> <?php echo esc($b['guest_name']); ?> (<?php echo (int)$b['num_guests']; ?> guests)</p>
<p><b>Room:</b> <?php echo esc($b['room_name']); ?></p>
<p><b>Dates:</b> <?php echo esc($b['check_in']); ?> → <?php echo esc($b['check_out']); ?> (<?php echo nights_between($b['check_in'],$b['check_out']); ?> nights)</p>
<p><b>Total:</b> <?php echo peso($b['total_amount']); ?> • <?php echo esc($b['payment_type']); ?> payment via <?php echo esc($b['payment_method']); ?></p>
<p><b>Payment status:</b> <?php echo status_badge($b['payment_status']); ?> <b>Booking:</b> <?php echo status_badge($b['booking_status']); ?></p>
</div></div>
<a href="index.php" class="btn btn-ocean mt-3">Back to Home</a>
<?php require 'includes/footer.php'; ?>
