<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_login();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf('change-password.php');
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $stmt = db()->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([current_user()['id']]);
    $hash = (string)$stmt->fetchColumn();
    if (!password_verify($current, $hash)) $errors[] = 'Current password is incorrect.';
    if (strlen($new) < 6) $errors[] = 'New password must be at least 6 characters.';
    if ($new !== $confirm) $errors[] = 'New password and confirmation do not match.';
    if (!$errors) {
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), current_user()['id']]);
        set_flash('success', 'Password changed successfully.');
        redirect('profile.php');
    }
}
$pageTitle = 'Change Password';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Change Password</h1><p>Keep your account secure</p></div>
<?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
<div class="card" style="max-width:520px">
  <form method="post">
    <?= csrf_field() ?>
    <div class="field"><label>Current Password</label><input type="password" name="current_password" required></div>
    <div class="field"><label>New Password</label><input type="password" name="new_password" required></div>
    <div class="field"><label>Confirm New Password</label><input type="password" name="confirm_password" required></div>
    <button type="submit" class="btn btn-block">Update Password</button>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
