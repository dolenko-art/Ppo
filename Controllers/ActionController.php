<?php
namespace Controllers;

require_once __DIR__ . '/../Services/PetitionService.php';
require_once __DIR__ . '/../Services/EventService.php';
require_once __DIR__ . '/../Services/UserService.php';
require_once __DIR__ . '/../Services/VotingService.php';

class ActionController {

    // ==========================================
    // 🛠 УНІВЕРСАЛЬНА ФУНКЦІЯ ДЛЯ ІМІТАЦІЇ КЕП
    // ==========================================
    private static function saveFakeKep($user_id, $action_context, $target_id = 0) {
        if (!$user_id) return;
        try {
            $salt = random_bytes(16);
            $fake_kep_container = "KEP-SIM-" . hash('sha256', $user_id . $action_context . $target_id . time() . bin2hex($salt));
            \Core\DB::query(
                "INSERT INTO digital_signatures (user_id, action_context, target_id, signature_hash) VALUES (?, ?, ?, ?)", 
                [$user_id, $action_context, $target_id, $fake_kep_container]
            );
        } catch (\Exception $e) {
            // Тихо ігноруємо помилки КЕПу, щоб не зламати основну дію
            error_log("Помилка генерації КЕП: " . $e->getMessage());
        }
    }

    // ==========================================
    // 🗳️ ГОЛОСУВАННЯ ТА ВИБОРИ
    // ==========================================
    public static function vote() {
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $poll_id = (int)($_POST['poll_id'] ?? 0);
        $kep_signed = !empty($_POST['kep_signed']);
        
        $res = \Services\VotingService::vote(
            $user_id, 
            (int)($_SESSION['ppo_id'] ?? 0), 
            $poll_id, 
            (int)($_POST['option_id'] ?? 0), 
            $kep_signed
        );

        if ($res['success'] && $kep_signed) {
            self::saveFakeKep($user_id, 'poll_vote', $poll_id);
        }
        return $res;
    }

    public static function signProtocol() {
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $poll_id = (int)($_POST['poll_id'] ?? 0);

        $res = \Services\VotingService::signProtocol($user_id, $poll_id);
        
        if ($res['success']) {
            self::saveFakeKep($user_id, 'protocol_sign', $poll_id);
        }
        return $res;
    }

    public static function nominate() {
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $nom_id = (int)($_POST['nomination_id'] ?? 0);
        $kep_signed = !empty($_POST['kep_signed']);

        $res = \Services\VotingService::nominate(
            $user_id, 
            (int)($_SESSION['ppo_id'] ?? 0), 
            $nom_id, 
            (int)($_POST['nominee_id'] ?? 0),
            $kep_signed
        );

        if ($res['success'] && $kep_signed) {
            self::saveFakeKep($user_id, 'nomination_vote', $nom_id);
        }
        return $res;
    }

    public static function nominateIdea() {
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $nom_id = (int)($_POST['nomination_id'] ?? 0);
        $kep_signed = !empty($_POST['kep_signed']);

        $res = \Services\VotingService::nominateIdea(
            $user_id, 
            (int)($_SESSION['ppo_id'] ?? 0), 
            $nom_id, 
            trim($_POST['idea_text'] ?? ''),
            $kep_signed
        );

        if ($res['success'] && $kep_signed) {
            self::saveFakeKep($user_id, 'nomination_idea_vote', $nom_id);
        }
        return $res;
    }

    public static function resolveElection() {
        return \Services\VotingService::resolveElection(
            (int)($_SESSION['user_id'] ?? 0), 
            (int)($_SESSION['ppo_id'] ?? 0), 
            (int)($_POST['poll_id'] ?? 0), 
            trim($_POST['role'] ?? ''), 
            trim($_POST['decision'] ?? '')
        );
    }

    public static function createExclusionPoll() {
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $target_user_id = (int)($_POST['target_user_id'] ?? 0);

        $res = \Services\VotingService::createExclusionPoll(
            $user_id, 
            (int)($_SESSION['ppo_id'] ?? 0), 
            $target_user_id
        );

        $isAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($isAjax) return $res;
        
        $_SESSION['toast_msg'] = $res['msg']; 
        header("Location: index.php?tab=polls"); 
        exit;
    }

