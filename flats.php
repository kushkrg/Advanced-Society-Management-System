<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
$db = db();
$block = trim($_GET['block'] ?? '');
$occ = $_GET['occupancy'] ?? '';
$flats = $db->query('SELECT f.*,
    (SELECT COUNT(*) FROM users u WHERE u.flat_id = f.id AND u.role = \'resident\' AND u.is_active = 1) AS res_count,
    (SELECT u.name FROM users u WHERE u.flat_id = f.id AND u.role = \'resident\' AND u.is_active = 1 ORDER BY u.id LIMIT 1) AS res_name
    FROM flats f ORDER BY f.block_name, CAST(f.flat_no AS UNSIGNED), f.flat_no')->fetchAll();
$blocks = array_unique(array_column($flats, 'block_name'));
$pageTitle = 'Flats';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Flats</h1><p><?= count($flats) ?> flat(s) in the society &bull; green = occupied</p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>
<div class="filter-card" style="display:flex;gap:10px;flex-wrap:wrap;justify-content:space-between">
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap">
    <select name="block" style="width:auto">
      <option value="">All Blocks</option>
      <?php foreach ($blocks as $b): ?><option value="<?= e($b) ?>" <?= $block === $b ? 'selected' : '' ?>>Block <?= e($b) ?></option><?php endforeach; ?>
    </select>
    <select name="occupancy" style="width:auto">
      <option value="">All Flats</option>
      <option value="occupied" <?= $occ === 'occupied' ? 'selected' : '' ?>>Occupied</option>
      <option value="vacant" <?= $occ === 'vacant' ? 'selected' : '' ?>>Vacant</option>
    </select>
    <button class="btn" type="submit">Filter</button>
  </form>
  <a class="btn btn-amber" href="flat-add.php">+ Add Flat</a>
</div>
<?php
$shown = 0;
foreach ($flats as $flat) {
    $isOcc = (int)$flat['res_count'] > 0;
    if ($block !== '' && $flat['block_name'] !== $block) continue;
    if ($occ === 'occupied' && !$isOcc) continue;
    if ($occ === 'vacant' && $isOcc) continue;
    if ($shown === 0) echo '<div class="flat-grid">';
    $shown++;
?>
  <div class="flat-card <?= $isOcc ? 'occupied' : '' ?>">
    <div class="flat-no"><?= e(flat_label($flat)) ?></div>
    <div style="color:var(--muted);font-size:.88rem"><?= e($flat['flat_type']) ?> &bull; Floor <?= (int)$flat['floor_no'] ?></div>
    <div style="font-size:.88rem;margin:6px 0 12px"><?= $isOcc ? '<strong>' . e($flat['res_name']) . '</strong>' : '<span style="color:var(--muted)">Vacant</span>' ?></div>
    <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
      <a class="btn btn-sm btn-view" href="flat-view.php?id=<?= (int)$flat['id'] ?>">View</a>
      <a class="btn btn-sm btn-edit" href="flat-edit.php?id=<?= (int)$flat['id'] ?>">Edit</a>
      <form method="post" action="flat-delete.php" data-confirm="Delete this flat? It must have no residents or bills.">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$flat['id'] ?>">
        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
      </form>
    </div>
  </div>
<?php }
if ($shown > 0) echo '</div>';
if ($shown === 0): ?><div class="card" style="text-align:center;color:var(--muted)">No flats match this filter. <a href="flat-add.php">Add a flat</a>.</div><?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
