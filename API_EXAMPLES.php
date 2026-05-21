<?php
/**
 * API Helper - Use cases for API endpoints
 * Показує як правильно користувати Core classes
 * ВЕРСІЯ: 2.0
 */

require_once 'db.php';

// ==========================================
// ПРИКЛАД 1: Безпечно отримати дані користувача
// ==========================================
function getUser($user_id) {
    // Перевірити авторизацію
    if (!\Core\Auth::loggedIn()) {
        return \Core\Response::unauthorized();
    }
    
    // Валідувати ID
    if (!\Core\Validator::integer($user_id, 1)) {
        return \Core\Response::validationError(['user_id' => 'Invalid user ID']);
    }
    
    // Отримати дані з безпечним запитом
    $user = \Core\DB::fetch("SELECT id, full_name, email FROM users WHERE id = ?", [$user_id]);
    
    if (!$user) {
        \Core\Logger::info("User not found: {$user_id}");
        return \Core\Response::notFound();
    }
    
    // Дешифрувати чутливі дані
    $user['full_name'] = \Core\Security::decrypt($user['full_name']) ?: 'Unknown';
    
    // Успішна відповідь
    return \Core\Response::success($user, 'User retrieved');
}

// ==========================================
// ПРИКЛАД 2: Безпечно створити ресурс
// ==========================================
function createPoll($data) {
    // Middleware: Auth check
    \Core\Middleware::auth();
    \Core\Middleware::rateLimit(50, 3600);
    \Core\Middleware::csrf();
    
    // Валідація
    $rules = [
        'title' => 'required|text:1,100',
        'description' => 'nullable|text:0,500',
        'end_date' => 'required|date'
    ];
    
    if (!\Core\Validator::validate($data, $rules)) {
        return \Core\Response::validationError(\Core\Validator::errors());
    }
    
    try {
        // Вставити дані
        $poll_id = \Core\DB::insert('polls', [
            'ppo_id' => \Core\Auth::ppoId(),
            'user_id' => \Core\Auth::userId(),
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'end_date' => $data['end_date'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Логувати успіх
        \Core\Logger::info("Poll created", ['poll_id' => $poll_id, 'user_id' => \Core\Auth::userId()]);
        
        return \Core\Response::success(['poll_id' => $poll_id], 'Poll created successfully', 201);
        
    } catch (\Exception $e) {
        \Core\Logger::error("Poll creation failed: " . $e->getMessage());
        return \Core\Response::serverError();
    }
}

// ==========================================
// ПРИКЛАД 3: Безпечно оновити дані
// ==========================================
function updateUserProfile($user_id, $data) {
    // Middleware
    \Core\Middleware::auth();
    \Core\Middleware::csrf();
    
    // Авторизація: може змінювати тільки свій профіль
    if (\Core\Auth::userId() !== (int)$user_id && !\Core\Auth::isLeadership()) {
        \Core\Logger::security('Unauthorized profile update attempt', \Core\Logger::WARNING, ['target_id' => $user_id]);
        return \Core\Response::forbidden();
    }
    
    // Валідація
    $rules = [
        'full_name' => 'required|text:3,100',
        'email' => 'required|email',
        'password' => 'nullable|password'
    ];
    
    if (!\Core\Validator::validate($data, $rules)) {
        return \Core\Response::validationError(\Core\Validator::errors());
    }
    
    try {
        $update_data = [
            'full_name' => \Core\Security::encrypt($data['full_name']),
            'email' => $data['email'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Якщо змінює пароль
        if (!empty($data['password'])) {
            $update_data['password'] = \Core\Security::hashPassword($data['password']);
        }
        
        // UPDATE запит (можна зробити просто через query або додати UPDATE helper)
        $sql = "UPDATE users SET full_name = ?, email = ?";
        $params = [$update_data['full_name'], $update_data['email']];
        
        if (isset($update_data['password'])) {
            $sql .= ", password = ?";
            $params[] = $update_data['password'];
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $user_id;
        
        \Core\DB::query($sql, $params);
        
        \Core\Logger::info("User profile updated", ['user_id' => $user_id]);
        return \Core\Response::success(null, 'Profile updated successfully');
        
    } catch (\Exception $e) {
        \Core\Logger::error("Profile update failed: " . $e->getMessage());
        return \Core\Response::serverError();
    }
}

// ==========================================
// ПРИКЛАД 4: Безпечно видалити ресурс
// ==========================================
function deletePoll($poll_id) {
    // Middleware
    \Core\Middleware::auth();
    \Core\Middleware::csrf();
    \Core\Middleware::rateLimit(20, 3600);
    
    // Валідація
    if (!\Core\Validator::integer($poll_id, 1)) {
        return \Core\Response::validationError(['poll_id' => 'Invalid ID']);
    }
    
    try {
        // Отримати і перевірити власність
        $poll = \Core\DB::fetch("SELECT user_id, ppo_id FROM polls WHERE id = ?", [$poll_id]);
        
        if (!$poll) {
            return \Core\Response::notFound();
        }
        
        // Авторизація: власник або лідер
        if ($poll['user_id'] !== \Core\Auth::userId() && !\Core\Auth::isLeadership()) {
            \Core\Logger::security('Unauthorized delete attempt', \Core\Logger::WARNING, ['poll_id' => $poll_id]);
            return \Core\Response::forbidden();
        }
        
        // Видалити
        \Core\DB::query("DELETE FROM polls WHERE id = ?", [$poll_id]);
        \Core\DB::query("DELETE FROM votes WHERE poll_id = ?", [$poll_id]);
        \Core\DB::query("DELETE FROM poll_options WHERE poll_id = ?", [$poll_id]);
        
        \Core\Logger::info("Poll deleted", ['poll_id' => $poll_id, 'user_id' => \Core\Auth::userId()]);
        return \Core\Response::success(null, 'Poll deleted');
        
    } catch (\Exception $e) {
        \Core\Logger::error("Delete failed: " . $e->getMessage());
        return \Core\Response::serverError();
    }
}
