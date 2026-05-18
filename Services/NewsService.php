<?php
namespace Services;

use Core\DB;

class NewsService {
    public static function create($title, $content, $ppo_id) {
        DB::query("INSERT INTO news (title, content, ppo_id) VALUES (?, ?, ?)", [$title, $content, $ppo_id]);
        return ['success' => true, 'msg' => '📢 Новину опубліковано!'];
    }
}
