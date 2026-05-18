<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'db.php';

// 1. БЕЗПЕЧНА ПЕРЕВІРКА АВТОРИЗАЦІЇ
if (!\Core\Auth::loggedIn()) { 
    header("Location: login.php"); 
    exit; 
}

$ppo_id = (int)$_SESSION['ppo_id'];

// 2. ОТРИМУЄМО АРХІВНІ ДАНІ (ТІЛЬКИ ДЛЯ СВОЄЇ ПРОФСПІЛКИ!)
try {
    $archived_noms = \Core\DB::fetchAll("SELECT * FROM nominations WHERE is_processed = 1 AND ppo_id = ? ORDER BY id DESC", [$ppo_id]);
    $archived_polls = \Core\DB::fetchAll("SELECT * FROM polls WHERE is_active = 0 AND ppo_id = ? ORDER BY id DESC", [$ppo_id]);
    $archived_events = \Core\DB::fetchAll("SELECT * FROM events WHERE event_date < NOW() AND ppo_id = ? ORDER BY event_date DESC", [$ppo_id]);
    $all_news = \Core\DB::fetchAll("SELECT * FROM news WHERE ppo_id = ? ORDER BY created_at DESC", [$ppo_id]);
    $all_finances = \Core\DB::fetchAll("SELECT * FROM finances WHERE ppo_id = ? ORDER BY report_month DESC", [$ppo_id]);
} catch (\Exception $e) { 
    die("На сайті ведуться технічні роботи. Спробуйте пізніше."); 
}

function formatUaDate($datetime, $showTime = true) {
    if (empty($datetime)) return 'Невідомо';
    $months = ['', 'січня', 'лютого', 'березня', 'квітня', 'травня', 'червня', 'липня', 'серпня', 'вересня', 'жовтня', 'листопада', 'грудня'];
    $time = strtotime($datetime); 
    $dateStr = date('j', $time) . ' ' . $months[date('n', $time)] . ' ' . date('Y', $time);
    return $showTime ? $dateStr . ' о ' . date('H:i', $time) : $dateStr;
}

