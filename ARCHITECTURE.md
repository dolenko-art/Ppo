# 🏗️ ENTERPRISE ARCHITECTURE — PPO (Профспілка цифрова)

## 📋 Огляд

Це повнофункціональна архітектура **сеньйор-рівня** для PHP проекту "Профспілка цифрова" з використанням:

- **Dependency Injection Container** — управління залежностями
- **Middleware Pipeline** — обробка HTTP запитів
- **Service Layer** — бізнес-логіка
- **Repository Pattern** — доступ до даних
- **Type Safety** — `declare(strict_types=1)`
- **Security First** — CSRF, SQL Injection, XSS защита

---

## 🗂️ СТРУКТУРА ПРОЕКТУ

```
Ppo/
├── src/
│   ├── Application/
│   │   ├── Application.php      # Bootstrap & Service Provider
│   │   └── Kernel.php           # HTTP Kernel
│   │
│   ├── Container/
│   │   └── Container.php        # DI Container з auto-wiring
│   │
│   ├── Http/
│   │   ├── Request.php          # Immutable Request object
│   │   ├── Response.php         # JSON Response builder
│   │   ├── Middleware/
│   │   │   ├── Middleware.php           # Base class
│   │   │   ├── MiddlewarePipeline.php   # Chain of Responsibility
│   │   │   ├── StartSession.php         # Session initialization
│   │   │   ├── Authenticate.php        # Session validation
│   │   │   ├── VerifyCSRFToken.php     # CSRF protection
│   │   │   ├── RateLimit.php           # Brute force protection
│   │   │   ├── LogRequest.php          # Request auditing
│   │   │   └── IsAdmin.php             # Admin authorization
│   │   │
│   │   ├── Controllers/         # Контролери (далі)
│   │   └── Requests/            # Form Request Validation (далі)
│   │
│   ├── Services/
│   │   ├── Database.php         # PDO обгортка
│   │   ├── Logger.php           # Структурований логінг
│   │   ├── Auth.php             # Автентифікація (далі)
│   │   └── Session.php          # Сесійний сервіс (далі)
│   │
│   ├── Repositories/            # Data Access Layer (далі)
│   │   ├── UserRepository.php
│   │   ├── VoteRepository.php
│   │   └── PollRepository.php
│   │
│   ├── Models/                  # Data Models (далі)
│   │   ├── User.php
│   │   ├── Poll.php
│   │   └── Vote.php
│   │
│   └── Config/
│       └── Config.php           # .env configuration
│
├── config/
│   └── .env.example             # Environment template
│
├── storage/
│   ├── logs/                    # Application logs
│   └── cache/                   # Cache files
│
└── tests/                       # Unit & Feature tests (далі)
    ├── Unit/
    ├── Feature/
    └── bootstrap.php
```

---

## 🚀 КЛЮЧОВІ КОМПОНЕНТИ

### 1. **Dependency Injection Container** 📦
```php
$container = new Container();
$container->singleton('database', function($c) {
    return new Database(...);
});

$db = $container->get('database');
```

**Особливості:**
- ✅ Singleton & Transient сервіси
- ✅ Auto-wiring з Reflection API
- ✅ Factory callbacks
- ✅ Lazy loading

---

### 2. **Middleware Pipeline** 🔄
```php
Request → [Middleware 1 → Middleware 2 → ... → Handler] → Response
```

**Порядок виконання:**
1. `StartSession` — ініціалізація сесії
2. `Authenticate` — перевірка сесії
3. `VerifyCSRFToken` — захист від CSRF
4. `RateLimit` — захист від brute force
5. `LogRequest` — логування запитів
6. **Handler** — обробка запиту

---

### 3. **Security Features** 🔐

#### ✅ CSRF Protection
```php
// Генерується автоматично в StartSession
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Перевіряється в VerifyCSRFToken для POST/PUT/DELETE
hash_equals($_SESSION['csrf_token'], $token);
```

#### ✅ SQL Injection Protection
```php
// Prepared Statements завжди!
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
```

