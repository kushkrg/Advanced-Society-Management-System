<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_login();
$db = db();
$u = current_user();
$month = date('Y-m');
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>

<?php if (is_admin()):
    $totalFlats = (int)$db->query('SELECT COUNT(*) FROM flats')->fetchColumn();
    $occupied = (int)$db->query("SELECT COUNT(DISTINCT flat_id) FROM users WHERE role = 'resident' AND is_active = 1 AND flat_id IS NOT NULL")->fetchColumn();
    $residents = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'resident' AND is_active = 1")->fetchColumn();
    $stmt = $db->prepare('SELECT COALESCE(SUM(total_amount),0) AS billed, COALESCE(SUM(paid_amount),0) AS collected FROM bills WHERE bill_month = ?');
    $stmt->execute([$month]);
    $sum = $stmt->fetch();
    $billed = (float)$sum['billed']; $collected = (float)$sum['collected'];
    $pct = $billed > 0 ? (int)round($collected / $billed * 100) : 0;
    $openComplaints = (int)$db->query("SELECT COUNT(*) FROM complaints WHERE status IN ('open','in_progress')")->fetchColumn();
    $visitorsToday = $db->prepare('SELECT COUNT(*) FROM visitors WHERE DATE(in_time) = ?');
    $visitorsToday->execute([date('Y-m-d')]);
    $visitorsToday = (int)$visitorsToday->fetchColumn();
    $pendingBookings = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
    $recentNotices = $db->query('SELECT * FROM notices ORDER BY is_pinned DESC, id DESC LIMIT 3')->fetchAll();
?>
<div class="page-head"><h1><?= e(setting($db, 'society_name', 'My Society')) ?></h1><p><?= e(date('l, d F Y')) ?> &bull; Society office overview</p></div>
<div class="stats">
  <div class="stat-card"><small>Total Flats</small><div class="num"><?= $totalFlats ?></div><small><?= $occupied ?> occupied &bull; <?= $totalFlats - $occupied ?> vacant</small></div>
  <div class="stat-card"><small>Residents</small><div class="num"><?= $residents ?></div></div>
  <div class="stat-card"><small>Open Complaints</small><div class="num"><?= $openComplaints ?></div></div>
  <div class="stat-card"><small>Visitors Today</small><div class="num"><?= $visitorsToday ?></div></div>
</div>
<div class="detail-grid">
  <div class="card">
    <h2>Maintenance Collection — <?= e(date('F Y')) ?></h2>
    <dl class="kv">
      <dt>Billed</dt><dd><strong><?= e(inr($billed)) ?></strong></dd>
      <dt>Collected</dt><dd><strong style="color:var(--teal-dark)"><?= e(inr($collected)) ?></strong></dd>
      <dt>Pending</dt><dd><strong style="color:#B45309"><?= e(inr($billed - $collected)) ?></strong></dd>
    </dl>
    <div class="progress" style="margin:14px 0"><div style="width:<?= $pct ?>%"></div></div>
    <p style="color:var(--muted)"><?= $pct ?>% collected this month<?php if ($billed == 0): ?> — no bills generated yet for this month<?php endif; ?>.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
      <a class="btn" href="bills.php?month=<?= e($month) ?>">Manage Bills</a>
      <a class="btn btn-amber" href="flat-add.php">+ Add Flat</a>
      <a class="btn btn-light" href="reports.php">Reports</a>
      <?php if ($pendingBookings): ?><a class="btn btn-light" href="bookings.php"><?= $pendingBookings ?> booking(s) waiting</a><?php endif; ?>
    </div>
  </div>
  <div class="card">
    <h2>Latest Notices</h2>
    <?php if (!$recentNotices): ?><p style="color:var(--muted)">No notices yet. <a href="notices.php">Post the first notice</a>.</p><?php endif; ?>
    <?php foreach ($recentNotices as $n): ?>
    <div style="padding:10px 0;border-bottom:1px solid var(--line)">
      <strong><?= $n['is_pinned'] ? '📌 ' : '' ?><?= e($n['title']) ?></strong><br>
      <small style="color:var(--muted)"><?= e(date('d M Y', strtotime($n['created_at']))) ?> &bull; <?= e($n['category']) ?></small>
    </div>
    <?php endforeach; ?>
    <div style="margin-top:14px"><a class="btn btn-light" href="notices.php">All Notices</a></div>
  </div>
