<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
$db = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decide'])) {
    verify_csrf('bookings.php');
    $id = (int)$_POST['id'];
    $decision = $_POST['decide'] === 'approved' ? 'approved' : 'rejected';
    if ($decision === 'approved') {
        // Block double-booking: reject if another approved booking exists for same facility+date
        $stmt = $db->prepare('SELECT facility_id, booking_date FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
        $bk = $stmt->fetch();
        $chk = $db->prepare("SELECT id FROM bookings WHERE facility_id = ? AND booking_date = ? AND status = 'approved' AND id <> ?");
        $chk->execute([$bk['facility_id'], $bk['booking_date'], $id]);
        if ($chk->fetch()) {
            set_flash('error', 'Another approved booking already exists for this facility on that date.');
            redirect('bookings.php');
        }
    }
    $db->prepare('UPDATE bookings SET status = ? WHERE id = ? AND status = ?')->execute([$decision, $id, 'pending']);
    set_flash('success', 'Booking ' . $decision . '.');
    redirect('bookings.php');
}
$statusFilter = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected', 'cancelled'], true) ? $_GET['status'] : '';
$sql = "SELECT bk.*, fac.name AS fac_name, usr.name AS res_name, f.block_name, f.flat_no FROM bookings bk
    JOIN facilities fac ON fac.id = bk.facility_id JOIN users usr ON usr.id = bk.user_id LEFT JOIN flats f ON f.id = bk.flat_id" .
    ($statusFilter ? ' WHERE bk.status = ?' : '') . ' ORDER BY bk.id DESC LIMIT 100';
$stmt = $db->prepare($sql);
$stmt->execute($statusFilter ? [$statusFilter] : []);
$bookings = $stmt->fetchAll();
$pageTitle = 'Facility Bookings';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Facility Bookings</h1><p>Approve or reject resident booking requests</p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>
<div class="filter-card">
  <a class="btn btn-sm <?= $statusFilter === '' ? '' : 'btn-light' ?>" href="bookings.php">All</a>
  <?php foreach (['pending', 'approved', 'rejected', 'cancelled'] as $st): ?>
  <a class="btn btn-sm <?= $statusFilter === $st ? '' : 'btn-light' ?>" href="bookings.php?status=<?= $st ?>"><?= ucfirst($st) ?></a>
  <?php endforeach; ?>
</div>
<div class="card">
  <div class="table-wrap"><table>
    <tr><th>Facility</th><th>Resident</th><th>Date</th><th>Slot / Purpose</th><th>Status</th><th>Action</th></tr>
    <?php if (!$bookings): ?><tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">No bookings found.</td></tr><?php endif; ?>
    <?php foreach ($bookings as $bk): ?>
    <tr>
      <td><strong><?= e($bk['fac_name']) ?></strong></td>
      <td><?= e($bk['res_name']) ?><?= $bk['flat_no'] ? '<br><small style="color:var(--muted)">' . e($bk['block_name'] . '-' . $bk['flat_no']) . '</small>' : '' ?></td>
      <td><?= e(date('D, d M Y', strtotime($bk['booking_date']))) ?></td>
      <td><?= e($bk['time_slot'] ?: '-') ?><?= $bk['purpose'] ? '<br><small style="color:var(--muted)">' . e($bk['purpose']) . '</small>' : '' ?></td>
      <td><span class="pill pill-<?= e($bk['status']) ?>"><?= e($bk['status']) ?></span></td>
      <td><?php if ($bk['status'] === 'pending'): ?>
        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$bk['id'] ?>"><button class="btn btn-sm" name="decide" value="approved" type="submit">Approve</button></form>
        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$bk['id'] ?>"><button class="btn btn-sm btn-danger" name="decide" value="rejected" type="submit">Reject</button></form>
      <?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
