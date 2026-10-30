<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('residents.php');
verify_csrf('residents.php');
$id = (int)($_POST['id'] ?? 0);
db()->prepare("DELETE FROM users WHERE id = ? AND role = 'resident'")->execute([$id]);
set_flash('success', 'Resident account deleted. Their family members and vehicles were removed; flat bills remain for records.');
redirect('residents.php');
