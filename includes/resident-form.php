<?php // Shared resident form (admin add + edit). Expects $old, $errors, $flats, $isEdit. ?>
<div class="card" style="max-width:760px">
  <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="field"><label>Full Name *</label><input type="text" name="name" value="<?= e($old['name'] ?? '') ?>" required placeholder="Resident full name"></div>
      <div class="field"><label>Email * (login)</label><input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required placeholder="resident@example.com"></div>
      <div class="field"><label>Mobile</label><input type="text" name="phone" value="<?= e($old['phone'] ?? '') ?>" placeholder="Mobile number"></div>
      <div class="field"><label>Assign Flat</label>
        <select name="flat_id">
          <option value="">— Not assigned —</option>
          <?php foreach ($flats as $f): ?><option value="<?= (int)$f['id'] ?>" <?= (string)($old['flat_id'] ?? '') === (string)$f['id'] ? 'selected' : '' ?>><?= e(flat_label($f)) ?> (<?= e($f['flat_type']) ?>)</option><?php endforeach; ?>
        </select></div>
      <div class="field"><label>Resident Type</label>
        <select name="resident_type">
          <option value="owner" <?= ($old['resident_type'] ?? 'owner') === 'owner' ? 'selected' : '' ?>>Owner</option>
          <option value="tenant" <?= ($old['resident_type'] ?? '') === 'tenant' ? 'selected' : '' ?>>Tenant</option>
        </select></div>
      <div class="field"><label>Move-in Date</label><input type="date" name="move_in_date" value="<?= e($old['move_in_date'] ?? '') ?>"></div>
      <?php if (!empty($isEdit)): ?>
      <div class="field"><label>Status</label>
        <select name="is_active">
          <option value="1" <?= (int)($old['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
          <option value="0" <?= (int)($old['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Inactive</option>
        </select></div>
      <div class="field"><label>New Password (leave blank to keep)</label><input type="password" name="password" placeholder="Only if resetting"></div>
      <?php else: ?>
      <div class="field"><label>Password *</label><input type="password" name="password" required placeholder="Login password for the resident"></div>
      <?php endif; ?>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <button type="submit" class="btn"><?= e($submitLabel ?? 'Save Resident') ?></button>
      <a class="btn btn-light" href="residents.php">Cancel</a>
    </div>
  </form>
</div>
