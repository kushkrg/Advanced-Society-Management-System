<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf('login.php');
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role'], 'flat_id' => $user['flat_id'] ? (int)$user['flat_id'] : null];
        redirect('dashboard.php');
    }
    $error = 'Incorrect email or password.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="auth-body">
<div class="auth-shell">
  <div class="auth-panel">
    <div class="auth-logo">S</div>
    <h2><?= e(APP_NAME) ?></h2>
    <p>Your whole society — billing, complaints, visitors and community — in one calm dashboard.</p>
  </div>
  <div class="auth-form">
    <h1>Welcome back</h1>
    <p class="auth-sub">Login to your society account</p>
    <?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>
    <?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="field"><label>Email</label><input type="email" name="email" required autofocus placeholder="you@example.com"></div>
      <div class="field"><label>Password</label><input type="password" name="password" required placeholder="Your password"></div>
      <button type="submit" class="btn btn-block">Login</button>
    </form>
    <p class="auth-switch">New resident? <a href="register.php">Register here</a></p>
    <p class="auth-switch" style="margin-top:6px">Demo: admin@example.com / Admin@123</p>
  </div>
</div>
</body>
</html>
