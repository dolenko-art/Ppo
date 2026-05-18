<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    
    <meta name="csrf-token" content="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? '')) ?>">
    <meta name="ppo-id" content="<?= htmlspecialchars((string)($_SESSION['ppo_id'] ?? '')) ?>">
    <meta name="pusher-key" content="<?= htmlspecialchars((string)getenv('PUSHER_KEY')) ?>">
    <meta name="pusher-cluster" content="<?= htmlspecialchars((string)(getenv('PUSHER_CLUSTER') ?: 'eu')) ?>">
    
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ACTION">
    
    <?php
        // Безпечна генерація OneSignal ID
        $os_user_id = '';
        if (!empty($_SESSION['user_id'])) {
            $secret = (string)getenv('APP_SECRET');
            if (!empty($secret)) {
                $os_user_id = htmlspecialchars(hash_hmac('sha256', (string)$_SESSION['user_id'], $secret));
            }
        }

        // 🔥 ФІКС ФАТАЛЬНОЇ ПОМИЛКИ: Безпечна перевірка константи для старих сторінок
        $safe_nonce = defined('CSP_NONCE') ? CSP_NONCE : '';
        
        // Скидання кешу для тестування
        $v_time = time(); 
    ?>
    <meta name="onesignal-app-id" content="<?= htmlspecialchars((string)getenv('ONESIGNAL_APP_ID')) ?>">
    <meta name="onesignal-user-id" content="<?= $os_user_id ?>">
    
    <title><?= htmlspecialchars($title ?? 'ACTION:Прозора Спілка', ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Syne:wght@700;800&family=DM+Mono&display=swap" rel="stylesheet" nonce="<?= $safe_nonce ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" nonce="<?= $safe_nonce ?>">

    <script src="https://js.pusher.com/8.4.0/pusher.min.js" nonce="<?= $safe_nonce ?>"></script>
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer nonce="<?= $safe_nonce ?>"></script>
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?= $v_time ?>" nonce="<?= $safe_nonce ?>">
    <script src="assets/js/theme-init.js" nonce="<?= $safe_nonce ?>"></script>
</head>
<body>

    <?php if (!empty($_SESSION['toast_msg'])): ?>
        <div id="sessionToastData" data-msg="<?= htmlspecialchars((string)$_SESSION['toast_msg'], ENT_QUOTES, 'UTF-8') ?>" class="d-none"></div>
        <?php unset($_SESSION['toast_msg']); ?>
    <?php endif; ?>

    <div id="toast" class="custom-toast-container"></div>

    <?php 
        $header_path = __DIR__ . '/partials/header.php';
        if (file_exists($header_path)) include $header_path; 
    ?>
    
    <?php
        if (!empty($view_path)) {
            $full_view_path = __DIR__ . '/' . $view_path;
            if (file_exists($full_view_path)) {
                include $full_view_path;
            } else {
                echo "<div style='text-align:center; padding:50px; color:var(--txt-muted);'>Помилка завантаження макету: Файл не знайдено.</div>";
            }
        }
    ?>

    <script src="assets/js/app.js?v=<?= $v_time ?>" nonce="<?= $safe_nonce ?>"></script>
    
</body>
</html>
