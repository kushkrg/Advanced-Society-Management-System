<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_login();
$u = current_user();
$stmt = db()->prepare('SELECT u.*, f.block_name, f.flat_no, f.flat_type FROM users u LEFT JOIN flats f ON f.id = u.flat_id WHERE u.id = ?');
$stmt->execute([$u['id']]);
$me = $stmt->fetch();
$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>My Profile</h1><p>Your society account</p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<div class="card" style="max-width:560px">
  <div class="user-cell" style="margin-bottom:18px"><span class="avatar" style="width:56px;height:56px;font-size:20px"><?= e(initials($me['name'])) ?></span>
  <span><strong style="font-size:1.15rem"><?= e($me['name']) ?></strong><br><span class="badge" style="text-transform:capitalize"><?= e($me['role']) ?><?= $me['role'] === 'resident' ? ' &bull; ' . e($me['resident_type']) : '' ?></span></span></div>
  <dl class="kv">
    <dt>Email</dt><dd><?= e($me['email']) ?></dd>
    <dt>Mobile</dt><dd><?= e($me['phone'] ?: '-') ?></dd>
    <?php if ($me['flat_no']): ?><dt>Flat</dt><dd><?= e($me['block_name'] . '-' . $me['flat_no']) ?> (<?= e($me['flat_type']) ?>)</dd><?php endif; ?>
    <dt>Member Since</dt><dd><?= e(date('d M Y', strtotime($me['created_at']))) ?></dd>
  </dl>
  <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn" href="change-password.php">Change Password</a>
    <a class="btn btn-light" href="dashboard.php">Back to Dashboard</a>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
