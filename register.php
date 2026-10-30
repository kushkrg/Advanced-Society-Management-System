<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf('register.php');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if (strlen($name) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) $errors[] = 'Enter a valid phone number.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if (!$errors) {
        $chk = db()->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) $errors[] = 'An account with this email already exists.';
    }
    if (!$errors) {
        // Public registration always creates a RESIDENT account - never admin.
        // The society office assigns the flat afterwards.
        $stmt = db()->prepare("INSERT INTO users (name, email, phone, password, role, resident_type) VALUES (?, ?, ?, ?, 'resident', 'owner')");
        $stmt->execute([$name, $email, $phone ?: null, password_hash($password, PASSWORD_DEFAULT)]);
        set_flash('success', 'Account created. Login now — the society office will link your flat to your account.');
        redirect('login.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="auth-body">
<div class="auth-shell">
  <div class="auth-panel">
    <div class="auth-logo">S</div>
    <h2><?= e(APP_NAME) ?></h2>
    <p>Register as a resident to see your bills, raise complaints and book facilities.</p>
  </div>
  <div class="auth-form">
    <h1>Resident Registration</h1>
    <p class="auth-sub">Your flat is linked by the society office after registration</p>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="field"><label>Full Name</label><input type="text" name="name" required placeholder="e.g. Rahul Sharma"></div>
      <div class="field"><label>Email</label><input type="email" name="email" required placeholder="you@example.com"></div>
      <div class="field"><label>Mobile</label><input type="text" name="phone" placeholder="Mobile number"></div>
      <div class="field"><label>Password</label><input type="password" name="password" required placeholder="Min 6 characters"></div>
      <button type="submit" class="btn btn-block">Create Account</button>
    </form>
    <p class="auth-switch">Already registered? <a href="login.php">Login</a></p>
  </div>
</div>
</body>
</html>
