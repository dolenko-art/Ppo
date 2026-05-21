# 📧 API INTEGRATION GUIDE

## Quick Start

```php
require_once 'db.php';

// 🔥 Авторизація обов'язкова
\Core\Middleware::auth();

// 🔥 CSRF захист
\Core\Middleware::csrf();

// 🔥 Rate limiting
\Core\Middleware::rateLimit(100, 3600); // 100 requests per hour

// 🔥 Валідація
if (!\Core\Validator::validate($_POST, ['name' => 'required|text:1,100'])) {
    exit(\Core\Response::validationError(\Core\Validator::errors()));
}

// 🔥 Безпечна операція з БД
$user = \Core\DB::fetch("SELECT id, name FROM users WHERE id = ?", [$user_id]);

// 🔥 Логування
\Core\Logger::info("User accessed", ['user_id' => $user_id]);

// 🔥 Відповідь
exit(\Core\Response::success($user));
```

---

## Core Classes Reference

### \Core\Validator
```php
// Single validation
\Core\Validator::email($email);
\Core\Validator::phone($phone);
\Core\Validator::password($password);
\Core\Validator::text($text, $min, $max);
\Core\Validator::integer($value, $min, $max);
\Core\Validator::date($date); // YYYY-MM-DD
\Core\Validator::datetime($datetime); // YYYY-MM-DD HH:MM:SS
\Core\Validator::regex($value, '/pattern/');
\Core\Validator::unique('users', 'email', 'test@test.com');
\Core\Validator::exists('users', 'id', 1);

// Batch validation
$rules = [
    'email' => 'required|email',
    'password' => 'required|password',
    'age' => 'integer:0,120|nullable'
];
\Core\Validator::validate($_POST, $rules);
if (!\Core\Validator::validate(...)) {
    $errors = \Core\Validator::errors();
}
```

### \Core\Logger
```php
// Log levels
\Core\Logger::critical($message, $context);
\Core\Logger::error($message, $context);
\Core\Logger::warning($message, $context);
\Core\Logger::info($message, $context);
\Core\Logger::debug($message, $context); // Only in DEBUG mode

// Security logging
\Core\Logger::security('Suspicious activity', \Core\Logger::WARNING, [
    'user_id' => 123,
    'action' => 'login_failed',
    'attempts' => 5
]);

// Utilities
$recent_logs = \Core\Logger::getLogs(100);
\Core\Logger::cleanup(30); // Remove logs older than 30 days
```

### \Core\Response
```php
// Success
\Core\Response::success($data, 'Message', 200);

// Errors
\Core\Response::error('Error message', 400);
\Core\Response::unauthorized('Please login');
\Core\Response::forbidden('No access');
\Core\Response::notFound('Not found');
\Core\Response::rateLimited();
\Core\Response::serverError();

// Validation
\Core\Response::validationError(['field' => 'Error message']);
```

### \Core\Middleware
```php
// Auth & Authorization
\Core\Middleware::auth(); // Require login
\Core\Middleware::role('head'); // Require specific role

// Security
\Core\Middleware::csrf(); // Check CSRF token
\Core\Middleware::rateLimit(100, 3600); // Rate limit
\Core\Middleware::cors(['https://example.com']); // CORS

// Data
\Core\Middleware::jsonOnly(); // Require JSON content type
```

### \Core\Security
```php
// Encryption
$encrypted = \Core\Security::encrypt('sensitive data');
$decrypted = \Core\Security::decrypt($encrypted);

// Passwords
$hash = \Core\Security::hashPassword('password');
$is_valid = \Core\Security::verifyPassword('password', $hash);

// Tokens
$token = \Core\Security::generateToken(32); // 64 hex chars
$key = \Core\Security::generateKey(); // Store in .env

// CSRF
$is_valid = \Core\Security::verifyCsrfToken($token1, $token2);
```

