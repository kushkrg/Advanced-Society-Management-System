<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
$db = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf('settings.php');
    $money = fn($k, $def) => (string)max(0, (float)($_POST[$k] ?? $def));
    $values = [
        'society_name' => trim($_POST['society_name'] ?? 'My Society') ?: 'My Society',
        'society_address' => trim($_POST['society_address'] ?? ''),
        'rate_1bhk' => $money('rate_1bhk', 1500),
        'rate_2bhk' => $money('rate_2bhk', 2500),
        'rate_3bhk' => $money('rate_3bhk', 3500),
        'rate_4bhk' => $money('rate_4bhk', 4500),
        'water_charge' => $money('water_charge', 300),
        'parking_rate' => $money('parking_rate', 200),
        'late_fee' => $money('late_fee', 100),
        'due_day' => (string)max(1, min(28, (int)($_POST['due_day'] ?? 10))),
    ];
    $stmt = $db->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
    foreach ($values as $k => $v) $stmt->execute([$k, $v]);
    set_flash('success', 'Settings saved. New rates apply to bills generated from now on.');
    redirect('settings.php');
}
$s = settings_all($db);
$pageTitle = 'Settings';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Society Settings</h1><p>Maintenance rates used when generating monthly bills</p></div>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<div class="card" style="max-width:680px">
  <form method="post">
    <?= csrf_field() ?>
    <div class="field"><label>Society Name</label><input type="text" name="society_name" value="<?= e($s['society_name'] ?? 'My Society') ?>"></div>
    <div class="field"><label>Society Address (shown on receipts)</label><input type="text" name="society_address" value="<?= e($s['society_address'] ?? '') ?>" placeholder="e.g. Green Valley Apartments, MG Road"></div>
    <h3 style="margin:18px 0 10px;color:var(--teal-dark)">Monthly Maintenance Rates (₹)</h3>
    <div class="form-grid">
      <div class="field"><label>1BHK</label><input type="number" step="0.01" min="0" name="rate_1bhk" value="<?= e($s['rate_1bhk'] ?? '1500') ?>"></div>
      <div class="field"><label>2BHK</label><input type="number" step="0.01" min="0" name="rate_2bhk" value="<?= e($s['rate_2bhk'] ?? '2500') ?>"></div>
      <div class="field"><label>3BHK</label><input type="number" step="0.01" min="0" name="rate_3bhk" value="<?= e($s['rate_3bhk'] ?? '3500') ?>"></div>
      <div class="field"><label>4BHK</label><input type="number" step="0.01" min="0" name="rate_4bhk" value="<?= e($s['rate_4bhk'] ?? '4500') ?>"></div>
      <div class="field"><label>Water Charge (per flat)</label><input type="number" step="0.01" min="0" name="water_charge" value="<?= e($s['water_charge'] ?? '300') ?>"></div>
      <div class="field"><label>Parking (per vehicle)</label><input type="number" step="0.01" min="0" name="parking_rate" value="<?= e($s['parking_rate'] ?? '200') ?>"></div>
      <div class="field"><label>Late Fee (₹, once per late bill)</label><input type="number" step="0.01" min="0" name="late_fee" value="<?= e($s['late_fee'] ?? '100') ?>"></div>
      <div class="field"><label>Due Day of Month</label><input type="number" min="1" max="28" name="due_day" value="<?= e($s['due_day'] ?? '10') ?>"></div>
    </div>
    <button class="btn btn-block" type="submit">Save Settings</button>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