    public static function submitExclusionComment() {
        $res = \Services\VotingService::submitExclusionComment(
            (int)($_SESSION['user_id'] ?? 0), 
            (int)($_POST['poll_id'] ?? 0), 
            trim($_POST['comment'] ?? $_POST['explanation'] ?? '')
        );
        
        $isAjax = !empty($_POST['ajax']) || (isset($_POST['action']) && $_POST['action'] === 'submit_defense') || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($isAjax) return $res;
        
        $_SESSION['toast_msg'] = $res['msg'] ?? 'Помилка'; 
        header("Location: profile.php?tab=notifs"); 
        exit;
    }

    // ==========================================
    // 🛡️ E2E-V (АБСОЛЮТНО ТАЄМНЕ ГОЛОСУВАННЯ)
    // ==========================================
    public static function generateE2eToken() {
        return \Services\VotingService::generateE2eToken(
            (int)($_SESSION['user_id'] ?? 0), 
            (int)($_SESSION['ppo_id'] ?? 0), 
            trim($_POST['target_type'] ?? ''), 
            (int)($_POST['target_id'] ?? 0),
            trim($_POST['token_hash'] ?? '')
        );
    }

    public static function castE2eVote() {
        // УВАГА: Тут ми НЕ використовуємо $_SESSION, бо запит абсолютно анонімний!
        return \Services\VotingService::castE2eVote(
            trim($_POST['target_type'] ?? ''), 
            (int)($_POST['target_id'] ?? 0), 
            (int)($_POST['option_id'] ?? 0), 
            trim($_POST['token'] ?? ''),
            isset($_POST['idea_text']) ? trim($_POST['idea_text']) : null
        );
    }

    // ==========================================
    // ✍️ ПЕТИЦІЇ ТА ПОДІЇ
    // ==========================================
    public static function createPetition() {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) { 
            return ['success' => false, 'msg' => 'CSRF Помилка. Оновіть сторінку.']; 
        }
        
        $user_id = (int)($_SESSION['user_id'] ?? 0);

        return \Services\PetitionService::create(
            $user_id, 
            (int)($_SESSION['ppo_id'] ?? 0), 
            trim($_POST['title'] ?? ''), 
            trim($_POST['description'] ?? ''), 
            (int)($_POST['is_anon'] ?? 0), 
            (int)($_POST['is_secret'] ?? 0)
        );
    }

    public static function signPetition() {
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $pet_id = (int)($_POST['id'] ?? 0);

        return \Services\PetitionService::sign($user_id, $pet_id);
    }

    public static function enroll() {
        return \Services\EventService::enroll(
            (int)($_SESSION['user_id'] ?? 0), 
            (int)($_SESSION['ppo_id'] ?? 0), 
            (int)($_POST['event_id'] ?? 0), 
            (int)($_POST['guests_count'] ?? 0), 
            trim($_POST['guests_info'] ?? '')
        );
    }

    // ==========================================
    // 🚪 ПРОФІЛЬ (ВИХІД, ПОШУК, ВСТУП)
    // ==========================================
    public static function resignRole() {
        return \Services\UserService::resignRole(
            (int)($_SESSION['user_id'] ?? 0), 
            (int)($_SESSION['ppo_id'] ?? 0)
        );
    }

    public static function leavePpo() {
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $ppo_id = (int)($_SESSION['ppo_id'] ?? 0);

        $res = \Services\UserService::leavePpo($user_id, $ppo_id);
        
        if ($res['success']) {
            self::saveFakeKep($user_id, 'leave_ppo', $ppo_id);
        }
        return $res;
    }

    public static function searchUsers() {
        return [
            'success' => true, 
            'users' => \Services\UserService::searchUsers(trim($_GET['q'] ?? ''), (int)($_SESSION['ppo_id'] ?? 0))
        ];
    }

    // ==========================================
    // 📝 ЗАЯВА НА ВСТУП (КЕП)
    // ==========================================
    public static function apply() {
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $ppo_id = (int)($_POST['ppo_id'] ?? $_SESSION['ppo_id'] ?? 0);
        
        $result = \Services\UserService::apply($user_id, $ppo_id);
        
        if ($result['success'] && !empty($result['ppo_id'])) {
            $_SESSION['ppo_id'] = $result['ppo_id'];
            self::saveFakeKep($user_id, 'admission_apply', $result['ppo_id']);
        }
        
        return $result;
    }
}