function getMonthName($dateString) {
    if (empty($dateString)) return '';
    $months = ['', 'Січень', 'Лютий', 'Березень', 'Квітень', 'Травень', 'Червень', 'Липень', 'Серпень', 'Вересень', 'Жовтень', 'Листопад', 'Грудень'];
    return $months[(int)date('m', strtotime($dateString))] . ' ' . date('Y', strtotime($dateString));
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Архів - Профспілка UA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; -webkit-tap-highlight-color: transparent; }
        .safe-bottom { padding-bottom: 8rem; }
        .tab-content { display: none; } 
        .tab-content.active { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="safe-bottom">

    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-xl border-b border-slate-200/60 px-5 py-4 flex items-center gap-4 shadow-sm">
        <a href="index.php" class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-600 active:scale-90 transition"><i class="fa-solid fa-arrow-left"></i></a>
        <div>
            <h1 class="text-xl font-black text-slate-800 leading-none">Архів та Звіти</h1>
        </div>
    </header>

    <!-- Навігація вкладок -->
    <div class="flex overflow-x-auto gap-2 p-4 no-scrollbar sticky top-[72px] bg-[#f1f5f9] z-40">
        <button onclick="openTab('t-noms')" class="tab-btn px-4 py-2 bg-violet-600 text-white rounded-xl font-bold text-sm shrink-0 transition"><i class="fa-solid fa-user-tie mr-1"></i> Висування</button>
        <button onclick="openTab('t-polls')" class="tab-btn px-4 py-2 bg-slate-200 text-slate-600 rounded-xl font-bold text-sm shrink-0 transition">Голосування</button>
        <button onclick="openTab('t-events')" class="tab-btn px-4 py-2 bg-slate-200 text-slate-600 rounded-xl font-bold text-sm shrink-0 transition">Події</button>
        <button onclick="openTab('t-news')" class="tab-btn px-4 py-2 bg-slate-200 text-slate-600 rounded-xl font-bold text-sm shrink-0 transition">Новини</button>
        <button onclick="openTab('t-finance')" class="tab-btn px-4 py-2 bg-slate-200 text-slate-600 rounded-xl font-bold text-sm shrink-0 transition">Фінанси</button>
    </div>

    <main class="px-4 mt-2">

        <!-- АРХІВ ВИСУВАНЬ -->
        <div id="t-noms" class="tab-content active space-y-4">
            <?php if(empty($archived_noms)): ?>
                <p class="text-slate-500 text-center py-10 font-medium">Архів висувань порожній.</p>
            <?php endif; ?>

            <?php foreach($archived_noms as $nom): 
                $results = \Core\DB::fetchAll("SELECT nominee_id, COUNT(*) as votes_count FROM nomination_votes WHERE nomination_id = ? GROUP BY nominee_id ORDER BY votes_count DESC", [$nom['id']]);
            ?>
                <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200/60 opacity-80 hover:opacity-100 transition-opacity">
                    <div class="mb-4">
                        <span class="text-[10px] font-black uppercase tracking-widest text-violet-500 bg-violet-50 px-2 py-1 rounded-lg">Завершено</span>
                        <h4 class="font-black text-slate-800 text-lg leading-snug mt-2 mb-1"><?= htmlspecialchars($nom['title']) ?></h4>
                        <p class="text-[11px] font-bold text-slate-500">Закрито: <?= formatUaDate($nom['created_at'] ?? '') ?></p>
                    </div>
                    
                    <div class="space-y-2 mt-4 border-t border-slate-100 pt-4">
                        <p class="text-[10px] font-black uppercase text-slate-400 mb-2">Підсумки пропозицій (голоси):</p>
                        <?php if(empty($results)): ?>
                            <p class="text-sm font-medium text-slate-500">Ніхто не запропонував жодного кандидата.</p>
                        <?php else: ?>
                            <?php foreach($results as $index => $res): 
                                $uName_raw = \Core\DB::fetchColumn("SELECT full_name FROM users WHERE id = ?", [(int)$res['nominee_id']]);
                                $uName = \Core\Security::decrypt($uName_raw) ?: $uName_raw;
                                $is_winner = ($index === 0 && count($results) === 1);
                            ?>
                                <div class="flex justify-between items-center p-3 <?= $is_winner ? 'bg-amber-50 border-amber-200' : 'bg-slate-50 border-slate-100' ?> rounded-xl border">
                                    <span class="font-bold text-sm text-slate-700"><?= $is_winner ? '<i class="fa-solid fa-trophy text-amber-500 mr-1"></i>' : ($index + 1 . '. ') ?> <?= htmlspecialchars($uName) ?></span>
                                    <span class="text-xs font-black text-violet-600 bg-violet-100 px-2 py-1 rounded-lg"><?= $res['votes_count'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- АРХІВ ГОЛОСУВАНЬ -->
        <div id="t-polls" class="tab-content space-y-4">
            <?php if(empty($archived_polls)): ?>
                <p class="text-slate-500 text-center py-10 font-medium">Архів голосувань порожній.</p>
            <?php endif; ?>

            <?php foreach($archived_polls as $poll): 
                $options = \Core\DB::fetchAll("SELECT id, option_text FROM poll_options WHERE poll_id = ?", [$poll['id']]);
                $total_votes = (int)\Core\DB::fetchColumn("SELECT COUNT(*) FROM votes WHERE poll_id = ?", [$poll['id']]);
                $is_secret = (int)$poll['is_secret'];
            ?>
                <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200/60 opacity-80 hover:opacity-100 transition-opacity">
                    <div class="mb-5">
                        <span class="text-[10px] font-black uppercase tracking-widest text-blue-500 bg-blue-50 px-2 py-1 rounded-lg">Завершено</span>
                        <h4 class="font-black text-slate-800 text-lg leading-snug mt-2 mb-2"><?= htmlspecialchars($poll['title']) ?></h4>
                        <p class="text-[11px] font-bold text-slate-500">Завершилось: <?= formatUaDate($poll['created_at'] ?? '') ?></p>
                    </div>

                    <div class="space-y-4">
                        <?php foreach($options as $opt): 
                            $voters_raw = \Core\DB::fetchAll("SELECT u.full_name FROM votes v JOIN users u ON v.user_id = u.id WHERE v.option_id = ?", [$opt['id']]);
                            
                            $voters = [];
                            foreach ($voters_raw as $vr) {
                                $voters[] = $is_secret ? '🔒 Анонімно' : (\Core\Security::decrypt($vr['full_name']) ?: $vr['full_name']);
                            }
                            
                            $opt_votes_count = count($voters);
                            $percent = ($total_votes > 0) ? round(($opt_votes_count / $total_votes) * 100) : 0;
                            $voters_json = htmlspecialchars(json_encode($voters, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                        ?>
                            <div>
                                <div class="relative w-full bg-slate-50 rounded-2xl overflow-hidden border border-slate-200">
                                    <div class="absolute h-full bg-slate-200/50" style="width: <?= $percent ?>%;"></div>
                                    <div class="relative flex justify-between items-center p-4">
                                        <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($opt['option_text']) ?></span>
                                        <span class="text-sm font-black text-slate-500"><?= $percent ?>%</span>
                                    </div>
                                </div>
                                <?php if($opt_votes_count > 0): ?>
                                    <button onclick="openVotersModal('<?= htmlspecialchars($opt['option_text'], ENT_QUOTES) ?>', <?= $voters_json ?>)" class="text-[10px] font-bold text-slate-400 hover:text-blue-600 mt-2 ml-2 transition flex items-center gap-1.5 outline-none">
                                        <i class="fa-solid fa-users text-[9px]"></i> Показати список (<?= $opt_votes_count ?>)
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-right text-[11px] font-bold text-slate-400 pt-2 border-t border-slate-100">Всього голосів: <?= $total_votes ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- АРХІВ ПОДІЙ -->
        <div id="t-events" class="tab-content space-y-4">
            <?php if(empty($archived_events)): ?>
                <p class="text-slate-500 text-center py-10 font-medium">Архів подій порожній.</p>
            <?php endif; ?>

            <?php foreach($archived_events as $event): ?>
                <div class="bg-white p-5 rounded-[2.5rem] border border-slate-200/60 shadow-sm opacity-80">
                    <div class="flex gap-4 items-start mb-3">
                        <div class="w-12 h-12 bg-slate-100 text-slate-500 rounded-2xl flex flex-col items-center justify-center font-black shrink-0">
                            <span class="text-[9px] uppercase leading-none"><?= date('M', strtotime($event['event_date'])) ?></span>
                            <span class="text-lg leading-none mt-1"><?= date('d', strtotime($event['event_date'])) ?></span>
                        </div>
                        <div class="flex-1">
                            <h5 class="font-bold text-sm text-slate-800 leading-tight"><?= htmlspecialchars($event['title']) ?></h5>
                            <p class="text-[11px] text-slate-400 mt-1 font-medium"><i class="fa-solid fa-location-dot mr-1"></i> <?= htmlspecialchars($event['location']) ?></p>
                        </div>
                    </div>
                    <?php if(!empty($event['description'])): ?>
                        <p class="text-xs text-slate-500 line-clamp-2"><?= htmlspecialchars($event['description']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ІСТОРІЯ НОВИН -->
        <div id="t-news" class="tab-content space-y-4">
            <?php if(empty($all_news)): ?>
                <p class="text-slate-500 text-center py-10 font-medium">Новин ще немає.</p>
            <?php endif; ?>

            <?php foreach($all_news as $news): ?>
                <div class="bg-white p-5 rounded-[2rem] shadow-sm border border-slate-200/60">
                    <span class="text-[10px] text-slate-400 font-bold uppercase"><i class="fa-regular fa-calendar mr-1"></i> <?= formatUaDate($news['created_at'], false) ?></span>
                    <h4 class="font-black text-slate-800 mt-2 mb-3 leading-snug text-base"><?= htmlspecialchars($news['title']) ?></h4>
                    <p class="text-sm text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-2xl border border-slate-100"><?= nl2br(htmlspecialchars($news['content'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ІСТОРІЯ ФІНАНСІВ -->
        <div id="t-finance" class="tab-content space-y-4">
            <?php if(empty($all_finances)): ?>
                <p class="text-slate-500 text-center py-10 font-medium">Фінансових звітів ще немає.</p>
            <?php endif; ?>

            <?php foreach($all_finances as $finance): ?>
                <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200/60">
                    <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
                        <h4 class="font-black text-slate-800 text-lg uppercase tracking-tight"><?= getMonthName($finance['report_month']) ?></h4>
                        <div class="text-right">
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Фонд на кінець</p>
                            <p class="font-black text-blue-600 text-lg"><?= number_format($finance['total_fund'], 0, '', ' ') ?> ₴</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-emerald-50 rounded-2xl p-3 border border-emerald-100">
                            <p class="text-[10px] text-emerald-600 font-bold uppercase tracking-widest mb-1"><i class="fa-solid fa-arrow-trend-up"></i> Доходи</p>
                            <p class="font-black text-sm text-emerald-700">+ <?= number_format($finance['income'], 0, '', ' ') ?> ₴</p>
                        </div>
                        <div class="bg-red-50 rounded-2xl p-3 border border-red-100">
                            <p class="text-[10px] text-red-600 font-bold uppercase tracking-widest mb-1"><i class="fa-solid fa-arrow-trend-down"></i> Витрати</p>
                            <p class="font-black text-sm text-red-700">- <?= number_format($finance['expenses'], 0, '', ' ') ?> ₴</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <!-- НИЖНЯ НАВІГАЦІЯ -->
    <nav class="fixed bottom-0 left-0 right-0 h-20 bg-white/95 backdrop-blur-xl border-t border-slate-200/60 flex justify-around items-center px-4 pb-safe z-40 shadow-[0_-10px_40px_rgba(0,0,0,0.03)]">
        <a href="index.php" class="flex flex-col items-center text-slate-400 hover:text-blue-600 active:scale-90 transition"><i class="fa-solid fa-house-chimney text-xl mb-1"></i><span class="text-[9px] font-black uppercase">Головна</span></a>
        <a href="history.php" class="flex flex-col items-center text-blue-600 active:scale-90 transition"><i class="fa-solid fa-box-archive text-xl mb-1"></i><span class="text-[9px] font-black uppercase">Архів</span></a>
        <a href="index.php?logout=1" class="flex flex-col items-center text-slate-400 hover:text-red-500 active:scale-90 transition"><i class="fa-solid fa-arrow-right-from-bracket text-xl mb-1"></i><span class="text-[9px] font-black uppercase">Вихід</span></a>
    </nav>

    <!-- МОДАЛКА СПИСКУ ПРОГОЛОСУВАВШИХ -->
    <div id="votersModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100] hidden items-center justify-center p-4 opacity-0 transition-opacity duration-300">
        <div class="bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl transform scale-95 transition-transform duration-300 p-6 flex flex-col max-h-[80vh]">
            <div class="flex justify-between items-start mb-4">
                <div><p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Проголосували за:</p><h3 id="modalOptionTitle" class="font-bold text-slate-800 leading-tight text-sm"></h3></div>
                <button onclick="closeVotersModal()" class="w-8 h-8 bg-slate-100 text-slate-500 rounded-full flex items-center justify-center hover:bg-slate-200 active:scale-90 shrink-0"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="modalVotersList" class="flex-1 overflow-y-auto space-y-2 pr-2 custom-scrollbar"></div>
        </div>
    </div>

    <!-- СКРИПТИ -->
    <script>
        function openTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => { 
                b.classList.remove('bg-blue-600', 'bg-violet-600', 'text-white'); 
                b.classList.add('bg-slate-200', 'text-slate-600'); 
            });
            
            document.getElementById(tabId).classList.add('active');
            
            let colorClass = tabId === 't-noms' ? 'bg-violet-600' : 'bg-blue-600';
            event.currentTarget.classList.add(colorClass, 'text-white');
            event.currentTarget.classList.remove('bg-slate-200', 'text-slate-600');
        }

        const modal = document.getElementById('votersModal');
        const modalList = document.getElementById('modalVotersList');
        const modalTitle = document.getElementById('modalOptionTitle');

        function openVotersModal(optionText, voters) {
            modalTitle.innerText = optionText;
            modalList.innerHTML = ''; 
            voters.forEach(name => {
                const div = document.createElement('div');
                div.className = 'py-2.5 border-b border-slate-100 text-sm font-semibold text-slate-700 flex items-center gap-3';
                div.innerHTML = `<div class="w-6 h-6 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center text-[10px]"><i class="fa-solid fa-user"></i></div> ${name}`;
                modalList.appendChild(div);
            });
            modal.classList.remove('hidden'); modal.classList.add('flex');
            setTimeout(() => { modal.classList.remove('opacity-0'); modal.children[0].classList.remove('scale-95'); }, 10);
        }

        function closeVotersModal() {
            modal.classList.add('opacity-0'); modal.children[0].classList.add('scale-95');
            setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); }, 300);
        }
    </script>
    <style>.custom-scrollbar::-webkit-scrollbar { width: 4px; } .custom-scrollbar::-webkit-scrollbar-track { background: transparent; } .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }</style>
</body>
</html>
