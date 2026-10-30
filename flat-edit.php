<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
$db = db();

function validate_flat(PDO $db, array $post, ?int $excludeId = null): array {
    $data = [
        'block_name' => strtoupper(trim($post['block_name'] ?? '')),
        'flat_no' => trim($post['flat_no'] ?? ''),
        'floor_no' => ($post['floor_no'] ?? '') !== '' ? (int)$post['floor_no'] : 0,
        'flat_type' => in_array($post['flat_type'] ?? '', FLAT_TYPES, true) ? $post['flat_type'] : '2BHK',
        'area_sqft' => ($post['area_sqft'] ?? '') !== '' ? (int)$post['area_sqft'] : null,
    ];
    $errors = [];
    if ($data['block_name'] === '' || $data['flat_no'] === '') $errors[] = 'Block and flat number are required.';
    if (!$errors) {
        $sql = 'SELECT id FROM flats WHERE block_name = ? AND flat_no = ?' . ($excludeId ? ' AND id <> ?' : '');
        $stmt = $db->prepare($sql);
        $stmt->execute($excludeId ? [$data['block_name'], $data['flat_no'], $excludeId] : [$data['block_name'], $data['flat_no']]);
        if ($stmt->fetch()) $errors[] = 'This flat already exists.';
    }
    return [$data, $errors];
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM flats WHERE id = ?');
$stmt->execute([$id]);
$flat = $stmt->fetch();
if (!$flat) { set_flash('error', 'Flat not found.'); redirect('flats.php'); }
$errors = []; $old = $flat;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf('flats.php');
    [$data, $errors] = validate_flat($db, $_POST, $id);
    if (!$errors) {
        $db->prepare('UPDATE flats SET block_name = ?, flat_no = ?, floor_no = ?, flat_type = ?, area_sqft = ? WHERE id = ?')
           ->execute([$data['block_name'], $data['flat_no'], $data['floor_no'], $data['flat_type'], $data['area_sqft'], $id]);
        set_flash('success', 'Flat updated.');
        redirect('flat-view.php?id=' . $id);
    }
    $old = $_POST;
}
$pageTitle = 'Edit Flat';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Edit Flat <?= e(flat_label($flat)) ?></h1><p>Update flat details</p></div>
<?php $submitLabel = 'Update Flat'; require __DIR__ . '/includes/flat-form.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
