<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
$db = db();
$q = trim($_GET['q'] ?? '');
$where = " WHERE u.role = 'resident'";
$params = [];
if ($q !== '') { $where .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)'; $like = '%' . $q . '%'; array_push($params, $like, $like, $like); }
$stmt = $db->prepare("SELECT u.*, f.block_name, f.flat_no, f.flat_type FROM users u LEFT JOIN flats f ON f.id = u.flat_id $where ORDER BY u.name");
$stmt->execute($params);
$residents = $stmt->fetchAll();
$pageTitle = 'Residents';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Residents</h1><p><?= count($residents) ?> resident account(s)</p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>
<div class="filter-card">
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search name, email, mobile..." style="flex:1;min-width:210px;padding:11px 13px;border:1.5px solid #D8D2C4;border-radius:11px;font:inherit">
    <button class="btn" type="submit">Search</button>
    <a class="btn btn-amber" href="resident-add.php">+ Add Resident</a>
  </form>
</div>
<div class="card">
  <div class="table-wrap"><table>
    <tr><th>Resident</th><th>Flat</th><th>Type</th><th>Contact</th><th>Status</th><th>Actions</th></tr>
    <?php if (!$residents): ?><tr><td colspan="6" style="text-align:center;color:var(--muted);padding:26px">No residents found. <a href="resident-add.php">Add the first resident</a>.</td></tr><?php endif; ?>
    <?php foreach ($residents as $r): ?>
    <tr>
      <td><div class="user-cell"><span class="avatar"><?= e(initials($r['name'])) ?></span><span><strong><?= e($r['name']) ?></strong><br><small style="color:var(--muted)"><?= e($r['email']) ?></small></span></div></td>
      <td><?= $r['flat_no'] ? '<strong>' . e($r['block_name'] . '-' . $r['flat_no']) . '</strong> <small style="color:var(--muted)">' . e($r['flat_type']) . '</small>' : '<span style="color:var(--muted)">Not assigned</span>' ?></td>
      <td style="text-transform:capitalize"><?= e($r['resident_type']) ?></td>
      <td><?= e($r['phone'] ?: '-') ?></td>
      <td><span class="badge <?= (int)$r['is_active'] === 1 ? 'badge-active' : 'badge-inactive' ?>"><?= (int)$r['is_active'] === 1 ? 'Active' : 'Inactive' ?></span></td>
      <td><div class="actions">
        <a class="btn btn-sm btn-view" href="resident-view.php?id=<?= (int)$r['id'] ?>">View</a>
        <a class="btn btn-sm btn-edit" href="resident-edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
        <form method="post" action="resident-delete.php" data-confirm="Delete this resident account? Their bills history stays on the flat.">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="btn btn-sm btn-danger" type="submit">Delete</button>
        </form>
      </div></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
