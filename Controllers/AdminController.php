<?php
declare(strict_types=1);

namespace Controllers;

class AdminController {

    // 🛡️ Типізуємо повернення: контролер ТІЛЬКИ віддає масив роутеру
    public static function handle(): array 
    {
        try {
            $user_id  = (int)($_SESSION['user_id'] ?? 0);
            $ppo_id   = (int)($_SESSION['ppo_id'] ?? 0);

            if ($user_id <= 0 || $ppo_id <= 0) {
                return ['success' => false, 'msg' => 'Не авторизовано.'];
            }

            // 🔒 ПЕРЕВІРКА ПРАВ ДОСТУПУ
            $user = \Core\DB::fetch("SELECT status, role FROM users WHERE id = ? AND ppo_id = ?", [$user_id, $ppo_id]);
            if (!$user || ($user['status'] !== 'admin' && !in_array($user['role'], ['head', 'manager'], true))) {
                return ['success' => false, 'msg' => '❌ Доступ заборонено.'];
            }

            // Захист від масивів у дії
            $action = is_scalar($_POST['action'] ?? null) ? (string)$_POST['action'] : '';
            $result = ['success' => false, 'msg' => 'Невідома дія.'];

            switch ($action) {
                case 'approve_reg': 
                    $result = \Services\UserService::approveReg((int)($_POST['user_id'] ?? 0), $ppo_id); 
                    break;
                case 'reject_reg':  
                    $result = \Services\UserService::rejectReg((int)($_POST['user_id'] ?? 0), $ppo_id); 
                    break;
                case 'approve_mem': 
                    $result = \Services\UserService::approveMem((int)($_POST['user_id'] ?? 0), $ppo_id); 
                    break;
                case 'reject_mem':  
                    $result = \Services\UserService::rejectMem((int)($_POST['user_id'] ?? 0), $ppo_id); 
                    break;

                case 'add_news':
                case 'news':
                    // 🛡️ Жорстке приведення до рядка для захисту від Array Payload
                    $title = (string)($_POST['title'] ?? '');
                    $content = (string)($_POST['content'] ?? '');
                    $result = \Services\NewsService::create($title, $content, $ppo_id); 
                    if ($result['success'] ?? false) $result['redirect'] = 'index.php?tab=news';
                    break;
                    
                case 'add_event':
                case 'event':       
                    $result = \Services\EventService::create($_POST, $ppo_id); 
                    if ($result['success'] ?? false) $result['redirect'] = 'index.php?tab=events';
                    break;

                case 'answer_petition': 
                    $result = \Services\PetitionService::answerPetition(
                        (int)($_POST['petition_id'] ?? 0), 
                        (int)($_POST['status'] ?? 0), 
                        trim((string)($_POST['comment'] ?? '')), 
                        $ppo_id
                    ); 
                    if ($result['success'] ?? false) {
                        $result['redirect'] = 'index.php?tab=petitions&sub=archive'; 
                    }
                    break;

                case 'add_poll':
                case 'poll':        
                    $result = \Services\PollService::createPoll($_POST, $ppo_id); 
                    if ($result['success'] ?? false) $result['redirect'] = 'index.php?tab=polls';
                    break;
                    
                case 'add_nomination':
                case 'nomination':  
                    $result = \Services\PollService::createNomination($_POST, $ppo_id); 
                    if ($result['success'] ?? false) $result['redirect'] = 'index.php?tab=noms';
                    break;

                case 'add_cash':    
                    $result = \Services\FinanceService::addCash($_POST, $user_id, $ppo_id); 
                    if ($result['success'] ?? false) $result['redirect'] = 'finance_history.php?tab=cash'; 
                    break;
                    
                case 'save_mono_token': 
                    $result = \Services\FinanceService::saveMonoToken($_POST, $ppo_id); 
                    break;

                default:
                    if ($action !== '') {
                        error_log("Security Warning: Спроба виклику невідомої дії в AdminController: " . htmlspecialchars($action));
                    }
                    return ['success' => false, 'msg' => 'Невідома дія. Доступ заблоковано.'];
            }

            // Зберігаємо сповіщення в сесію, якщо дія успішна
            if (($result['success'] ?? false) && !empty($result['msg'])) {
                $_SESSION['toast_msg'] = $result['msg'];
            }

            return $result;

        } catch (\Throwable $e) { 
            error_log("AdminController Error: " . $e->getMessage());
            return ['success' => false, 'msg' => "❌ Сталася системна помилка. Спробуйте пізніше."];
        }
    }
}
