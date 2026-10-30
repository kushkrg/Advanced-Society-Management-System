<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_login();
if (is_admin()) redirect('bookings.php');
$db = db();
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    verify_csrf('book-facility.php');
    $facilityId = (int)$_POST['facility_id'];
    $date = (string)($_POST['booking_date'] ?? '');
    $slot = trim($_POST['time_slot'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < date('Y-m-d')) {
        set_flash('error', 'Please choose a valid future date.');
    } else {
        // Conflict check: same facility + date with an approved booking
        $chk = $db->prepare("SELECT id FROM bookings WHERE facility_id = ? AND booking_date = ? AND status = 'approved'");
        $chk->execute([$facilityId, $date]);
        if ($chk->fetch()) {
            set_flash('error', 'This facility is already booked (approved) on that date. Please pick another date.');
        } else {
            $db->prepare('INSERT INTO bookings (facility_id, user_id, flat_id, booking_date, time_slot, purpose) VALUES (?, ?, ?, ?, ?, ?)')
               ->execute([$facilityId, $u['id'], $u['flat_id'] ?: null, $date, $slot ?: null, $purpose ?: null]);
            set_flash('success', 'Booking requested. The society office will approve it shortly.');
        }
    }
    redirect('book-facility.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
    verify_csrf('book-facility.php');
    $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'")
       ->execute([(int)$_POST['cancel'], $u['id']]);
    set_flash('success', 'Booking cancelled.');
    redirect('book-facility.php');
}
$facilities = $db->query('SELECT * FROM facilities WHERE is_active = 1 ORDER BY name')->fetchAll();
$mine = $db->prepare('SELECT bk.*, fac.name AS fac_name, fac.charges FROM bookings bk JOIN facilities fac ON fac.id = bk.facility_id WHERE bk.user_id = ? ORDER BY bk.id DESC LIMIT 30');
$mine->execute([$u['id']]);
$mine = $mine->fetchAll();
$preselect = (int)($_GET['facility_id'] ?? 0);
$pageTitle = 'Book Facility';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Book a Facility</h1><p>Clubhouse, hall, guest room and more — bookings are approved by the society office</p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>
<div class="card" style="margin-bottom:22px">
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="book" value="1">
    <div class="form-grid">
      <div class="field"><label>Facility</label>
        <select name="facility_id" required>
          <?php foreach ($facilities as $fac): ?><option value="<?= (int)$fac['id'] ?>" <?= $preselect === (int)$fac['id'] ? 'selected' : '' ?>><?= e($fac['name']) ?> (<?= (float)$fac['charges'] > 0 ? e(inr((float)$fac['charges'])) : 'Free' ?>)</option><?php endforeach; ?>
        </select></div>
      <div class="field"><label>Date</label><input type="date" name="booking_date" required min="<?= e(date('Y-m-d')) ?>"></div>
      <div class="field"><label>Time Slot</label><input type="text" name="time_slot" placeholder="e.g. 10 AM – 2 PM / Full Day"></div>
      <div class="field"><label>Purpose</label><input type="text" name="purpose" placeholder="e.g. Birthday party"></div>
    </div>
    <button class="btn" type="submit">Request Booking</button>
  </form>
</div>
<div class="card">
  <h2 style="font-size:1.08rem;margin-bottom:12px">My Bookings</h2>
  <div class="table-wrap"><table>
    <tr><th>Facility</th><th>Date</th><th>Time Slot</th><th>Purpose</th><th>Charges</th><th>Status</th><th>Action</th></tr>
    <?php if (!$mine): ?><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:22px">No bookings yet.</td></tr><?php endif; ?>
    <?php foreach ($mine as $bk): ?>
    <tr>
      <td><strong><?= e($bk['fac_name']) ?></strong></td>
      <td><?= e(date('D, d M Y', strtotime($bk['booking_date']))) ?></td>
      <td><?= e($bk['time_slot'] ?: '-') ?></td>
      <td><?= e($bk['purpose'] ?: '-') ?></td>
      <td><?= (float)$bk['charges'] > 0 ? e(inr((float)$bk['charges'])) : 'Free' ?></td>
      <td><span class="pill pill-<?= e($bk['status']) ?>"><?= e($bk['status']) ?></span></td>
      <td><?php if ($bk['status'] === 'pending'): ?><form method="post" data-confirm="Cancel this booking request?"><?= csrf_field() ?><input type="hidden" name="cancel" value="<?= (int)$bk['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Cancel</button></form><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