### \Core\DB
```php
// Fetch
$user = \Core\DB::fetch("SELECT * FROM users WHERE id = ?", [$id]);
$users = \Core\DB::fetchAll("SELECT * FROM users LIMIT ? OFFSET ?", [$limit, $offset]);
$count = \Core\DB::fetchColumn("SELECT COUNT(*) FROM users");

// Modify
\Core\DB::query("UPDATE users SET name = ? WHERE id = ?", [$name, $id]);
$user_id = \Core\DB::insert('users', ['name' => 'John', 'email' => 'john@example.com']);

// Debug
echo \Core\DB::getQueryCount();
var_dump(\Core\DB::getQueryLog());
```

### \Core\Auth
```php
// Check
\Core\Auth::loggedIn(); // bool
\Core\Auth::userId(); // int|null
\Core\Auth::ppoId(); // int|null
\Core\Auth::role(); // string|null
\Core\Auth::status(); // string|null
\Core\Auth::user(); // array|null

// Permissions
\Core\Auth::hasRole('head'); // bool
\Core\Auth::hasStatus('member'); // bool
\Core\Auth::isLeadership(); // bool

// CSRF
$token = \Core\Auth::getCsrfToken();
\Core\Auth::verifyCsrfToken($token); // bool

// Session
\Core\Auth::logout(); // bool
\Core\Auth::regenerateSessionId(); // bool
```

---

## Common Patterns

### Pattern 1: List with Pagination
```php
\Core\Middleware::auth();
\Core\Middleware::rateLimit();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$items = \Core\DB::fetchAll(
    "SELECT * FROM items WHERE user_id = ? LIMIT ? OFFSET ?",
    [\Core\Auth::userId(), $limit, $offset]
);

$total = \Core\DB::fetchColumn(
    "SELECT COUNT(*) FROM items WHERE user_id = ?",
    [\Core\Auth::userId()]
);

exit(\Core\Response::success([
    'items' => $items,
    'total' => $total,
    'page' => $page,
    'pages' => ceil($total / $limit)
]));
```

### Pattern 2: Create with Validation
```php
\Core\Middleware::auth();
\Core\Middleware::csrf();
\Core\Middleware::rateLimit(50, 3600);

if (!\Core\Validator::validate($_POST, [
    'name' => 'required|text:1,100',
    'email' => 'required|email|unique:users,email',
    'password' => 'required|password'
])) {
    exit(\Core\Response::validationError(\Core\Validator::errors()));
}

$id = \Core\DB::insert('users', [
    'name' => $_POST['name'],
    'email' => $_POST['email'],
    'password' => \Core\Security::hashPassword($_POST['password'])
]);

\Core\Logger::info("User created", ['user_id' => $id]);
exit(\Core\Response::success(['id' => $id], 'Created', 201));
```

### Pattern 3: Update with Authorization
```php
\Core\Middleware::auth();
\Core\Middleware::csrf();

$id = (int)($_POST['id'] ?? 0);
if (!\Core\Validator::integer($id, 1)) {
    exit(\Core\Response::validationError(['id' => 'Invalid']));
}

// Get resource
$item = \Core\DB::fetch("SELECT user_id FROM items WHERE id = ?", [$id]);
if (!$item) exit(\Core\Response::notFound());

// Check authorization
if ($item['user_id'] !== \Core\Auth::userId() && !\Core\Auth::isLeadership()) {
    \Core\Logger::security('Unauthorized update', \Core\Logger::WARNING, ['item_id' => $id]);
    exit(\Core\Response::forbidden());
}

// Update
\Core\DB::query("UPDATE items SET name = ? WHERE id = ?", [$_POST['name'], $id]);
\Core\Logger::info("Item updated", ['item_id' => $id]);
exit(\Core\Response::success(null, 'Updated'));
```

---

## Security Checklist

✅ Always use `Middleware::auth()` for protected endpoints
✅ Always use `Middleware::csrf()` for mutations
✅ Always validate with `Validator::validate()`
✅ Always use prepared statements in `DB` queries
✅ Always check authorization before modify/delete
✅ Always log security events
✅ Always escape output in JSON with `json_encode()`
✅ Always use HTTPS (enforced in production)
✅ Always handle exceptions and return proper HTTP codes
✅ Always implement rate limiting for APIs

---

**Version:** 2.0  
**Last Updated:** 2026-05-21  
**Status:** Production Ready
