<?php
declare(strict_types=1);

ob_start();

// ФІКС: db.php всередині try-catch, щоб помилка підключення не обходила обробник
try {
    require_once 'db.php';
} catch (\Throwable $e) {
    error_log("DB init error: " . $e->getMessage());
}

// ==========================================
// БРОНЯ 1: CSP NONCE
// ==========================================
$nonce = base64_encode(random_bytes(16));
define('CSP_NONCE', $nonce);

// ФІКС КРИТИЧНИЙ: $$csp → $csp (подвійний $ — це змінна-змінна, CSP-заголовок ніколи не відправлявся!)
// ФІКС: виправлено всі URL (повні домени, без typo)
// ФІКС: додано OneSignal у script-src та connect-src
// ФІКС: wss://://pusher.com → wss://ws-eu.pusher.com (typo + правильний домен)
$csp = "default-src 'self'; " .
       "script-src 'self' 'nonce-{$nonce}' https://js.pusher.com https://cdn.onesignal.com; " .
       "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
       "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
       "img-src 'self' data:; " .
       "connect-src 'self' wss://ws-eu.pusher.com https://js.pusher.com https://onesignal.com; " .
       "object-src 'none'; " .
       "base-uri 'self';";

header("Content-Security-Policy: " . $csp);
header('Content-Type: text/html; charset=utf-8');

// ==========================================
// БРОНЯ 2: OWASP HTTP HEADERS
// ==========================================
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), camera=(), microphone=()');

// ==========================================
// ЗАПУСК КОНТРОЛЕРА З ОБРОБКОЮ ПОМИЛОК
// ==========================================
try {
    \Controllers\HomeController::index();
} catch (\Throwable $e) {
    error_log("Critical Error in index.php: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

    if (ob_get_length()) ob_clean();

    http_response_code(500);

    ?>
    <!DOCTYPE html>
    <html lang="uk">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>Помилка 500 - Сервер недоступний</title>
        <style nonce="<?= CSP_NONCE ?>">
            body {
                margin: 0; padding: 0;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                background-color: #f8fafc; color: #334155;
                display: flex; align-items: center; justify-content: center;
                min-height: 100vh; text-align: center;
            }
            .error-card {
                background: #ffffff; padding: 40px 30px;
                border-radius: 24px;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
                max-width: 400px; width: 90%;
                border: 1px solid #f1f5f9;
            }
            .icon-wrapper {
                display: inline-flex; align-items: center; justify-content: center;
                width: 72px; height: 72px;
                background: #fee2e2; border-radius: 50%;
                margin-bottom: 20px; color: #ef4444;
            }
            .error-code { font-size: 72px; font-weight: 900; color: #ef4444; margin: 0; line-height: 1; letter-spacing: -2px; }
            .error-title { font-size: 22px; font-weight: 800; margin: 15px 0 10px; color: #0f172a; }
            .error-desc { font-size: 15px; color: #64748b; line-height: 1.6; margin-bottom: 30px; }
            .btn-refresh {
                display: inline-block; background-color: #3b82f6; color: #ffffff;
                text-decoration: none; padding: 14px 28px;
                border-radius: 14px; font-weight: 700; font-size: 15px;
                transition: background-color 0.2s;
                width: 100%; box-sizing: border-box;
            }
            .btn-refresh:hover { background-color: #2563eb; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="icon-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                    <path d="M12 9v4"></path>
                    <path d="M12 17h.01"></path>
                </svg>
            </div>
            <h1 class="error-code">500</h1>
            <h2 class="error-title">Серверна помилка</h2>
            <p class="error-desc">Наші інженери вже отримали сповіщення. Будь ласка, зачекайте 1-2 хвилини перед тим, як намагатися знову.</p>
            <a href="/" class="btn-refresh">Повернутися на головну</a>
        </div>
    </body>
    </html>
    <?php
}
