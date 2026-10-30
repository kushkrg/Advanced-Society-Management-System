<?php // Shared flat form (add + edit). Expects $old, $errors. ?>
<div class="card" style="max-width:680px">
  <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="field"><label>Block *</label><input type="text" name="block_name" value="<?= e($old['block_name'] ?? '') ?>" required placeholder="e.g. A" maxlength="10"></div>
      <div class="field"><label>Flat No. *</label><input type="text" name="flat_no" value="<?= e($old['flat_no'] ?? '') ?>" required placeholder="e.g. 101" maxlength="10"></div>
      <div class="field"><label>Floor</label><input type="number" name="floor_no" value="<?= e((string)($old['floor_no'] ?? '')) ?>" placeholder="e.g. 1" min="0" max="60"></div>
      <div class="field"><label>Flat Type</label>
        <select name="flat_type">
          <?php foreach (FLAT_TYPES as $t): ?><option value="<?= e($t) ?>" <?= ($old['flat_type'] ?? '2BHK') === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
        </select></div>
      <div class="field"><label>Area (sq. ft.)</label><input type="number" name="area_sqft" value="<?= e((string)($old['area_sqft'] ?? '')) ?>" placeholder="e.g. 950" min="0"></div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <button type="submit" class="btn"><?= e($submitLabel ?? 'Save Flat') ?></button>
      <a class="btn btn-light" href="flats.php">Cancel</a>
    </div>
  </form>
</div>
