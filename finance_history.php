<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'db.php';

// ==========================================
// 🛡️ 1. БЕЗПЕКА ТА АВТОРИЗАЦІЯ
// ==========================================
if (!\Core\Auth::loggedIn()) { 
    header("Location: login.php"); 
    exit; 
}

$user_id = (int)$_SESSION['user_id'];
$ppo_id  = (int)$_SESSION['ppo_id'];

// Перевірка доступу (тільки для членів та адмінів)
$user = \Core\DB::fetch("SELECT status FROM users WHERE id = ?", [$user_id]);
if (!$user || !in_array($user['status'], ['member', 'admin'])) {
    $_SESSION['toast_msg'] = "❌ Фінансова виписка доступна лише членам ППО.";
    header("Location: index.php"); 
    exit;
}

// ==========================================
// ⚙️ 2. САНІТИЗАЦІЯ ПАРАМЕТРІВ ПАГІНАЦІЇ
// ==========================================
$limit = 20;

$active_fin_tab = isset($_GET['tab']) && is_scalar($_GET['tab']) ? (string)$_GET['tab'] : 'cash';
$p_cash = max(1, (int)($_GET['p_cash'] ?? 1));
$p_bank = max(1, (int)($_GET['p_bank'] ?? 1));

// Захист від Array Payload у $_GET['acc']
$selected_acc = isset($_GET['acc']) && is_scalar($_GET['acc']) ? (string)$_GET['acc'] : 'all';

// ==========================================
// 💵 3. ЛОГІКА КАСИ (ГОТІВКА)
// ==========================================
$cash_offset = ($p_cash - 1) * $limit;

// 🛡️ ФІКС: Ніяких SELECT *, вибираємо лише безпечні колонки
$cash_history = \Core\DB::fetchAll("
    SELECT c.id, c.amount_cents, c.type, c.description, c.created_at, u.full_name 
    FROM cash_transactions c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.ppo_id = ? 
    ORDER BY c.created_at DESC 
    LIMIT $limit OFFSET $cash_offset
", [$ppo_id]);

// Дешифруємо імена безпечно
if (!empty($cash_history)) {
    $has_security = class_exists('\Core\Security');
    foreach ($cash_history as &$tx) {
        if (!empty($tx['full_name']) && $has_security) {
            $decrypted = \Core\Security::decrypt($tx['full_name']);
            $tx['full_name'] = $decrypted !== false ? $decrypted : $tx['full_name'];
        }
    }
    // 🛡️ ФІКС: Знищуємо посилання, щоб уникнути перезапису останнього елемента!
    unset($tx); 
}

$cash_total_count = (int)\Core\DB::fetchColumn("SELECT COUNT(id) FROM cash_transactions WHERE ppo_id = ?", [$ppo_id]);

// ==========================================
// 💳 4. ЛОГІКА БАНКУ (БЕЗГОТІВКА)
// ==========================================
$mono_accounts = \Core\DB::fetchAll("SELECT external_id, name FROM bank_accounts WHERE ppo_id = ? AND is_active = 1", [$ppo_id]);

$bank_offset = ($p_bank - 1) * $limit;
$bank_params = [$ppo_id];
$bank_where = "WHERE t.ppo_id = ?";

if ($selected_acc !== 'all') {
    $bank_where .= " AND t.account_id = ?";
    $bank_params[] = $selected_acc;
}

// 🛡️ ФІКС: Ніяких SELECT *, вибираємо лише публічні колонки
$bank_history = \Core\DB::fetchAll("
    SELECT t.id, t.amount_cents, t.description, t.time, t.mcc, a.name as account_name 
    FROM bank_transactions t 
    LEFT JOIN bank_accounts a ON t.account_id = a.external_id 
    $bank_where 
    ORDER BY t.time DESC 
    LIMIT $limit OFFSET $bank_offset
", $bank_params);

$bank_total_count = (int)\Core\DB::fetchColumn("SELECT COUNT(t.id) FROM bank_transactions t $bank_where", $bank_params);

// ==========================================
// 🖼️ 5. ПІДКЛЮЧЕННЯ ІНТЕРФЕЙСУ
// ==========================================
$title = 'Історія транзакцій';
$header_title = 'Історія транзакцій';
$view_path = 'finance_history_view.php';
require_once 'views/layout.php';
