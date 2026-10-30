<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
$db = db();
$CATEGORIES = ['Security', 'Housekeeping', 'Repairs', 'Electricity', 'Water', 'Lift Maintenance', 'Garden', 'Events', 'Office', 'Other'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    verify_csrf('expenses.php');
    $title = trim($_POST['title'] ?? '');
    $category = in_array($_POST['category'] ?? '', $CATEGORIES, true) ? $_POST['category'] : 'Other';
    $amount = (float)($_POST['amount'] ?? 0);
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['expense_date'] ?? '') ? $_POST['expense_date'] : date('Y-m-d');
    $vendor = trim($_POST['vendor'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    if (strlen($title) >= 2 && $amount > 0) {
        $db->prepare('INSERT INTO expenses (title, category, amount, expense_date, vendor, notes) VALUES (?, ?, ?, ?, ?, ?)')
           ->execute([$title, $category, $amount, $date, $vendor ?: null, $notes ?: null]);
        set_flash('success', 'Expense recorded.');
    } else set_flash('error', 'Expense title and a valid amount are required.');
    redirect('expenses.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verify_csrf('expenses.php');
    $db->prepare('DELETE FROM expenses WHERE id = ?')->execute([(int)$_POST['delete_id']]);
    set_flash('success', 'Expense deleted.');
    redirect('expenses.php');
}
$month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$stmt = $db->prepare("SELECT * FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = ? ORDER BY expense_date DESC, id DESC");
$stmt->execute([$month]);
$expenses = $stmt->fetchAll();
$total = array_sum(array_map(fn($x) => (float)$x['amount'], $expenses));
$pageTitle = 'Expenses';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Society Expenses — <?= e(date('F Y', strtotime($month . '-01'))) ?></h1><p>Total this month: <strong><?= e(inr($total)) ?></strong></p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>
<div class="card" style="margin-bottom:22px">
  <h2 style="font-size:1.08rem;margin-bottom:14px">Add Expense</h2>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="add" value="1">
    <div class="form-grid">
      <div class="field"><label>Title *</label><input type="text" name="title" required placeholder="e.g. Security agency bill"></div>
      <div class="field"><label>Category</label>
        <select name="category"><?php foreach ($CATEGORIES as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Amount (₹) *</label><input type="number" name="amount" step="0.01" min="1" required></div>
      <div class="field"><label>Date</label><input type="date" name="expense_date" value="<?= e(date('Y-m-d')) ?>" required></div>
      <div class="field"><label>Vendor / Paid To</label><input type="text" name="vendor" placeholder="Vendor name"></div>
      <div class="field"><label>Notes</label><input type="text" name="notes" placeholder="Optional note"></div>
    </div>
    <button class="btn" type="submit">Add Expense</button>
  </form>
</div>
<div class="filter-card">
  <form method="get" style="display:flex;gap:10px">
    <input type="month" name="month" value="<?= e($month) ?>" style="padding:11px 13px;border:1.5px solid #D8D2C4;border-radius:11px;font:inherit">
    <button class="btn" type="submit">View Month</button>
  </form>
</div>
<div class="card">
  <div class="table-wrap"><table>
    <tr><th>Date</th><th>Expense</th><th>Category</th><th>Vendor</th><th>Amount</th><th>Action</th></tr>
    <?php if (!$expenses): ?><tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">No expenses recorded for this month.</td></tr><?php endif; ?>
    <?php foreach ($expenses as $ex): ?>
    <tr>
      <td><?= e(date('d M Y', strtotime($ex['expense_date']))) ?></td>
      <td><strong><?= e($ex['title']) ?></strong><?= $ex['notes'] ? '<br><small style="color:var(--muted)">' . e($ex['notes']) . '</small>' : '' ?></td>
      <td><?= e($ex['category']) ?></td>
      <td><?= e($ex['vendor'] ?: '-') ?></td>
      <td><strong><?= e(inr((float)$ex['amount'])) ?></strong></td>
      <td><form method="post" data-confirm="Delete this expense?"><?= csrf_field() ?><input type="hidden" name="delete_id" value="<?= (int)$ex['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Delete</button></form></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
