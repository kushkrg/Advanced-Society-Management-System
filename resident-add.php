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

$errors = []; $old = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf('resident-add.php');
    [$data, $errors] = validate_resident($db, $_POST);
    if (!$errors) {
        $db->prepare("INSERT INTO users (name, email, phone, password, role, flat_id, resident_type, move_in_date) VALUES (?, ?, ?, ?, 'resident', ?, ?, ?)")
           ->execute([$data['name'], $data['email'], $data['phone'] ?: null, password_hash($data['password'], PASSWORD_DEFAULT), $data['flat_id'], $data['resident_type'], $data['move_in_date']]);
        set_flash('success', 'Resident added. They can now login with their email and password.');
        redirect('residents.php');
    }
    $old = $_POST;
}
$flats = $db->query('SELECT * FROM flats ORDER BY block_name, CAST(flat_no AS UNSIGNED)')->fetchAll();
$pageTitle = 'Add Resident';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Add Resident</h1><p>Create a resident login and assign their flat</p></div>
<?php $submitLabel = 'Add Resident'; $isEdit = false; require __DIR__ . '/includes/resident-form.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
