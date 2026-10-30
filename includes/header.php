<?php require_once __DIR__ . '/functions.php'; $u = current_user(); $cur = basename($_SERVER['PHP_SELF']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Dashboard') ?> | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="layout">
<aside class="sidebar" id="sidebar">
  <a class="brand" href="dashboard.php"><span class="brand-icon">S</span><span>Society<b>Pro</b></span></a>
  <?php if ($u): ?>
  <div class="side-user">
    <span class="avatar"><?= e(initials($u['name'])) ?></span>
    <span><strong><?= e($u['name']) ?></strong><br><small style="text-transform:capitalize"><?= e($u['role']) ?></small></span>
  </div>
  <?php endif; ?>
  <nav class="side-nav">
    <a href="dashboard.php" class="<?= $cur === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <?php if (is_admin()): ?>
    <a href="flats.php" class="<?= in_array($cur, ['flats.php','flat-add.php','flat-edit.php','flat-view.php'], true) ? 'active' : '' ?>">Flats</a>
    <a href="residents.php" class="<?= in_array($cur, ['residents.php','resident-add.php','resident-edit.php','resident-view.php'], true) ? 'active' : '' ?>">Residents</a>
    <a href="bills.php" class="<?= in_array($cur, ['bills.php','bill-view.php'], true) ? 'active' : '' ?>">Maintenance Bills</a>
    <a href="bookings.php" class="<?= $cur === 'bookings.php' ? 'active' : '' ?>">Facility Bookings</a>
    <a href="expenses.php" class="<?= $cur === 'expenses.php' ? 'active' : '' ?>">Expenses</a>
    <a href="reports.php" class="<?= $cur === 'reports.php' ? 'active' : '' ?>">Reports</a>
    <a href="settings.php" class="<?= $cur === 'settings.php' ? 'active' : '' ?>">Settings</a>
    <?php else: ?>
    <a href="my-flat.php" class="<?= $cur === 'my-flat.php' ? 'active' : '' ?>">My Flat & Family</a>
    <a href="my-bills.php" class="<?= in_array($cur, ['my-bills.php','bill-view.php'], true) ? 'active' : '' ?>">My Bills</a>
    <a href="book-facility.php" class="<?= $cur === 'book-facility.php' ? 'active' : '' ?>">Book Facility</a>
    <?php endif; ?>
    <a href="complaints.php" class="<?= $cur === 'complaints.php' ? 'active' : '' ?>">Complaints</a>
    <a href="visitors.php" class="<?= $cur === 'visitors.php' ? 'active' : '' ?>">Visitors</a>
    <a href="notices.php" class="<?= $cur === 'notices.php' ? 'active' : '' ?>">Notices</a>
    <a href="events.php" class="<?= $cur === 'events.php' ? 'active' : '' ?>">Events</a>
    <a href="facilities.php" class="<?= $cur === 'facilities.php' ? 'active' : '' ?>">Facilities</a>
    <a href="profile.php" class="<?= $cur === 'profile.php' ? 'active' : '' ?>">My Profile</a>
    <a href="logout.php">Logout</a>
  </nav>
  <div class="side-foot">Society &bull; PHP + MySQL</div>
</aside>
<div class="topbar">
  <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Menu">&#9776;</button>
  <strong><?= e($pageTitle ?? 'Dashboard') ?></strong>
</div>
<main class="main">
