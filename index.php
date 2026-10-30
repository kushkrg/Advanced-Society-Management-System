<?php require_once __DIR__ . '/includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(APP_NAME) ?> — Advanced Society Management System in PHP & MySQL</title>
<meta name="description" content="Manage your housing society: flats, residents, maintenance billing, complaints, visitors, notices, events and facility bookings. Built in Core PHP & MySQL.">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>.landing{max-width:1000px;margin:0 auto;padding:64px 20px;text-align:center}.landing h1{font-size:2.6rem;color:var(--teal-deep)}.landing p.lead{color:var(--muted);font-size:1.12rem;margin:14px auto 26px;max-width:680px}.feat{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-top:40px;text-align:left}.feat .f{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px}.feat h3{color:var(--teal-dark);margin-bottom:6px}.feat p{color:var(--muted);font-size:.94rem}</style>
</head>
<body>
<div class="landing">
  <div class="auth-logo" style="margin:0 auto 18px">S</div>
  <h1><?= e(APP_NAME) ?></h1>
  <p class="lead"><?= e(APP_TAGLINE) ?> — flats &amp; residents, one-click monthly maintenance billing with receipts, complaints, visitor gate logs, notices, events and facility bookings. Core PHP &amp; MySQL.</p>
  <?php if (current_user()): ?>
    <a class="btn" href="dashboard.php">Go to Dashboard</a>
  <?php else: ?>
    <a class="btn" href="login.php">Login</a>
    <a class="btn btn-amber" href="register.php">Register as Resident</a>
  <?php endif; ?>
  <div class="feat">
    <div class="f"><h3>Maintenance Billing</h3><p>Generate monthly bills for all flats in one click — by flat type, water and parking — record payments and print receipts. Dues tracked automatically.</p></div>
    <div class="f"><h3>Residents &amp; Flats</h3><p>Blocks, flats, owners &amp; families, vehicles — with an occupancy view of the whole society.</p></div>
    <div class="f"><h3>Complaints &amp; Visitors</h3><p>Residents raise complaints and track them; the gate logs every visitor with check-out.</p></div>
    <div class="f"><h3>Community Life</h3><p>Notices board, society events and clubhouse/hall bookings with admin approval.</p></div>
  </div>
  <p style="margin-top:36px;color:var(--muted)">Demo — Admin: <strong>admin@example.com / Admin@123</strong> &bull; Resident: <strong>resident@example.com / Resident@123</strong> (Flat A-101)</p>
</div>
</body>
</html>
