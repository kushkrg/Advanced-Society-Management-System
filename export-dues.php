<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
$db = db();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="society_dues_' . date('Y-m-d') . '.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['Flat', 'Flat Type', 'Resident', 'Mobile', 'Bill Month', 'Total', 'Paid', 'Due', 'Due Date', 'Status']);
$rows = $db->query("SELECT b.*, f.block_name, f.flat_no, f.flat_type,
    (SELECT u.name FROM users u WHERE u.flat_id = b.flat_id AND u.role = 'resident' AND u.is_active = 1 ORDER BY u.id LIMIT 1) AS res_name,
    (SELECT u.phone FROM users u WHERE u.flat_id = b.flat_id AND u.role = 'resident' AND u.is_active = 1 ORDER BY u.id LIMIT 1) AS res_phone
    FROM bills b JOIN flats f ON f.id = b.flat_id
    WHERE b.status IN ('unpaid','partial') ORDER BY f.block_name, CAST(f.flat_no AS UNSIGNED), b.bill_month")->fetchAll();
foreach ($rows as $r) {
    fputcsv($out, [$r['block_name'] . '-' . $r['flat_no'], $r['flat_type'], $r['res_name'] ?? '', $r['res_phone'] ?? '', date('M Y', strtotime($r['bill_month'] . '-01')), $r['total_amount'], $r['paid_amount'], (float)$r['total_amount'] - (float)$r['paid_amount'], $r['due_date'], $r['status']]);
}
fclose($out);
exit;