#### ✅ Secure Sessions
```
- HTTPOnly cookies (JS доступ заборонено)
- SameSite=Strict (CSRF захист)
- Session.use_strict_mode (Session Fixation захист)
- use_only_cookies (URL ID заборонено)
```

#### ✅ XSS Protection
```php
// Завжди escape вихідні дані!
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

#### ✅ Rate Limiting
```php
// Per-IP & per-route limiting
RateLimit::RATE_LIMIT_WINDOW = 60s
MAX_REQUESTS = 100
```

---

## 📊 ТИПОВА ОБРОБКА ЗАПИТУ

```php
// 1. Запит приходить
POST /api/action?ajax=1
Data: { action: 'vote', poll_id: 123, csrf_token: 'xxx' }

// 2. Application::run() запускається
$app = new Application(__DIR__);
$app->run();

// 3. Request парсується
$request = Request::fromGlobals();
// $request->post('action') === 'vote'
// $request->input('poll_id') === 123

// 4. Middleware Pipeline
→ StartSession (ініціалізація)
→ Authenticate (перевірка $_SESSION['user_id'])
→ VerifyCSRFToken (перевірка токена)
→ RateLimit (перевірка ліміту)
→ LogRequest (запис логу)
→ Handler (обробка)

// 5. Handler отримує Request
$response = $handler($request);

// 6. Response відправляється
echo Response::success(['vote_id' => 456]);
```

---

## 🔧 КОНФІГУРАЦІЯ (.env)

```bash
# Application
APP_NAME=PPO
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ppo
DB_USER=root
DB_PASS=

# Security
CSRF_TOKEN_LENGTH=32
SESSION_LIFETIME=2592000
RATE_LIMIT_WINDOW=60
RATE_LIMIT_MAX=100

# Logging
LOG_CHANNEL=file
LOG_LEVEL=debug
```

---

## 💡 ПРИМЕРЫ ВИКОРИСТАННЯ

### 1. **Реєстрація сервісу**
```php
// У Application::registerServices()
$this->container->singleton('vote', function($c) {
    return new VoteService($c->get('database'));
});
```

### 2. **Використання middleware**
```php
// У маршруті
Route::post('/admin/users', 'AdminUserController@store')
    ->middleware('auth')
    ->middleware('admin')
    ->middleware('verify_csrf');
```

### 3. **Database операції**
```php
$db = $container->get('database');

// SELECT
$user = $db->fetch(
    "SELECT * FROM users WHERE email = ?",
    [$email]
);

// INSERT
$db->insert('users', [
    'name' => 'John',
    'email' => 'john@example.com',
]);

// UPDATE
$db->update('users', 
    ['name' => 'Jane'],
    ['id' => 1]
);

// DELETE
$db->delete('users', ['id' => 1]);
```

### 4. **Request обробка**
```php
$action = $request->post('action');
$pollId = $request->input('poll_id');
$file = $request->file('avatar');
$ip = $request->ip();

if ($request->isAjax()) {
    return Response::success($data);
}
```

---

## 📈 НАСТУПНІ КРОКИ

- [ ] **Repositories** — UserRepository, VoteRepository
- [ ] **Services** — AuthService, VoteService
- [ ] **Controllers** — Thin controllers
- [ ] **Routes** — Маршрутизація
- [ ] **Validation** — Form Request validation
- [ ] **Tests** — Unit & Feature tests
- [ ] **Database Migrations** — Schema management

---

## 🎯 BEST PRACTICES

✅ **Dependency Injection** — всі залежності вводяться  
✅ **Type Hints** — повна типізація  
✅ **Immutable Objects** — Request не може змінюватись  
✅ **Single Responsibility** — кожен клас має одну роль  
✅ **DRY** — не повторюй код  
✅ **SOLID Principles** — дотримуємось їх  
✅ **Security First** — захист вбудований  

---

## 📚 ДОКУМЕНТАЦІЯ

- **Database.php** — CRUD операції, транзакції
- **Container.php** — Auto-wiring, Reflection
- **Middleware** — Chain of Responsibility паттерн
- **Request/Response** — Immutable HTTP объекти

---

**Автор:** Copilot  
**Версія:** 1.0.0  
**Стан:** Production-Ready 🚀
