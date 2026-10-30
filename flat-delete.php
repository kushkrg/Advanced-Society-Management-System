<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('flats.php');
verify_csrf('flats.php');
$id = (int)($_POST['id'] ?? 0);
$resCount = db()->prepare('SELECT COUNT(*) FROM users WHERE flat_id = ?');
$resCount->execute([$id]);
$billCount = db()->prepare('SELECT COUNT(*) FROM bills WHERE flat_id = ?');
$billCount->execute([$id]);
if ((int)$resCount->fetchColumn() > 0 || (int)$billCount->fetchColumn() > 0) {
    set_flash('error', 'This flat has residents or bills, so it cannot be deleted. Unassign the residents first.');
    redirect('flats.php');
}
db()->prepare('DELETE FROM flats WHERE id = ?')->execute([$id]);
set_flash('success', 'Flat deleted.');
redirect('flats.php');
