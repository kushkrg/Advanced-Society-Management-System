<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
$db = db();

function validate_resident(PDO $db, array $post, ?int $excludeId = null, bool $isEdit = false): array {
    $data = [
        'name' => trim($post['name'] ?? ''),
        'email' => trim($post['email'] ?? ''),
        'phone' => trim($post['phone'] ?? ''),
        'flat_id' => ($post['flat_id'] ?? '') !== '' ? (int)$post['flat_id'] : null,
        'resident_type' => ($post['resident_type'] ?? 'owner') === 'tenant' ? 'tenant' : 'owner',
        'move_in_date' => trim($post['move_in_date'] ?? '') ?: null,
        'is_active' => isset($post['is_active']) ? (int)$post['is_active'] : 1,
        'password' => (string)($post['password'] ?? ''),
    ];
    $errors = [];
    if (strlen($data['name']) < 2) $errors[] = 'Resident name is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($data['phone'] !== '' && !preg_match('/^[0-9+\-\s]{7,15}$/', $data['phone'])) $errors[] = 'Enter a valid phone number.';
    if (!$isEdit && strlen($data['password']) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($isEdit && $data['password'] !== '' && strlen($data['password']) < 6) $errors[] = 'New password must be at least 6 characters.';
    if (!$errors) {
        $sql = 'SELECT id FROM users WHERE email = ?' . ($excludeId ? ' AND id <> ?' : '');
        $stmt = $db->prepare($sql);
        $stmt->execute($excludeId ? [$data['email'], $excludeId] : [$data['email']]);
        if ($stmt->fetch()) $errors[] = 'This email is already registered.';
    }
    return [$data, $errors];
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'resident'");
$stmt->execute([$id]);
$res = $stmt->fetch();
if (!$res) { set_flash('error', 'Resident not found.'); redirect('residents.php'); }
$errors = []; $old = $res;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf('residents.php');
    [$data, $errors] = validate_resident($db, $_POST, $id, true);
    if (!$errors) {
        $db->prepare('UPDATE users SET name = ?, email = ?, phone = ?, flat_id = ?, resident_type = ?, move_in_date = ?, is_active = ? WHERE id = ?')
           ->execute([$data['name'], $data['email'], $data['phone'] ?: null, $data['flat_id'], $data['resident_type'], $data['move_in_date'], $data['is_active'], $id]);
        if ($data['password'] !== '') {
            $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($data['password'], PASSWORD_DEFAULT), $id]);
        }
        // Keep the resident's own session flat in sync if they're the one editing (admin session unaffected)
        set_flash('success', 'Resident updated.');
        redirect('resident-view.php?id=' . $id);
    }
    $old = $_POST;
}
$flats = $db->query('SELECT * FROM flats ORDER BY block_name, CAST(flat_no AS UNSIGNED)')->fetchAll();
$pageTitle = 'Edit Resident';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Edit Resident</h1><p><?= e($res['name']) ?></p></div>
<?php $submitLabel = 'Update Resident'; $isEdit = true; require __DIR__ . '/includes/resident-form.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
