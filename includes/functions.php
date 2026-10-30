<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header('Location: ' . $url); exit; }

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function verify_csrf(string $back = 'dashboard.php'): void {
    if (!isset($_POST['csrf_token']) || !hash_equals(csrf_token(), (string)$_POST['csrf_token'])) {
        set_flash('error', 'Invalid security token. Please try again.');
        redirect($back);
    }
}
function set_flash(string $t, string $m): void { $_SESSION['flash_' . $t] = $m; }
function flash(string $t): ?string {
    if (!empty($_SESSION['flash_' . $t])) { $m = $_SESSION['flash_' . $t]; unset($_SESSION['flash_' . $t]); return $m; }
    return null;
}

function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }
function require_login(): void { if (!current_user()) redirect('login.php'); }
function require_admin(): void {
    require_login();
    if (!is_admin()) { set_flash('error', 'Only society administrators can open that page.'); redirect('dashboard.php'); }
}
function initials(string $name): string {
    $out = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $p) $out .= strtoupper(mb_substr($p, 0, 1));
    return $out ?: 'R';
}

/* ---------------- Settings ---------------- */
function settings_all(PDO $db): array {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try { foreach ($db->query('SELECT skey, svalue FROM settings') as $row) $cache[$row['skey']] = $row['svalue']; }
        catch (Throwable $ex) { $cache = []; }
    }
    return $cache;
}
function setting(PDO $db, string $key, string $default = ''): string {
    $all = settings_all($db);
    return $all[$key] ?? $default;
}

/* ---------------- Money (INR, Indian grouping) ---------------- */
function inr(float $amount): string {
    $neg = $amount < 0;
    $amount = abs($amount);
    $parts = explode('.', number_format($amount, 2, '.', ''));
    $int = $parts[0];
    if (strlen($int) > 3) {
        $last3 = substr($int, -3);
        $rest = substr($int, 0, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $int = $rest . ',' . $last3;
    }
    $dec = ($parts[1] ?? '00') !== '00' ? '.' . $parts[1] : '';
    return ($neg ? '-₹' : '₹') . $int . $dec;
}

/* ---------------- Billing logic (shared + testable) ---------------- */
const FLAT_TYPES = ['1BHK', '2BHK', '3BHK', '4BHK'];

/** Charge breakdown for one flat for one month. */
function compute_bill_amounts(string $flatType, array $rates, int $vehicleCount): array {
    $maintenance = (float)($rates['rate_' . strtolower($flatType)] ?? 0);
    $water = (float)($rates['water_charge'] ?? 0);
    $parking = (float)($rates['parking_rate'] ?? 0) * max(0, $vehicleCount);
    return [
        'maintenance' => $maintenance,
        'water' => $water,
        'parking' => $parking,
        'total' => $maintenance + $water + $parking,
    ];
}

function bill_status_for(float $paid, float $total): string {
    if ($total > 0 && $paid >= $total) return 'paid';
    if ($paid > 0) return 'partial';
    return 'unpaid';
}

/** Flat label like "A-101". */
function flat_label(array $flat): string {
    return $flat['block_name'] . '-' . $flat['flat_no'];
}

/** Resident(s) living in a flat - primary resident = first active resident user. */
function flat_resident(PDO $db, int $flatId): ?array {
    $stmt = $db->prepare("SELECT * FROM users WHERE flat_id = ? AND role = 'resident' AND is_active = 1 ORDER BY id LIMIT 1");
    $stmt->execute([$flatId]);
    return $stmt->fetch() ?: null;
}

function flat_is_occupied(PDO $db, int $flatId): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE flat_id = ? AND role = 'resident' AND is_active = 1");
    $stmt->execute([$flatId]);
    return (int)$stmt->fetchColumn() > 0;
}

function flat_vehicle_count(PDO $db, int $flatId): int {
    $stmt = $db->prepare('SELECT COUNT(*) FROM vehicles v JOIN users u ON u.id = v.user_id WHERE u.flat_id = ?');
    $stmt->execute([$flatId]);
    return (int)$stmt->fetchColumn();
}
