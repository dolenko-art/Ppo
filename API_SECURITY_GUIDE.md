# API Security Guide

## 🔒 Основні принципи

### 1. Завжди валідуйте вхідні дані
```php
$user_id = (int)$_GET['id'];  // ✅ ДОБРЕ
$user_id = $_GET['id'];       // ❌ ПОГАНО
```

### 2. Використовуйте Prepared Statements
```php
// ✅ ДОБРЕ
\Core\DB::fetch("SELECT * FROM users WHERE id = ?", [$id]);

// ❌ ПОГАНО
\Core\DB::fetch("SELECT * FROM users WHERE id = $id");
```

### 3. Екранујте весь вивід
```php
// ✅ ДОБРЕ
echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

// ❌ ПОГАНО
echo $name;
```

### 4. Перевіряйте авторизацію
```php
// ✅ ДОБРЕ
if ($user_id !== $resource['owner_id']) {
    http_response_code(403);
    exit;
}

// ❌ ПОГАНО
// Без перевірки власника
```

### 5. Логуйте все підозріле
```php
// ✅ ДОБРЕ
error_log("Security Alert: Unauthorized access attempt by user {$user_id}");

// ❌ ПОГАНО
// Без логування
```

---

## 🛡️ Паттерни для кожного типу операції

### GET запити (Читання)
```php
<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// 1. Перевірка авторизації
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

// 2. Валідація параметрів
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'msg' => 'Invalid ID']);
    exit;
}

// 3. Rate limiting
$rate_limiter = new \Core\RateLimiter($_SESSION['user_id']);
if (!$rate_limiter->isAllowed(100, 3600)) {
    http_response_code(429);
    echo json_encode(['success' => false]);
    exit;
}

// 4. Отримати дані
try {
    $data = \Core\DB::fetch("SELECT * FROM resources WHERE id = ?", [$id]);
    if (!$data) {
        http_response_code(404);
        echo json_encode(['success' => false]);
        exit;
    }
    
    // 5. Екранувати вивід
    echo json_encode([
        'success' => true,
        'data' => htmlspecialchars($data)
    ]);
} catch (\Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false]);
}
```

### POST запити (Запис)
```php
<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// 1. Перевірка авторизації
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

// 2. Перевірка методу
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

// 3. CSRF перевірка
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Invalid CSRF token']);
    exit;
}

// 4. Валідація даних
$name = trim((string)($_POST['name'] ?? ''));
if (empty($name) || mb_strlen($name, 'UTF-8') > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'msg' => 'Invalid name']);
    exit;
}

// 5. Rate limiting для POST
$rate_limiter = new \Core\RateLimiter($_SESSION['user_id']);
if (!$rate_limiter->isAllowed(10, 3600)) { // Більш суворий для запису
    http_response_code(429);
    echo json_encode(['success' => false]);
    exit;
}

// 6. Виконати операцію
try {
    $result = \Core\DB::insert('resources', [
        'user_id' => $_SESSION['user_id'],
        'name' => $name
    ]);
    
    if ($result) {
        error_log("Resource created: {$result} by user {$_SESSION['user_id']}");
        echo json_encode(['success' => true, 'id' => $result]);
    } else {
        throw new \Exception('Insert failed');
    }
} catch (\Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false]);
}
```

### DELETE запити (Видалення)
```php
<?php
// 1. Перевірка авторизації
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

// 2. CSRF перевірка
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit;
}

// 3. Отримати ресурс
$id = (int)($_POST['id'] ?? 0);
$resource = \Core\DB::fetch("SELECT user_id FROM resources WHERE id = ?", [$id]);

// 4. АВТОРИЗАЦІЯ: перевірити власника
if ((int)$resource['user_id'] !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    error_log("Authorization error: User {$_SESSION['user_id']} tried to delete resource of {$resource['user_id']}");
    exit;
}

// 5. Видалити
\Core\DB::query("DELETE FROM resources WHERE id = ?", [$id]);
echo json_encode(['success' => true]);
```

---

## ✅ Чек-лист для кожного новоготу API endpoint'a

- [ ] Перевірка авторизації (401)
- [ ] Валідація параметрів (400)
- [ ] Rate limiting (429)
- [ ] CSRF token перевірка (для POST/DELETE)
- [ ] Роль-базована авторизація (403)
- [ ] Используются prepared statements
- [ ] Весь вивід екранований (htmlspecialchars)
- [ ] Логування для аудиту
- [ ] Try-catch для обробки помилок
- [ ] Тестування SQL injection
- [ ] Тестування XSS
- [ ] Тестування unauthorized access

---

**Версія:** 2.0  
**Дата:** 2026-05-21