</div>

<?php else:
    $flat = null; $dues = 0.0; $myComplaints = 0;
    if (!empty($u['flat_id'])) {
        $stmt = $db->prepare('SELECT * FROM flats WHERE id = ?');
        $stmt->execute([$u['flat_id']]);
        $flat = $stmt->fetch();
        $stmt = $db->prepare('SELECT COALESCE(SUM(total_amount - paid_amount),0) FROM bills WHERE flat_id = ? AND status IN (\'unpaid\',\'partial\')');
        $stmt->execute([$u['flat_id']]);
        $dues = (float)$stmt->fetchColumn();
    }
    $stmt = $db->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ? AND status IN ('open','in_progress')");
    $stmt->execute([$u['id']]);
    $myComplaints = (int)$stmt->fetchColumn();
    $notices = $db->query('SELECT * FROM notices ORDER BY is_pinned DESC, id DESC LIMIT 3')->fetchAll();
    $events = $db->query('SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 3')->fetchAll();
?>
<div class="page-head"><h1>Namaste, <?= e(explode(' ', $u['name'])[0]) ?> 🙏</h1><p><?= e(setting($db, 'society_name', 'My Society')) ?> &bull; <?= e(date('l, d F Y')) ?></p></div>
<?php if (!$flat): ?>
<div class="alert alert-error" style="background:#FFFBEB;border-color:#FDE68A;color:#92400E">Your account is not linked to a flat yet. Please visit the society office — once they link your flat, your bills and services appear here.</div>
<?php endif; ?>
<div class="stats">
  <div class="stat-card"><small>My Flat</small><div class="num" style="font-size:1.6rem"><?= $flat ? e(flat_label($flat)) : '—' ?></div><small><?= $flat ? e($flat['flat_type']) . ' &bull; Floor ' . e((string)$flat['floor_no']) : 'Not assigned' ?></small></div>
  <div class="stat-card"><small>Maintenance Dues</small><div class="num" style="font-size:1.6rem"><?= e(inr($dues)) ?></div><small><?= $dues > 0 ? 'Please pay at the society office' : 'All clear 🎉' ?></small></div>
  <div class="stat-card"><small>My Open Complaints</small><div class="num"><?= $myComplaints ?></div></div>
  <div class="stat-card"><small>Upcoming Events</small><div class="num"><?= count($events) ?></div></div>
</div>
<div class="detail-grid">
  <div class="card">
    <h2>Notice Board</h2>
    <?php if (!$notices): ?><p style="color:var(--muted)">No notices right now.</p><?php endif; ?>
    <?php foreach ($notices as $n): ?>
    <div style="padding:10px 0;border-bottom:1px solid var(--line)">
      <strong><?= $n['is_pinned'] ? '📌 ' : '' ?><?= e($n['title']) ?></strong><br>
      <small style="color:var(--muted)"><?= e(date('d M Y', strtotime($n['created_at']))) ?> &bull; <?= e($n['category']) ?></small>
      <p style="color:var(--muted);margin-top:4px"><?= e(mb_strimwidth($n['body'], 0, 140, '…')) ?></p>
    </div>
    <?php endforeach; ?>
    <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap">
      <a class="btn" href="my-bills.php">My Bills</a>
      <a class="btn btn-amber" href="complaints.php">Raise a Complaint</a>
      <a class="btn btn-light" href="book-facility.php">Book Facility</a>
    </div>
  </div>
  <div class="card">
    <h2>Upcoming Events</h2>
    <?php if (!$events): ?><p style="color:var(--muted)">No upcoming events.</p><?php endif; ?>
    <?php foreach ($events as $ev): ?>
    <div style="padding:10px 0;border-bottom:1px solid var(--line)">
      <strong><?= e($ev['title']) ?></strong><br>
      <small style="color:var(--muted)"><?= e(date('d M Y', strtotime($ev['event_date']))) ?><?= $ev['event_time'] ? ' &bull; ' . e(date('h:i A', strtotime($ev['event_time']))) : '' ?><?= $ev['venue'] ? ' &bull; ' . e($ev['venue']) : '' ?></small>
    </div>
    <?php endforeach; ?>
    <div style="margin-top:14px"><a class="btn btn-light" href="events.php">All Events</a></div>
  </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
