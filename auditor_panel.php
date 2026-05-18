<?php
// Підключаємо ядро
require_once 'db.php';

// 1. ПЕРЕВІРКА АВТОРИЗАЦІЇ
if (!\Core\Auth::loggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$ppo_id = (int)$_SESSION['ppo_id'];

// 🛡️ Жорстка перевірка ролі Ревізора
$user = \Core\DB::fetch("SELECT full_name, role FROM users WHERE id = ?", [$user_id]);
if (!$user || $user['role'] !== 'auditor') {
    $_SESSION['toast_msg'] = "❌ Доступ заборонено. Сторінка тільки для Ревізійної комісії.";
    header("Location: index.php");
    exit;
}

// Дешифруємо ім'я ревізора
$auditor_name = \Core\Security::decrypt($user['full_name']) ?: $user['full_name'];

// ==========================================
// 1. ТРАНЗАКЦІЇ (Очікують на підтвердження)
// ==========================================

// Банківські (Безготівка)
$bank_unverified = [];
try {
    $bank_unverified = \Core\DB::fetchAll("
        SELECT id, time, amount, description, comment 
        FROM bank_transactions 
        WHERE ppo_id = ? AND is_audited = 0 
        ORDER BY time DESC
    ", [$ppo_id]);
} catch (\Exception $e) {
    error_log("Audit Error (Bank): " . $e->getMessage());
}

// Готівкові (Каса)
$cash_unverified = [];
try {
    $cash_unverified = \Core\DB::fetchAll("
        SELECT id, created_at, amount, reason as description 
        FROM cash_transactions 
        WHERE ppo_id = ? AND is_audited = 0 
        ORDER BY created_at DESC
    ", [$ppo_id]);
} catch (\Exception $e) {
    error_log("Audit Error (Cash): " . $e->getMessage());
}

// ==========================================
// 2. РЕЄСТР МАЙНА (Інвентар)
// ==========================================
$assets = [];
try {
    $assets = \Core\DB::fetchAll("
        SELECT * FROM ppo_assets 
        WHERE ppo_id = ? 
        ORDER BY status ASC, id DESC
    ", [$ppo_id]);
} catch (\Exception $e) {
    error_log("Audit Error (Assets): " . $e->getMessage());
}

// ==========================================
// 3. АКТИ РЕВІЗІЇ (З ПАГІНАЦІЄЮ)
// ==========================================
$reports = [];
$limit = 10; // Кількість актів на одну сторінку
$current_page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($current_page - 1) * $limit;
$total_pages = 1;

try {
    // Спочатку дізнаємося загальну кількість актів для цієї ППО
    $total_reports = (int)\Core\DB::fetchColumn("SELECT COUNT(*) FROM audit_reports WHERE ppo_id = ?", [$ppo_id]);
    
    if ($total_reports > 0) {
        $total_pages = ceil($total_reports / $limit);
        
        // Витягуємо тільки ту частину, яка потрібна для поточної сторінки
        $reports = \Core\DB::fetchAll("
            SELECT * FROM audit_reports 
            WHERE ppo_id = ? 
            ORDER BY id DESC 
            LIMIT $limit OFFSET $offset
        ", [$ppo_id]);
    }
} catch (\Exception $e) {
    error_log("Audit Error (Reports): " . $e->getMessage());
}

// Підключаємо візуал
$title = 'Кабінет Ревізора';
$header_title = 'Ревізор';
$view_path = 'auditor_panel_view.php';
require_once 'views/layout.php';
