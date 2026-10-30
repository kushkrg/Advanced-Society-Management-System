<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_login();
$db = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add']) && is_admin()) {
    verify_csrf('notices.php');
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $category = trim($_POST['category'] ?? 'General') ?: 'General';
    $pinned = isset($_POST['is_pinned']) ? 1 : 0;
    if (strlen($title) >= 3 && $body !== '') {
        $db->prepare('INSERT INTO notices (title, body, category, is_pinned, posted_by) VALUES (?, ?, ?, ?, ?)')
           ->execute([$title, $body, $category, $pinned, current_user()['id']]);
        set_flash('success', 'Notice posted on the board.');
    } else set_flash('error', 'Notice title and details are required.');
    redirect('notices.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && is_admin()) {
    verify_csrf('notices.php');
    $db->prepare('DELETE FROM notices WHERE id = ?')->execute([(int)$_POST['delete_id']]);
    set_flash('success', 'Notice removed.');
    redirect('notices.php');
}
$notices = $db->query('SELECT * FROM notices ORDER BY is_pinned DESC, id DESC LIMIT 50')->fetchAll();
$pageTitle = 'Notices';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Notice Board</h1><p>Announcements from the society office</p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>
<?php if (is_admin()): ?>
<div class="card" style="margin-bottom:22px">
  <h2 style="font-size:1.08rem;margin-bottom:14px">Post a Notice</h2>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="add" value="1">
    <div class="form-grid">
      <div class="field"><label>Title *</label><input type="text" name="title" required placeholder="e.g. Water supply maintenance on Sunday"></div>
      <div class="field"><label>Category</label><input type="text" name="category" placeholder="General / Maintenance / Event / Urgent"></div>
      <div class="field" style="grid-column:1/-1"><label>Details *</label><textarea name="body" rows="3" required placeholder="Write the notice..."></textarea></div>
    </div>
    <label style="display:flex;gap:8px;align-items:center;margin-bottom:14px"><input type="checkbox" name="is_pinned" value="1" style="width:auto"> Pin to top</label>
    <button class="btn" type="submit">Post Notice</button>
  </form>
</div>
<?php endif; ?>
<?php if (!$notices): ?><div class="card" style="text-align:center;color:var(--muted)">No notices yet.</div><?php endif; ?>
<?php foreach ($notices as $n): ?>
<div class="<?= $n['is_pinned'] ? 'notice-pin' : 'notice-card' ?>">
  <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <strong style="font-size:1.08rem"><?= $n['is_pinned'] ? '📌 ' : '' ?><?= e($n['title']) ?></strong>
    <?php if (is_admin()): ?><form method="post" data-confirm="Remove this notice?"><?= csrf_field() ?><input type="hidden" name="delete_id" value="<?= (int)$n['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Delete</button></form><?php endif; ?>
  </div>
  <small style="color:var(--muted)"><?= e(date('d M Y, h:i A', strtotime($n['created_at']))) ?> &bull; <?= e($n['category']) ?></small>
  <p style="margin-top:8px;white-space:pre-line"><?= e($n['body']) ?></p>
</div>
<?php endforeach; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
