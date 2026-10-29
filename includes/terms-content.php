<?php
// Shared Terms & Conditions body — SINGLE SOURCE OF TRUTH.
// Included by:
//   terms.php            (standalone / printable page)
//   booking.php          (Terms modal, so the guest never leaves the form)
//   booking-success.php  (optional)
// Keep the wording here only; edit this file to change the terms everywhere.
// Guard: only load helpers when this partial is reached without them, so a page
// that already did `require 'config/helpers.php'` never redeclares functions.
if (!defined('CHECK_IN_TIME')) { require __DIR__ . '/../config/helpers.php'; }
?>
<div class="alert alert-warning small">
  <strong>Check-in <?php echo CHECK_IN_TIME; ?> · Check-out by <?php echo CHECK_OUT_TIME; ?>.</strong>
  Please read these terms before booking. Your booking is accepted when you submit the form and agree to these terms.
</div>

<h3 class="serif">1. Booking &amp; Confirmation</h3>
<p>Your booking is <strong>confirmed the moment you submit the booking form</strong>, and your selected dates are reserved exclusively for you. There is no approval wait. No other guest can book the same room for overlapping dates while your booking stands. To release your dates, contact the homestay so your reservation can be cancelled.</p>

<h3 class="serif">2. Payment</h3>
<ul>
  <li>Room rate is <strong>₱3,000 per night</strong>, per unit.</li>
  <li>A <strong>non-refundable reservation fee of ₱<?php echo number_format(RESERVATION_FEE, 0); ?></strong> secures your booking and is deducted from your total.</li>
  <li>The remaining balance is payable on arrival in <strong>Cash</strong>, or you may settle by <strong>GCash</strong> or <strong>Maya</strong>.</li>
  <li>You may instead pay the <strong>full amount</strong> at the time of booking.</li>
  <li><strong>Proof of payment is required for every payment method, cash included.</strong> Upload a photo or screenshot of the payment when you book — even for a face-to-face cash handover — so there is a clear record of what was paid. jpg/png/webp, under 2MB.</li>
  <li>If you are already at the homestay and cannot upload anything, the homestay can create the booking on your behalf and attach the proof for you. Just ask.</li>
  <li>Your booking is treated as settled once the homestay has verified payment in full.</li>
</ul>

<h3 class="serif">3. Cancellation &amp; Refund</h3>
<p>The <strong>₱<?php echo number_format(RESERVATION_FEE, 0); ?> reservation fee is non-refundable</strong>. Only amounts paid <em>above</em> the reservation fee are refundable:</p>
<div class="table-responsive">
  <table class="table table-sm spec-table">
    <thead><tr><th>Amount you paid</th><th>Refundable on cancellation</th></tr></thead>
    <tbody>
      <tr><td>₱<?php echo number_format(RESERVATION_FEE, 0); ?> (reservation only)</td><td><strong>₱0</strong> — the fee itself</td></tr>
      <tr><td>Full amount (e.g. ₱6,000 for 2 nights)</td><td><strong>₱<?php echo number_format(6000 - RESERVATION_FEE, 0); ?></strong> — everything above the fee</td></tr>
    </tbody>
  </table>
</div>
<p class="text-muted small">Refunds are returned to the same payment method used, within 7 days of the cancellation being approved.</p>

<h3 class="serif">4. Check-in &amp; Check-out</h3>
<ul>
  <li><strong>Check-in: from <?php echo CHECK_IN_TIME; ?></strong></li>
  <li><strong>Check-out: by <?php echo CHECK_OUT_TIME; ?></strong></li>
  <li>Your checkout day is not charged as an extra night.</li>
  <li>Arriving before <?php echo CHECK_IN_TIME; ?> or leaving after <?php echo CHECK_OUT_TIME; ?> is subject to availability and is not guaranteed.</li>
</ul>

<h3 class="serif">5. House Rules</h3>
<ul>
  <li>Maximum <strong>6 guests per unit</strong>. Additional or unregistered guests are not permitted.</li>
  <li>The unit is <strong>strictly non-smoking</strong>.</li>
  <li>No parties or events.</li>
  <li><strong>Free Wi-Fi</strong> is provided throughout the homestay. Network details are given to you at check-in.</li>
  <li>Guests are responsible for any damage they cause. Damaged items will be assessed and the repair or replacement cost <strong>discussed between the guest and the owner</strong> and settled directly.</li>
  <li>Please leave the unit reasonably tidy; kitchen and dining areas must be left clean.</li>
  <li>Rooms may be inspected on check-out.</li>
</ul>

<h3 class="serif">6. Included in Your Stay</h3>
<p>Room 1 includes a <strong>free 1-day scooter rental</strong> as part of the base package. Licence requirements, fuel, return time and damage terms are confirmed with the homestay at booking.</p>

<h3 class="serif">7. If the Homestay Cancels</h3>
<p>If the homestay cancels your booking for any reason, <strong>any amount you have paid is refunded in full</strong>.</p>

<h3 class="serif">8. Privacy</h3>
<p>To administer your stay we collect only what is required: <strong>your name, mobile number, number of guests, selected room and dates, and any payment screenshot you upload.</strong> We do <strong>not</strong> collect or store your email address. Your information is used solely to process and administer your booking and is not shared with third parties, except where required by law.</p>

<h3 class="serif">9. Liability</h3>
<p>The homestay is not liable for loss of personal property, or for interruptions to utilities or services beyond our reasonable control.</p>

<h3 class="serif">10. Contact</h3>
<p>EAV's Homestay · Catohugan, Mahinog, Camiguin 9101 · <a href="tel:+639664197812">+63 966 419 7812</a></p>