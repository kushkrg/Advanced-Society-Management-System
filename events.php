<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_login();
$db = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add']) && is_admin()) {
    verify_csrf('events.php');
    $title = trim($_POST['title'] ?? '');
    $date = (string)($_POST['event_date'] ?? '');
    $time = trim($_POST['event_time'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if (strlen($title) >= 3 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $db->prepare('INSERT INTO events (title, event_date, event_time, venue, description, created_by) VALUES (?, ?, ?, ?, ?, ?)')
           ->execute([$title, $date, $time !== '' && preg_match('/^\d{2}:\d{2}/', $time) ? $time : null, $venue ?: null, $desc ?: null, current_user()['id']]);
        set_flash('success', 'Event added.');
    } else set_flash('error', 'Event title and a valid date are required.');
    redirect('events.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && is_admin()) {
    verify_csrf('events.php');
    $db->prepare('DELETE FROM events WHERE id = ?')->execute([(int)$_POST['delete_id']]);
    set_flash('success', 'Event removed.');
    redirect('events.php');
}
$events = $db->query('SELECT * FROM events ORDER BY (event_date >= CURDATE()) DESC, event_date ASC, id DESC LIMIT 50')->fetchAll();
$pageTitle = 'Events';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Society Events</h1><p>Festivals, meetings and get-togethers</p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>
<?php if (is_admin()): ?>
<div class="card" style="margin-bottom:22px">
  <h2 style="font-size:1.08rem;margin-bottom:14px">Add Event</h2>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="add" value="1">
    <div class="form-grid">
      <div class="field"><label>Event Title *</label><input type="text" name="title" required placeholder="e.g. Diwali Celebration"></div>
      <div class="field"><label>Venue</label><input type="text" name="venue" placeholder="e.g. Community Hall"></div>
      <div class="field"><label>Date *</label><input type="date" name="event_date" required></div>
      <div class="field"><label>Time</label><input type="time" name="event_time"></div>
      <div class="field" style="grid-column:1/-1"><label>Description</label><textarea name="description" rows="2" placeholder="What is planned..."></textarea></div>
    </div>
    <button class="btn" type="submit">Add Event</button>
  </form>
</div>
<?php endif; ?>
<?php if (!$events): ?><div class="card" style="text-align:center;color:var(--muted)">No events yet.</div><?php endif; ?>
<div class="flat-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
<?php foreach ($events as $ev): $upcoming = $ev['event_date'] >= date('Y-m-d'); ?>
  <div class="flat-card" style="text-align:left">
    <div style="display:flex;justify-content:space-between;align-items:start;gap:8px">
      <strong style="font-size:1.05rem"><?= e($ev['title']) ?></strong>
      <?= $upcoming ? '<span class="pill pill-approved">Upcoming</span>' : '<span class="pill pill-closed">Past</span>' ?>
    </div>
    <p style="color:var(--muted);margin-top:6px"><?= e(date('D, d M Y', strtotime($ev['event_date']))) ?><?= $ev['event_time'] ? ' &bull; ' . e(date('h:i A', strtotime($ev['event_time']))) : '' ?><?= $ev['venue'] ? '<br>📍 ' . e($ev['venue']) : '' ?></p>
    <?php if ($ev['description']): ?><p style="color:var(--muted);margin-top:6px"><?= e($ev['description']) ?></p><?php endif; ?>
    <?php if (is_admin()): ?><form method="post" data-confirm="Remove this event?" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="delete_id" value="<?= (int)$ev['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Delete</button></form><?php endif; ?>
  </div>
<?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
