<?php
// Підключаємо ядро (сесії, автозавантажувач та підключення до БД)
require_once 'db.php';

// Перевірка авторизації
if (!\Core\Auth::loggedIn()) { 
    header("Location: login.php"); 
    exit; 
}

// Отримуємо ID та передаємо керування Контролеру
$report_id = (int)($_GET['id'] ?? 0);
\Controllers\DocumentController::viewAuditReport($report_id);
