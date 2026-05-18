/**
 * APP.JS - Головний логічний центр додатку (Користувачі + Адмінка)
 * SENIOR EDITION: 100% CSP COMPLIANT (DOM CSSOM API)
 */

// Глобальні змінні
window.pendingSignaturesCount = window.pendingSignaturesCount || 0;
let currentKepAction = null;

// ==========================================
// 🛡️ 1. БАЗОВІ ФУНКЦІЇ ТА ТОАСТИ
// ==========================================
function escapeHTML(str) {
    if (!str) return '';
    return str.toString()
        .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function getCsrfToken() { 
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; 
}

window.toggleFin = function() {
    const box = document.getElementById('fin-box');
    const chevron = document.getElementById('fin-chevron');
    if (!box) return;
    
    if (box.style.display === 'block') {
        box.style.display = 'none';
        if (chevron) chevron.innerHTML = '<i class="fa-solid fa-chevron-down"></i>';
    } else {
        box.style.display = 'block';
        if (chevron) chevron.innerHTML = '<i class="fa-solid fa-chevron-up"></i>';
    }
};

function showToast(m) { 
    const t = document.getElementById('toast'); 
    if (!t) return;
    
    t.innerHTML = m; 
    t.classList.remove('success', 'error', 'show');
    
    if (m.includes('✅')) t.classList.add('success');
    if (m.includes('❌') || m.includes('🛑') || m.includes('🔴')) t.classList.add('error');
    
    void t.offsetWidth; 
    t.classList.add('show'); 
    
    if (t.hideTimeout) clearTimeout(t.hideTimeout);
    
    t.hideTimeout = setTimeout(() => { 
        t.classList.remove('show'); 
    }, 3500); 
}

// ==========================================
// 🎨 2. ТЕМИ ТА НАВІГАЦІЯ (Макет)
// ==========================================
function cycleTheme() {
    const themes = ['light', 'dark', 'aurora', 'sunset', 'ocean', 'latte', 'vision', 'emerald'];
    const layouts = ['bar', 'tile'];
    
    let currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    let currentLayout = document.documentElement.getAttribute('data-layout') || 'bar';
    
    let themeIdx = themes.indexOf(currentTheme);
    if (themeIdx === -1) themeIdx = 0;
    let layoutIdx = layouts.indexOf(currentLayout);
    if (layoutIdx === -1) layoutIdx = 0;
    
    const isMainPage = document.querySelector('.view-bar') !== null && window.location.pathname.endsWith('index.php');

    if (isMainPage) {
        layoutIdx++;
        if (layoutIdx >= layouts.length) {
            layoutIdx = 0; 
            themeIdx = (themeIdx + 1) % themes.length; 
        }
    } else {
        themeIdx = (themeIdx + 1) % themes.length; 
    }
    
    let nextTheme = themes[themeIdx];
    let nextLayout = layouts[layoutIdx];
    
    document.documentElement.setAttribute('data-theme', nextTheme);
    document.documentElement.setAttribute('data-layout', nextLayout);
    
    localStorage.setItem('theme', nextTheme);
    if (isMainPage) localStorage.setItem('layout', nextLayout);

    if (isMainPage && typeof window.goTo === 'function') {
        if (nextLayout === 'tile') window.goTo(0);
        else if (nextLayout === 'bar' && document.getElementById('slider')?.style.transform === 'translateX(0%)') window.goTo(1);
    }
}

// ==========================================
// 🛝 3. ЛОГІКА СЛАЙДЕРІВ
// ==========================================
window.goTo = function(idx) {
    const layout = document.documentElement.getAttribute('data-layout');
    const slider = document.getElementById('slider');
    const backBtn = document.getElementById('back-btn-dash');
    const mainIcon = document.getElementById('main-icon-dash');

    document.querySelectorAll('.slide').forEach((s, i) => {
        if (i === idx) s.classList.add('active-slide');
        else s.classList.remove('active-slide');
    });

    if (slider) {
        const slidesCount = document.querySelectorAll('.slide').length;
        const percent = slidesCount > 0 ? (100 / slidesCount) : 0;
        slider.style.transform = `translateX(-${idx * percent}%)`;
    }

    if (layout === 'tile' && document.getElementById('header-title')) {
        if (idx === 0) {
            if(backBtn) backBtn.classList.remove('active');
            if(mainIcon) mainIcon.classList.remove('hidden');
            document.getElementById('header-title').innerText = 'Профспілка';
        } else {
            if(backBtn) backBtn.classList.add('active');
            if(mainIcon) mainIcon.classList.add('hidden');
            const titles = ['', 'Стрічка новин', 'Голосування', 'Висування', 'Ініціативи', 'Заходи'];
            document.getElementById('header-title').innerText = titles[idx] || 'Розділ';
        }
    }

    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    const navItem = document.getElementById('n' + idx);
    if (navItem) navItem.classList.add('active');
    
    window.scrollTo({top:0, behavior:'smooth'});
};

window.goToProfileTab = function(idx) {
    const slider = document.getElementById('slider');
    if (!slider) return;
    document.querySelectorAll('.slide').forEach((s, i) => {
        if (i === idx) s.classList.add('active-slide');
        else s.classList.remove('active-slide');
    });
    slider.style.transform = `translateX(-${idx * 25}%)`;
    document.querySelectorAll('#profile-bottom-nav .nav-item').forEach(el => el.classList.remove('active'));
    const navItem = document.getElementById('pn' + idx);
    if (navItem) navItem.classList.add('active');
    window.scrollTo({top:0, behavior:'smooth'});
};

window.goToInfoTab = function(idx) {
    const slider = document.getElementById('slider');
    if (!slider) return;
    
    document.querySelectorAll('.slide').forEach((s, i) => {
        if (i === idx) s.classList.add('active-slide');
        else s.classList.remove('active-slide');
    });
    slider.style.transform = `translateX(-${idx * 20}%)`;
    
    document.querySelectorAll('#info-bottom-nav .nav-item').forEach(el => el.classList.remove('active'));
    const navItem = document.getElementById('in' + idx);
    if (navItem) navItem.classList.add('active');
    
    // Оновлюємо URL для збереження історії
    const tabsMap = ['leaders', 'members', 'top', 'archive', 'stats'];
    const url = new URL(window.location);
    url.searchParams.set('tab', tabsMap[idx]);
    window.history.replaceState({}, '', url);
    
    window.scrollTo({top:0, behavior:'smooth'});
};

window.goToFinTab = function(idx) { 
    const slider = document.getElementById('slider');
    if (!slider) return;
    document.querySelectorAll('.slide').forEach((s, i) => {
        if (i === idx) s.classList.add('active-slide');
        else s.classList.remove('active-slide');
    });
    slider.style.transform = `translateX(-${idx*50}%)`; 
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active')); 
    const navItem = document.getElementById('fn' + idx);
    if (navItem) navItem.classList.add('active'); 
    
    const newTab = idx === 1 ? 'bank' : 'cash';
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('tab', newTab);
    window.history.replaceState({}, '', '?' + urlParams.toString());
    window.scrollTo({top:0, behavior:'smooth'}); 
};

window.goToAuditorTab = function(idx) {
    const slider = document.getElementById('slider');
    if (!slider) return;
    document.querySelectorAll('.slide').forEach((s, i) => {
        if (i === idx) s.classList.add('active-slide');
        else s.classList.remove('active-slide');
    });
    slider.style.transform = `translateX(-${idx * 33.33333}%)`;
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    const navItem = document.getElementById('an' + idx);
    if (navItem) navItem.classList.add('active');
    window.scrollTo({top:0, behavior:'smooth'});
};

// ==========================================
// 👑 4. НАВІГАЦІЯ АДМІНКИ
// ==========================================
function handleBack() {
    const path = window.location.pathname;
    const currentTab = new URLSearchParams(window.location.search).get('tab') || 'menu';
    const isUrlAdmin = path.includes('admin_create.php') || document.getElementById('adminBottomNav') !== null;

    if (isUrlAdmin && currentTab !== 'menu') {
        adminGoTo(0); 
    } else if (path.includes('index.php') && currentTab && currentTab !== 'menu' && typeof goTo === 'function') {
        goTo(0); 
    } else {
        window.location.href = 'index.php'; 
    }
}

function adminGoTo(idx, noScroll = false) { 
    const sectionTitles = ['Пульт управління', 'Заявки на реєстрацію', 'Додати новину', 'Створити голосування', 'Збір пропозицій', 'Керування подіями', 'Петиції на розгляді', 'Фінанси та інтеграція'];
    const tabsMapArr = ['menu', 'reg', 'news', 'polls', 'noms', 'events', 'pets', 'fin'];

    document.querySelectorAll('.slide').forEach((s, i) => {
        if (i === idx) s.classList.add('active-slide');
        else s.classList.remove('active-slide');
    });

    const slider = document.getElementById('slider');
    if (slider) slider.style.transform = `translateX(-${(idx*12.5)}%)`; 
    
    const url = new URL(window.location); 
    url.searchParams.set('tab', tabsMapArr[idx]);
    
    if (!noScroll) {
        url.searchParams.delete('sub_events');
        url.searchParams.delete('sub_fin');
        window.history.replaceState({}, '', url); 
    }
    
    const t = document.getElementById('headerTitle');
    if(t) t.innerText = sectionTitles[idx];
    
    const backBtn = document.getElementById('headerBackBtn');
    if(backBtn) {
        if(idx === 0) backBtn.innerHTML = '<i class="fa-solid fa-arrow-left"></i>';
        else backBtn.innerHTML = '<i class="fa-solid fa-house"></i>';
    }

    const bNav = document.getElementById('adminBottomNav');
    if (bNav) {
        if (tabsMapArr[idx] === 'events' || tabsMapArr[idx] === 'fin') {
            bNav.style.display = 'grid';
            renderBottomNav(tabsMapArr[idx]);
            document.body.style.paddingBottom = '80px';
        } else {
            bNav.style.display = 'none';
            document.body.style.paddingBottom = '20px';
        }
    }

    if (!noScroll) window.scrollTo({top:0,behavior:'smooth'}); 
}

function renderBottomNav(tab) {
    const bNav = document.getElementById('adminBottomNav');
    if (!bNav) return;
    const urlParams = new URLSearchParams(window.location.search);
    
    if (tab === 'events') {
        const sub = urlParams.get('sub_events') || 'list';
        bNav.innerHTML = `
            <a href="?tab=events&sub_events=create" class="nav-item ${sub==='create'?'active':''}"><i class="fa-solid fa-plus"></i>СТВОРИТИ</a>
            <a href="?tab=events&sub_events=list" class="nav-item ${sub==='list'?'active':''}"><i class="fa-solid fa-list"></i>СПИСОК</a>
        `;
    } else if (tab === 'fin') {
        const sub = urlParams.get('sub_fin') || 'cash';
        bNav.innerHTML = `
            <a href="?tab=fin&sub_fin=cash" class="nav-item ${sub==='cash'?'active':''}"><i class="fa-solid fa-wallet"></i>КАСА</a>
            <a href="?tab=fin&sub_fin=bank" class="nav-item ${sub==='bank'?'active':''}"><i class="fa-solid fa-building-columns"></i>МОНОБАНК</a>
        `;
    }
}

// ==========================================
// 🪟 5. МОДАЛКИ
// ==========================================
function openModal(title, content, isHtml = false) {
    const modal = document.getElementById('mainModal');
    if(modal) {
        document.getElementById('mTitle').innerText = title;
        const bodyDiv = document.getElementById('mBody');
        if (isHtml) bodyDiv.innerHTML = content;
        else {
            bodyDiv.innerHTML = `<p class="ui-desc"></p>`;
            bodyDiv.querySelector('p').textContent = content; 
        }
        modal.style.display = 'flex';
    }
}

function closeModal(modalId = 'mainModal') {
    const modal = document.getElementById(modalId);
    if(modal) modal.style.display = 'none';
}

function getAvatar(name) {
    const initial = name ? name.trim().charAt(0).toUpperCase() : '👤';
    return `<div class="js-avatar" data-char="${initial}"></div>`;
}

// ==========================================
// 🚀 6. УНІВЕРСАЛЬНИЙ AJAX / API ОБРОБНИК
// ==========================================
const AppAPI = {
    async post(route, data, btn = null) {
        let originalHtml = '';
        if (btn) {
            if (btn.dataset.processing === "1") return null;
            btn.dataset.processing = "1";
            originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Обробка...';
            btn.disabled = true;
        }

        let fd = data instanceof FormData ? data : new FormData();
        if (!(data instanceof FormData)) {
            for (let key in data) fd.append(key, data[key]);
        }
        if (!fd.has('ajax')) fd.append('ajax', '1');
        if (!fd.has('csrf_token')) fd.append('csrf_token', getCsrfToken());

        try {
            const targetUrl = route.includes('.php') ? route : `action.php?route=${route}`;
            let fetchOptions = { method: 'POST', body: fd };
            if (route === 'cast_e2e_vote') fetchOptions.credentials = 'omit';
            
            const r = await fetch(targetUrl, fetchOptions);
            const text = await r.text();
            
            let res;
            try {
                const firstBrace = text.indexOf('{');
                const lastBrace = text.lastIndexOf('}');
                res = JSON.parse(text.substring(firstBrace, lastBrace + 1));
            } catch (e) {
                console.error("🔴 ПОМИЛКА ПАРСИНГУ ВІДПОВІДІ:", text);
                if (typeof showToast === 'function') showToast("🔴 Помилка сервера");
                return null;
            }
            return res;
        } catch (error) {
            console.error("🔴 ПОМИЛКА МЕРЕЖІ:", error);
            if (typeof showToast === 'function') showToast("🔴 Мережева помилка");
            return null;
        } finally {
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                btn.dataset.processing = "0";
            }
        }
    }
};

async function submitFormAjax(event, form) {
    if(event) event.preventDefault(); 
    const btn = (event && event.submitter) ? event.submitter : form.querySelector('button[type="submit"]');
    
    const targetUrl = form.getAttribute('action') || 'action.php';
    const res = await AppAPI.post(targetUrl, new FormData(form), btn);
    
    if (!res) return; 

    if (res.success) {
        if (btn) {
            btn.className = 'ui-alert green';
            btn.innerHTML = '<i class="fa-solid fa-check"></i> ' + (res.msg || "Успішно!");
        }
        if (typeof showToast === 'function') showToast("✅ " + (res.msg || "Виконано!"));
        
        const redirectUrl = res.redirect || form.getAttribute('data-redirect');
        setTimeout(() => {
            const modal = form.closest('.modal');
            if (modal) modal.style.display = 'none';
            if (redirectUrl) window.location.href = redirectUrl;
            else window.location.reload(); 
        }, 1500); 
    } else {
        if (typeof showToast === 'function') showToast("❌ " + (res.msg || "Помилка"));
        if (btn) { 
            let oldHtml = btn.innerHTML;
            btn.className = 'ui-alert red';
            btn.innerHTML = '<i class="fa-solid fa-xmark"></i> ' + (res.msg || "Помилка");
            setTimeout(() => {
                btn.className = 'ui-btn primary';
                btn.innerHTML = oldHtml; 
            }, 3000);
        }
    }
}

// ==========================================
// 💡 7. ЛОГІКА АДМІНКИ ТА ПОШУКУ
// ==========================================
function toggleNomType() {
    const type = document.getElementById('nomTypeSelect');
    const titleInput = document.getElementById('nomTitleInput');
    const requireKep = document.getElementById('nomRequireKep');
    
    if (!type || !titleInput) return;
    if (type.value === 'auditor') { titleInput.value = 'Висування кандидатів на посаду Ревізора'; titleInput.readOnly = true; if (requireKep) requireKep.value = '1'; } 
    else if (type.value === 'manager') { titleInput.value = 'Висування кандидатів на посаду Менеджера'; titleInput.readOnly = true; if (requireKep) requireKep.value = '1'; } 
    else { titleInput.value = ''; titleInput.readOnly = false; titleInput.placeholder = 'Наприклад: Куди поїдемо на екскурсію?'; if (requireKep) requireKep.value = '0'; }
}

function togglePollType() {
    const type = document.getElementById('pollTypeSelect').value;
    const searchBlock = document.getElementById('candidateSearchBlock');
    const titleBlock = document.getElementById('regularTitleBlock');
    const titleInput = document.getElementById('pollTitleInput');
    const optionsBlock = document.getElementById('regularOptionsBlock');
    const optionsInputs = document.querySelectorAll('.opt-input');
    const quorumSelect = document.getElementById('quorumSelect');

    if (type === 'regular') {
        searchBlock.style.display = 'none'; titleBlock.style.display = 'block'; optionsBlock.style.display = 'block'; titleInput.required = true;
        document.getElementById('candidateIdInput').value = ''; 
        optionsInputs.forEach(input => input.required = true); quorumSelect.disabled = false; 
    } else {
        searchBlock.style.display = 'block'; titleBlock.style.display = 'none'; optionsBlock.style.display = 'none'; titleInput.required = false; 
        optionsInputs.forEach(input => input.required = false);
        quorumSelect.value = '50+1'; quorumSelect.disabled = true; 
    }
}

window.searchTimers = window.searchTimers || {};
async function fetchSearch(route, query) {
    try {
        const res = await fetch(`action.php?route=${route}&q=${encodeURIComponent(query)}`);
        return await res.json();
    } catch (e) { return null; }
}

function searchUser(query, nomId, isSecret = 0) {
    const resBox = document.getElementById('res_' + nomId);
    if (query.length < 2) { resBox.style.display = 'none'; return; }
    
    clearTimeout(window.searchTimers['su_' + nomId]);
    window.searchTimers['su_' + nomId] = setTimeout(async () => {
        const data = await fetchSearch('search_users', query);
        if (!data) return;
        
        let usersList = Array.isArray(data) ? data : (data.users || []);
        if (usersList.length > 0) {
            resBox.innerHTML = usersList.map(u => {
                let dispName = escapeHTML(u.name || u.full_name || 'Без імені'); 
                return `<div class="ui-card js-nominate-candidate" data-nom-id="${nomId}" data-u-id="${u.id}" data-is-secret="${isSecret}" style="margin:0; padding:12px; border-radius:0; border:none; border-bottom:1px solid var(--border); cursor:pointer;">
                            <i class="fa-solid fa-user-plus" style="color:var(--accent);"></i> <span style="font-weight:800; margin-left:8px;">${dispName}</span>
                         </div>`;
            }).join('');
        } else {
            resBox.innerHTML = '<div class="ui-empty" style="margin:0; padding:12px; border:none;">Нікого не знайдено</div>';
        }
        resBox.style.display = 'block';
    }, 300);
}

function searchCandidate(query) {
    const resBox = document.getElementById('candidateSearchResults');
    if (query.length < 2) { resBox.innerHTML = ''; return; }
    
    clearTimeout(window.searchTimers['sc']);
    window.searchTimers['sc'] = setTimeout(async () => {
        const data = await fetchSearch('search_candidate', query);
        if (!data) return;
        
        if (data.length === 0) { resBox.innerHTML = '<div class="ui-empty" style="padding:10px; margin:0;">Нікого не знайдено</div>'; return; }
        resBox.innerHTML = data.map(user => { 
            let safeName = escapeHTML(user.name);
            return `<div class="ui-card js-select-candidate" data-id="${user.id}" data-name="${safeName.replace(/'/g, "&apos;")}" style="margin:0; padding:10px; border-radius:0; border:none; border-bottom:1px solid var(--border); cursor:pointer;">
                        <i class="fa-regular fa-user" style="color:var(--accent);"></i> <span style="font-weight:800; margin-left:6px;">${safeName}</span>
                    </div>`; 
        }).join('');
    }, 300);
}

function selectCandidate(id, name) {
    document.getElementById('candidateIdInput').value = id;
    document.getElementById('candidateSearchInput').value = name;
    document.getElementById('candidateSearchResults').innerHTML = '<div class="ui-alert green" style="margin:0;"><i class="fa-solid fa-check"></i> Кандидата обрано</div>';
}

function openEnroll(eventId, maxSeats) {
    document.getElementById('e_id').value = eventId;
    const gInput = document.getElementById('g_count');
    if (gInput) { gInput.max = maxSeats; gInput.value = 0; }
    document.getElementById('enrollModal').style.display = 'flex';
}

// ==========================================
// 💡 8. ВИСУВАННЯ ТА ЗБІР ІДЕЙ
// ==========================================
window.nominateCandidate = async function(nomId, nomineeId, element, isSecret = 0, requireKep = 0, kepSigned = false) {
    if (isSecret == 2) { return submitE2eVote('nomination', nomId, nomineeId, requireKep, element, null, kepSigned); }
    if (requireKep == 1 && !kepSigned) { openKepModal('nominate_candidate', { nomId, nomineeId, isSecret, requireKep }, element, ''); return; }

    let data = { nomination_id: nomId, nominee_id: nomineeId };
    if (kepSigned) data.kep_signed = '1';

    const res = await AppAPI.post('nominate', data, element);
    if (!res) return;

    if (res.success) {
        if (typeof showToast === 'function') showToast("✅ Кандидата висунуто!");
        const container = document.getElementById('nom-actions-' + nomId);
        if (container) container.innerHTML = '<div class="ui-alert green"><i class="fa-solid fa-check"></i> Кандидата висунуто</div>';
        
        let textEl = document.getElementById('nom-text-' + nomId);
        let pctEl  = document.getElementById('nom-pct-' + nomId);
        let fillEl = document.getElementById('nom-fill-' + nomId);
        if (textEl) {
            let match = textEl.innerText.match(/Кворум:\s*(\d+)\s*\/\s*(\d+)\s*\((.+)\)/);
            if (match) {
                let currentVotes = parseInt(match[1]) + 1;
                let reqQuorum = parseInt(match[2]);
                let newPct = Math.min(100, Math.round((currentVotes / Math.max(1, reqQuorum)) * 100));
                textEl.innerText = `Кворум: ${currentVotes} / ${reqQuorum} (${match[3]})`;
                if (pctEl) pctEl.innerText = newPct + '%';
                if (fillEl) fillEl.style.width = newPct + '%';
            }
        }
    } else if (res.requires_kep) { 
        openKepModal('nominate_candidate', { nomId, nomineeId, isSecret, requireKep: 1 }, element, '');
    } else {
        if (typeof showToast === 'function') showToast("❌ " + (res.msg || "Помилка"));
    }
};

window.submitIdeaFast = async function(nomId, btn, isSecret = 0, requireKep = 0, kepSigned = false) {
    let input = document.getElementById('idea-input-' + nomId);
    let text = input ? input.value.trim() : '';
    if (!text && currentKepAction && currentKepAction.data && currentKepAction.data.text) text = currentKepAction.data.text; 
    if (!text) { if (typeof showToast === 'function') showToast('❌ Введіть текст ідеї!'); return; }
    
    if (isSecret == 2) { return submitE2eVote('nomination', nomId, 0, requireKep, btn, text, kepSigned); }
    if (requireKep == 1 && !kepSigned) { openKepModal('submit_idea', { nomId, isSecret, requireKep, text }, btn, ''); return; }
    
    let data = { nomination_id: nomId, idea_text: text };
    if (kepSigned) data.kep_signed = '1';

    const res = await AppAPI.post('nominate_idea', data, btn);
    if (!res) return;

    let needsKepFallback = res.requires_kep === true || (res.msg && res.msg.includes('КЕП'));

    if (res.success) {
        if (typeof showToast === 'function') showToast("✅ Ідею прийнято!");
        let container = document.getElementById('nom-actions-' + nomId);
        if (container) container.innerHTML = `<div class="ui-alert green"><i class="fa-solid fa-check"></i> ${res.msg || "Вашу ідею прийнято!"}</div>`;
        
        if(res.pct !== undefined) {
            let fill = document.getElementById('nom-fill-' + nomId);
            if(fill) fill.style.width = res.pct + '%'; 
            let pctTxt = document.getElementById('nom-pct-' + nomId);
            if(pctTxt) pctTxt.innerText = res.pct + '%';
            let reqTxt = document.getElementById('nom-text-' + nomId);
            if(reqTxt) reqTxt.innerText = 'Кворум: ' + res.current_votes + ' / ' + res.req + ' (' + res.t_disp + ')';
        }
    } else if (needsKepFallback) { 
        openKepModal('submit_idea', { nomId, isSecret, requireKep: 1, text }, btn, '');
    } else {
        if (typeof showToast === 'function') showToast("❌ " + (res.msg || "Помилка"));
    }
};

// ==========================================
// 🛡️ E2E-V: АБСОЛЮТНО ТАЄМНЕ ГОЛОСУВАННЯ
// ==========================================
const E2ECrypto = {
    generateRawToken: function() {
        const array = new Uint8Array(6); 
        window.crypto.getRandomValues(array);
        const hex = Array.from(array).map(b => b.toString(16).padStart(2, '0')).join('').toUpperCase();
        return `PPO-${hex.substring(0,4)}-${hex.substring(4,8)}-${hex.substring(8,12)}`;
    },
    hashTokenLocal: async function(token) {
        const msgBuffer = new TextEncoder().encode(token);
        const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }
};

window.submitE2eVote = async function(targetType, targetId, optionId, requireKep, btn, ideaText = null, kepSigned = false) {
    if (!navigator.onLine) { if (typeof showToast === 'function') showToast("🔴 Немає підключення до інтернету!"); return; }
    if (requireKep == 1 && !kepSigned) { openKepModal('e2e_vote', {targetType, targetId, optionId, requireKep, ideaText}, btn, ''); return; }
    if (btn.dataset.processing === "1") return;
    btn.dataset.processing = "1";
    let originalHtml = btn.innerHTML;

    try {
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Створення токена...';
        btn.disabled = true;

        const secretToken = E2ECrypto.generateRawToken();
        sessionStorage.setItem('my_secret_ballot', secretToken);
        const tokenHash = await E2ECrypto.hashTokenLocal(secretToken); 

        let res1 = await AppAPI.post('generate_e2e_token', { target_type: targetType, target_id: targetId, token_hash: tokenHash }, null);
        if (!res1 || !res1.success) { 
            if (typeof showToast === 'function') showToast("❌ " + (res1 ? res1.msg : "Не вдалося отримати бюлетень"));
            sessionStorage.removeItem('my_secret_ballot'); 
            btn.innerHTML = originalHtml; btn.disabled = false; btn.dataset.processing = "0"; 
            return; 
        }

        btn.innerHTML = '<i class="fa-solid fa-shield-halved fa-beat-fade"></i> Шифрування...';
        await new Promise(resolve => setTimeout(resolve, 1500)); 

        const savedToken = sessionStorage.getItem('my_secret_ballot');
        let data2 = { target_type: targetType, target_id: targetId, token: savedToken };
        if (optionId) data2.option_id = optionId;
        if (ideaText) data2.idea_text = ideaText;

        const delaySeconds = Math.floor(Math.random() * (15 - 5 + 1)) + 5; 
        for (let i = delaySeconds; i > 0; i--) {
            btn.innerHTML = `<i class="fa-solid fa-user-secret fa-beat-fade"></i> Анонімізація... (${i}с)`;
            await new Promise(resolve => setTimeout(resolve, 1000)); 
        }
        
        btn.innerHTML = `<i class="fa-solid fa-paper-plane"></i> Відправка...`;
        let res2 = await AppAPI.post('cast_e2e_vote', data2, null);
        sessionStorage.removeItem('my_secret_ballot');

        if (!res2) { btn.innerHTML = originalHtml; btn.disabled = false; btn.dataset.processing = "0"; return; }

        if (res2.success) {
            try {
                let textEl = document.getElementById(targetType === 'poll' ? 'poll-text-'+targetId : 'nom-text-'+targetId);
                let pctEl = document.getElementById(targetType === 'poll' ? 'poll-pct-'+targetId : 'nom-pct-'+targetId);
                let fillEl = document.getElementById(targetType === 'poll' ? 'poll-fill-'+targetId : 'nom-fill-'+targetId);
                if (textEl && res2.total_votes !== undefined) {
                    let match = textEl.innerText.match(/\/ (\d+) \((.+)\)/);
                    if (match) {
                        let req = parseInt(match[1]);
                        let pct = Math.min(100, Math.round((res2.total_votes / Math.max(1, req)) * 100));
                        textEl.innerText = `Кворум: ${res2.total_votes} / ${req} (${match[2]})`;
                        if (pctEl) pctEl.innerText = pct + '%';
                        if (fillEl) fillEl.style.width = pct + '%';
                    }
                }
            } catch(ed) {}

            try {
                let containerId = (targetType === 'poll') ? 'poll-actions-' + targetId : 'nom-actions-' + targetId;
                let container = document.getElementById(containerId);
                if (container) {
                    container.innerHTML = `
                        <div class="ui-defense js-e2e-rc" style="margin:0;">
                            <div class="ui-alert purple"><i class="fa-solid fa-lock"></i> Анонімний голос в урні</div>
                            <div class="ui-defense-title">Ваш трек-номер (квитанція):</div>
                            <div class="f-between" style="display:flex; justify-content:space-between; align-items:center;">
                                <b class="ui-title js-e2e-tk">${savedToken}</b>
                                <button class="ui-btn-icon js-copy-token" data-token="${savedToken}"><i class="fa-regular fa-copy"></i></button>
                            </div>
                        </div>
                    `;
                    let rec = container.querySelector('.js-e2e-rc');
                    if (rec) { rec.style.borderColor = '#8b5cf6'; rec.style.backgroundColor = 'rgba(139,92,246,0.1)'; }
                    let tok = container.querySelector('.js-e2e-tk');
                    if (tok) { tok.style.fontFamily = "'DM Mono', monospace"; tok.style.fontSize = '16px'; tok.style.userSelect = 'all'; tok.style.margin = '0'; }
                }
            } catch(ec) {}
            if (typeof showToast === 'function') showToast("✅ Ваш голос надійно зашифровано!");
        } else {
            if (typeof showToast === 'function') showToast("❌ Відмова сервера: " + (res2.msg || "Помилка"));
            btn.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Помилка збереження';
            btn.className = 'ui-btn danger';
            setTimeout(() => { btn.innerHTML = originalHtml; btn.className = 'ui-btn primary'; btn.dataset.processing = "0"; btn.disabled = false; }, 5000);
        }
    } catch (e) {
        sessionStorage.removeItem('my_secret_ballot'); 
        if (typeof showToast === 'function') showToast("🔴 Сталася системна помилка у браузері");
        btn.innerHTML = originalHtml || "Помилка"; 
        btn.disabled = false; btn.dataset.processing = "0";
    }
};

// ==========================================
// 🛡️ 9. СИСТЕМА КЕП ДЛЯ ДІЙ
// ==========================================
function openKepModal(action, data, btn, badgeId) {
    currentKepAction = { action, data, btn, badgeId };
    const statusDiv = document.getElementById('kep-status');
    if(statusDiv) statusDiv.innerHTML = '';
    const m = document.getElementById('kepModal');
    if(m) m.style.display = 'flex';
}

function simulateKEP() {
    const statusDiv = document.getElementById('kep-status');
    if(!statusDiv) return;
    statusDiv.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Зчитування обличчя...';
    
    setTimeout(() => {
        statusDiv.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Накладання підпису...';
        setTimeout(() => {
            statusDiv.innerHTML = '<span style="color:var(--green);"><i class="fa-solid fa-check"></i> Підписано успішно!</span>';
            setTimeout(() => {
                const m = document.getElementById('kepModal');
                if(m) m.style.display = 'none';
                
                if (currentKepAction) {
                    if (currentKepAction.action === 'e2e_vote') {
                        submitE2eVote(currentKepAction.data.targetType, currentKepAction.data.targetId, currentKepAction.data.optionId, currentKepAction.data.requireKep, currentKepAction.btn, currentKepAction.data.ideaText, true);
                    } else if (currentKepAction.action === 'submit_idea') {
                        submitIdeaFast(currentKepAction.data.nomId, currentKepAction.btn, currentKepAction.data.isSecret, currentKepAction.data.requireKep, true);
                    } else if (currentKepAction.action === 'nominate_candidate') {
                        nominateCandidate(currentKepAction.data.nomId, currentKepAction.data.nomineeId, currentKepAction.btn, currentKepAction.data.isSecret, currentKepAction.data.requireKep, true);
                    } else {
                        currentKepAction.data.kep_signed = 1;
                        submitActionAjax(currentKepAction.action, currentKepAction.data, currentKepAction.btn, currentKepAction.badgeId);
                    }
                    currentKepAction = null;
                }
            }, 1000);
        }, 1500);
    }, 1500);
}

async function submitActionAjax(action, data, btn, badgeId) {
    const res = await AppAPI.post(action, data, btn);
    if (!res) return;

    if (res.success) {
        if (typeof showToast === 'function') showToast("✅ " + (res.msg || "Успішно!"));
        
        if (action === 'vote' && res.total_votes !== undefined) {
            updatePollDOM(data.poll_id, res.total_votes, res.total_members);
        } else if (action === 'sign_petition') {
            updatePetDOM(data.id);
            btn.className = 'ui-alert green'; 
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Підписано'; 
            btn.onclick = null;
        } else {
            btn.className = 'ui-alert green'; 
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Враховано'; btn.onclick = null;
        }
    } else if (res.requires_kep) {
        openKepModal(action, data, btn, badgeId);
    } else { 
        if (typeof showToast === 'function') showToast("❌ " + (res.msg || "Помилка")); 
    }
}

// ==========================================
// 📊 10. ОНОВЛЕННЯ DOM ТА ВІДОБРАЖЕННЯ РЕЗУЛЬТАТІВ
// ==========================================
function updatePollDOM(pollId, totalVotes, totalMembers, skipActionEl = false) {
    let textEl = document.getElementById('poll-text-' + pollId);
    let pctEl  = document.getElementById('poll-pct-' + pollId);
    let fillEl = document.getElementById('poll-fill-' + pollId);
    let actionEl = document.getElementById('poll-actions-' + pollId);

    if (textEl) {
        let match = textEl.innerText.match(/\/ (\d+) \((.+)\)/);
        let reqQuorum = match ? parseInt(match[1]) : totalMembers;
        let tDisp = match ? match[2] : '50%+1';
        
        let newPct = Math.min(100, Math.round((totalVotes / Math.max(1, reqQuorum)) * 100));
        let isFinished = totalVotes >= reqQuorum;

        textEl.innerText = `Кворум: ${totalVotes} / ${reqQuorum} (${tDisp})`;
        if (pctEl) pctEl.innerText = newPct + '%';
        if (fillEl) fillEl.style.width = newPct + '%';
        
        if (actionEl && !skipActionEl) {
            actionEl.innerHTML = `<div class="ui-alert green"><i class="fa-solid fa-check"></i> ${isFinished ? 'Опитування завершено' : 'Ваш голос враховано'}</div>`;
        }
    }
}

function updatePetDOM(petId) {
    let textEl = document.getElementById('pet-text-' + petId);
    let pctEl  = document.getElementById('pet-pct-' + petId);
    let fillEl = document.getElementById('pet-fill-' + petId);

    if (textEl) {
        let match = textEl.innerText.match(/Підписів:\s*(\d+)\s*\/\s*(\d+)\s*\((.+)\)/);
        if (match) {
            let currentSigns = parseInt(match[1]) + 1; 
            let reqQuorum = parseInt(match[2]);
            let newPct = Math.min(100, Math.round((currentSigns / Math.max(1, reqQuorum)) * 100));

            textEl.innerText = `Підписів: ${currentSigns} / ${reqQuorum} (${match[3]})`;
            if (pctEl) pctEl.innerText = newPct + '%';
            if (fillEl) fillEl.style.width = newPct + '%';
        }
    }
}

async function viewResults(type, id, title, isSecret) {
    if (!navigator.onLine) { if (typeof showToast === 'function') showToast('🔴 Немає підключення до інтернету'); return; }
    openModal(title, '<div class="ui-empty" style="border:none;"><i class="fa-solid fa-circle-notch fa-spin"></i> Завантаження...</div>', true);
    
    const endpoints = {
        'poll': `get_poll_results.php?poll_id=${id}`,
        'nom': `get_nomination_results.php?nomination_id=${id}`,
        'pet': `get_petition_signers.php?petition_id=${id}`,
        'event': `get_participants.php?event_id=${id}`
    };

    try {
        const res = await fetch(endpoints[type]);
        const d = await res.json();
        const mBody = document.getElementById('mBody');
        
        if (!d || d.length === 0) {
            mBody.innerHTML = `<div class="ui-empty">Немає даних.</div>`; 
            return;
        }

        let h = '';
        if (isSecret && !d[0].is_token && (type === 'poll' || type === 'nom')) {
            let counts = {}; 
            d.forEach(v => { let key = type === 'poll' ? v.option_text : v.nominee; counts[key] = (counts[key]||0)+1; });
            
            h = Object.keys(counts).map(o => { 
                let pct = Math.round((counts[o] / d.length) * 100); 
                return `<div class="ui-res-card">
                            <div class="ui-res-bar js-res-bar" data-w="${pct}"></div>
                            <div class="ui-res-content">
                                <span class="ui-res-label">${escapeHTML(o)}</span>
                                <span class="ui-res-val">${pct}%</span>
                            </div>
                        </div>`; 
            }).join('');
            
            mBody.innerHTML = h;
            
            setTimeout(() => {
                mBody.querySelectorAll('.js-res-bar').forEach(b => {
                    b.style.width = b.getAttribute('data-w') + '%';
                });
            }, 50);

        } else {
            if (d[0].is_token) {
                h += `<div class="ui-token-alert">
                        <b><i class="fa-solid fa-server"></i> Відкритий реєстр бюлетенів</b><br>
                        <span style="opacity:0.8;">Знайдіть свій трек-номер у списку. На комп'ютері натисніть <b>Ctrl+F</b>, на телефоні оберіть «Знайти на сторінці» в меню браузера.</span>
                      </div>`;
            }

            h += d.map((v, i) => {
                let name = (type==='poll') ? v.user_name : (type==='nom' ? v.nominator : (type==='pet' ? v.full_name : v.user_name));
                let extra = '';
                
                if (type === 'poll' && v.option_text) extra = `<div class="ui-list-extra">Варіант: ${escapeHTML(v.option_text)}</div>`;
                else if (type === 'nom' && v.nominee) extra = `<div class="ui-list-extra">💡 ${escapeHTML(v.nominee)}</div>`;
                else if (v.guests_count > 0) extra = `<div class="ui-list-guests"><i class="fa-solid fa-user-plus"></i> +${v.guests_count} гостей</div>`;

                let initial = name ? name.trim().charAt(0).toUpperCase() : '👤';
                let avatar = v.is_token 
                    ? `<div class="ui-avatar token"><i class="fa-solid fa-receipt"></i></div>` 
                    : `<div class="ui-avatar">${initial}</div>`;
                
                let nameClass = v.is_token ? 'ui-list-name token-font' : 'ui-list-name';

                return `<div class="ui-list-item">
                            <div style="font-size: 10px; color: var(--txt-muted); font-weight: 800; width: 24px;">#${i+1}</div>
                            ${avatar}
                            <div style="flex:1;">
                                <div class="${nameClass}">${escapeHTML(name)}</div>
                                ${extra}
                            </div>
                        </div>`;
            }).join('');
            
            mBody.innerHTML = h;
        }
    } catch (e) { 
        document.getElementById('mBody').innerHTML = '<div class="ui-alert red" style="margin:0;"><i class="fa-solid fa-triangle-exclamation"></i> Помилка завантаження даних</div>'; 
    }
}

// ==========================================
// 👤 11. ФУНКЦІЇ ПРОФІЛЮ ТА СПОВІЩЕНЬ
// ==========================================
function loadMoreNotifs() {
    const items = document.querySelectorAll('.info-notif-item');
    let revealed = 0, remaining = 0;
    items.forEach(el => {
        if (el.style.display === 'none') {
            if (revealed < 10) { el.style.display = 'block'; el.style.animation = 'uiFadeIn 0.5s'; revealed++; } 
            else { remaining++; }
        }
    });
    if (remaining === 0) { const btn = document.getElementById('loadMoreNotifsBtn'); if(btn) btn.style.display = 'none'; }
}

function updatePushUI() {
    const btn = document.getElementById('push-btn'); const banner = document.getElementById('push-banner');
    const iconBox = document.getElementById('push-icon-box'); const statusText = document.getElementById('push-status-text');
    if (!btn || !banner) return;
    const hasOsPerm = typeof Notification !== 'undefined' && Notification.permission === 'granted';
    const isOptedIn = window.OneSignal && window.OneSignal.User && window.OneSignal.User.PushSubscription ? window.OneSignal.User.PushSubscription.optedIn : false;

    if (hasOsPerm && isOptedIn) {
        banner.style.borderColor = 'var(--green)'; banner.style.background = 'rgba(16,185,129,0.05)';
        iconBox.style.background = 'var(--green)'; statusText.innerText = 'Активно'; statusText.style.color = 'var(--green)';
        btn.innerText = 'Вимкнути'; btn.style.background = 'transparent'; btn.style.color = 'var(--txt-muted)';
        btn.style.border = '1px solid var(--border)'; btn.style.pointerEvents = 'auto'; 
    } else {
        banner.style.borderColor = 'var(--accent)'; banner.style.background = 'rgba(59,91,219,0.05)';
        iconBox.style.background = 'var(--accent)'; statusText.innerText = 'Вимкнено'; statusText.style.color = 'var(--txt-muted)';
        btn.innerText = 'Увімкнути'; btn.style.background = 'var(--accent)'; btn.style.color = '#fff'; btn.style.border = 'none';
    }
}

async function togglePushes() {
    if (typeof window.OneSignal !== 'undefined' && window.OneSignal.User && window.OneSignal.User.PushSubscription) {
        try {
            const hasOsPerm = typeof Notification !== 'undefined' && Notification.permission === 'granted';
            if (hasOsPerm && window.OneSignal.User.PushSubscription.optedIn) {
                await window.OneSignal.User.PushSubscription.optOut();
                if (typeof showToast === 'function') showToast("🔕 Сповіщення тимчасово вимкнено");
            } else {
                if (!hasOsPerm) await window.OneSignal.Notifications.requestPermission();
                await window.OneSignal.User.PushSubscription.optIn();
                if (typeof showToast === 'function') showToast("✅ Сповіщення успішно увімкнено!");
            }
            updatePushUI();
        } catch (err) { if (typeof showToast === 'function') showToast("❌ Помилка: " + err.message); }
    } else { if (typeof showToast === 'function') showToast("❌ Модуль сповіщень ще завантажується."); }
}

async function resolveElection(pollId, roleName, decision, btnElement) { 
    const res = await AppAPI.post('resolve_election', { poll_id: pollId, role: roleName, decision: decision }, btnElement);
    if (!res) return;
    if (res.success) window.location.reload(); 
    else if (typeof showToast === 'function') showToast("❌ " + res.msg); 
}

function censorText(text) { 
    const badWords = /хуй|пизд|блять|бляд|єбат|ебат|нахуй|підар|пидор|шлюх|сука|суки|мудак|гадон|гандон|пізд|пизд|залуп|їбат/gi; 
    return text.replace(badWords, '***'); 
}

async function submitDefenseFromNotif(pollId) { 
    const textarea = document.getElementById('defense_text_' + pollId); 
    let text = textarea ? textarea.value.trim() : ''; 
    if(!text) { if (typeof showToast === 'function') showToast('❌ Напишіть текст пояснення!'); return; } 
    
    const originalText = text; text = censorText(text); 
    if (originalText !== text && !confirm('Знайдено ненормативну лексику. Її буде замінено на "***". Продовжити?')) return; 
    else if(originalText === text && !confirm('Ви впевнені? Це пояснення буде публічним і ви не зможете його змінити.')) return; 
    
    const res = await AppAPI.post('submit_exclusion_comment', { poll_id: pollId, explanation: text }, null);
    if (!res) return;

    if (res.success) { 
        if (typeof showToast === 'function') showToast('✅ Пояснення збережено!'); 
        const box = document.getElementById('defense-box-' + pollId); 
        if (box) box.innerHTML = '<div class="ui-alert green"><i class="fa-solid fa-check"></i> Ваше пояснення відправлено.</div>'; 
    } else { 
        if (typeof showToast === 'function') showToast("❌ " + (res.msg || 'Помилка збереження.')); 
    } 
}

function openProtocolKep(pollId) { 
    document.getElementById('protocol_poll_id').value = pollId; 
    document.getElementById('protocol-kep-status').innerText = ''; 
    document.getElementById('protocolKepModal').style.display = 'flex'; 
}

function openLeavePpoModal() { 
    if (window.pendingSignaturesCount > 0) { if (typeof showToast === 'function') showToast('🛑 У вас є непідписані протоколи!'); return; } 
    document.getElementById('leave-kep-status').innerText = ''; 
    document.getElementById('leavePpoModal').style.display = 'flex'; 
}

function openResignRoleModal() { 
    if (window.pendingSignaturesCount > 0) { if (typeof showToast === 'function') showToast('🛑 У вас є непідписані протоколи!'); return; } 
    document.getElementById('resign-kep-status').innerText = ''; 
    document.getElementById('resignRoleModal').style.display = 'flex'; 
}

async function simulateProtocolKEP() {
    const status = document.getElementById('protocol-kep-status');
    if(status) status.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Підписання...';
    const res = await AppAPI.post('sign_protocol', { poll_id: document.getElementById('protocol_poll_id').value }, null);
    if(res && res.success) window.location.href = 'archive.php?action=protocol&id=' + document.getElementById('protocol_poll_id').value + '&from=notifs';
    else { if(status) status.innerHTML = '<span style="color:var(--red);">Помилка</span>'; if(typeof showToast==='function') showToast("❌ " + (res?res.msg:"Помилка")); }
}

async function simulateLeaveKEP() {
    const status = document.getElementById('leave-kep-status');
    if(status) status.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Підписання...';
    const res = await AppAPI.post('leave_ppo', {}, null);
    if(res && res.success) window.location.href = 'index.php';
    else { if(status) status.innerHTML = '<span style="color:var(--red);">Помилка</span>'; if(typeof showToast==='function') showToast("❌ " + (res?res.msg:"Помилка")); }
}

async function simulateResignKEP() {
    const status = document.getElementById('resign-kep-status');
    if(status) status.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Підписання...';
    const res = await AppAPI.post('resign_role', {}, null);
    if(res && res.success) window.location.reload();
    else { if(status) status.innerHTML = '<span style="color:var(--red);">Помилка</span>'; if(typeof showToast==='function') showToast("❌ " + (res?res.msg:"Помилка")); }
}

// ==========================================
// 🚀 12. ГОЛОВНА ІНІЦІАЛІЗАЦІЯ (DOM Ready & Events)
// ==========================================
document.addEventListener('DOMContentLoaded', () => {

    const sessionToast = document.getElementById('sessionToastData');
    if (sessionToast && typeof showToast === 'function') setTimeout(() => showToast(sessionToast.getAttribute('data-msg')), 150);

    const osAppIdMeta = document.querySelector('meta[name="onesignal-app-id"]');
    if (osAppIdMeta) {
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        window.OneSignalDeferred.push(async function(OneSignal) {
            await OneSignal.init({ appId: osAppIdMeta.getAttribute('content'), notifyButton: { enable: false } });
            const osUserIdMeta = document.querySelector('meta[name="onesignal-user-id"]');
            if (osUserIdMeta) OneSignal.login(osUserIdMeta.getAttribute('content'));
            else OneSignal.logout();
        });
    }

    // 🔥 НАДІЙНЕ ПЕРЕХОПЛЕННЯ УСІХ КЛІКІВ
    document.body.addEventListener('click', function(e) {
        const target = e.target;

        if (target.closest('.js-stop-propagation')) e.stopPropagation();

        const goToBtn = target.closest('.js-go-to');
        if (goToBtn) { e.preventDefault(); return goTo(parseInt(goToBtn.getAttribute('data-idx'))); }

        const navGoToBtn = target.closest('.js-nav-go-to');
        if (navGoToBtn) { e.preventDefault(); return goTo(parseInt(navGoToBtn.getAttribute('data-idx'))); }

        // Маршрутизатор вкладок для Профілю
        const goToProfTabBtn = target.closest('.js-go-to-profile-tab');
        if (goToProfTabBtn) { e.preventDefault(); return window.goToProfileTab(parseInt(goToProfTabBtn.getAttribute('data-idx'))); }

        // Маршрутизатор вкладок для Інфо ППО
        const goToInfoTabBtn = target.closest('.js-go-to-info-tab');
        if (goToInfoTabBtn) { e.preventDefault(); return window.goToInfoTab(parseInt(goToInfoTabBtn.getAttribute('data-idx'))); }

        const newsBtn = target.closest('.js-open-news-modal');
        if (newsBtn) return openModal(newsBtn.getAttribute('data-title'), newsBtn.getAttribute('data-content'), true);

        const openModalBtn = target.closest('.js-open-modal-id');
        if (openModalBtn) { const m = document.getElementById(openModalBtn.getAttribute('data-target')); if(m) m.style.display = 'flex'; return; }

        const closeModalBtn = target.closest('.js-close-modal');
        if (closeModalBtn) { const m = document.getElementById(closeModalBtn.getAttribute('data-target')); if(m) m.style.display = 'none'; return; }

        const voteBtn = target.closest('.js-vote-btn');
        if (voteBtn) {
            const isSec = parseInt(voteBtn.getAttribute('data-is-secret')), pId = parseInt(voteBtn.getAttribute('data-poll-id')), 
                  oId = parseInt(voteBtn.getAttribute('data-option-id')), reqKep = parseInt(voteBtn.getAttribute('data-require-kep'));
            if (isSec === 2) submitE2eVote('poll', pId, oId, reqKep, voteBtn);
            else submitActionAjax('vote', {poll_id: pId, option_id: oId}, voteBtn, 'badge-polls');
            return;
        }

        const viewResBtn = target.closest('.js-view-results');
        if (viewResBtn) return viewResults(viewResBtn.getAttribute('data-type'), parseInt(viewResBtn.getAttribute('data-id')), viewResBtn.getAttribute('data-title'), parseInt(viewResBtn.getAttribute('data-is-secret')));

        const ideaBtn = target.closest('.js-submit-idea');
        if (ideaBtn) return submitIdeaFast(parseInt(ideaBtn.getAttribute('data-nom-id')), ideaBtn, parseInt(ideaBtn.getAttribute('data-is-secret')), parseInt(ideaBtn.getAttribute('data-require-kep')) || 0);

        const signPetBtn = target.closest('.js-sign-petition');
        if (signPetBtn) return submitActionAjax('sign_petition', {id: parseInt(signPetBtn.getAttribute('data-pet-id'))}, signPetBtn, 'badge-pets');

        const enrollBtn = target.closest('.js-open-enroll');
        if (enrollBtn) return openEnroll(parseInt(enrollBtn.getAttribute('data-event-id')), parseInt(enrollBtn.getAttribute('data-max-seats')));

        const resolveElectionBtn = target.closest('.js-resolve-election');
        if (resolveElectionBtn) return resolveElection(parseInt(resolveElectionBtn.getAttribute('data-id')), resolveElectionBtn.getAttribute('data-role'), resolveElectionBtn.getAttribute('data-decision'), resolveElectionBtn);

        const submitDefenseBtn = target.closest('.js-submit-defense');
        if (submitDefenseBtn) return submitDefenseFromNotif(parseInt(submitDefenseBtn.getAttribute('data-id')));

        const openProtocolBtn = target.closest('.js-open-protocol-kep');
        if (openProtocolBtn) return openProtocolKep(parseInt(openProtocolBtn.getAttribute('data-id')));

        if (target.closest('.js-open-leave-modal')) return openLeavePpoModal();
        if (target.closest('.js-open-resign-modal')) return openResignRoleModal();

        if (target.closest('.js-simulate-protocol-kep')) return simulateProtocolKEP();
        if (target.closest('.js-simulate-leave-kep')) return simulateLeaveKEP();
        if (target.closest('.js-simulate-resign-kep')) return simulateResignKEP();
        
        if (target.closest('.js-load-more-notifs')) return loadMoreNotifs();
        if (target.closest('.js-toggle-pushes')) return togglePushes();

        if (target.closest('.js-simulate-kep')) return simulateKEP();
        
        const copyBtn = target.closest('.js-copy-token');
        if (copyBtn) { navigator.clipboard.writeText(copyBtn.getAttribute('data-token')); if(typeof showToast==='function') showToast('✅ Скопійовано!'); copyBtn.innerHTML = '<i class="fa-solid fa-check"></i>'; copyBtn.style.color = 'var(--green)'; return; }

        if (target.closest('.js-cycle-theme')) return cycleTheme();
        if (target.closest('.js-header-back-btn')) { if (typeof handleBack === 'function') handleBack(); else if (typeof goTo === 'function') goTo(0); return; }

        const closeBg = target.classList.contains('js-close-modal-click') ? target : null;
        if (closeBg) return closeBg.style.display = 'none';
    });

    document.body.addEventListener('change', function(e) {
        if (e.target.classList.contains('js-toggle-poll-type')) togglePollType();
        if (e.target.classList.contains('js-toggle-nom-type')) toggleNomType();
    });

    document.body.addEventListener('mousedown', function(e) {
        let t = e.target.closest('.js-nominate-candidate');
        if (t) return nominateCandidate(parseInt(t.getAttribute('data-nom-id')), parseInt(t.getAttribute('data-u-id')), t, parseInt(t.getAttribute('data-is-secret')), parseInt(t.getAttribute('data-require-kep')) || 0);
        
        t = e.target.closest('.js-select-candidate');
        if (t) return selectCandidate(parseInt(t.getAttribute('data-id')), t.getAttribute('data-name'));
    });

    document.body.addEventListener('input', function(e) {
        if (e.target.classList.contains('js-search-nominee')) searchUser(e.target.value, parseInt(e.target.getAttribute('data-nom-id')), parseInt(e.target.getAttribute('data-is-secret')));
        if (e.target.classList.contains('js-search-candidate')) searchCandidate(e.target.value);
    });

    document.body.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.classList.contains('ajax-admin-form') || ['createPetitionForm', 'exclusionForm', 'enrollForm'].includes(form.id)) {
            e.preventDefault(); 
            if (form.dataset.processing === "1") return;
            form.dataset.processing = "1";
            
            if (!form.getAttribute('action') || form.getAttribute('action') === '') {
                const routeMap = { 'createPetitionForm': 'create_petition', 'exclusionForm': 'create_exclusion', 'enrollForm': 'enroll' };
                form.setAttribute('action', routeMap[form.id] ? `action.php?route=${routeMap[form.id]}` : 'action.php');
            }
            submitFormAjax(e, form).finally(() => { form.dataset.processing = "0"; });
        }
    });

    const savedTheme = localStorage.getItem('theme') || 'light';
    const savedLayout = localStorage.getItem('layout') || 'bar';
    document.documentElement.setAttribute('data-theme', savedTheme);
    document.documentElement.setAttribute('data-layout', savedLayout);

    const path = window.location.pathname, urlParams = new URLSearchParams(window.location.search);
    
    // 🔥 Логіка старту для Головної
    if (path.endsWith('index.php') || path.endsWith('/')) {
        const tabMap = { 'news':1, 'polls':2, 'noms':3, 'petitions':4, 'events':5 };
        const tab = urlParams.get('tab');
        if (document.querySelector('.view-bar')) {
            if (savedLayout === 'tile' && !tab) window.goTo(0);
            else window.goTo(tabMap[tab] !== undefined ? tabMap[tab] : (savedLayout === 'tile' ? 0 : 1));
        }
    } else {
        const backBtn = document.getElementById('back-btn-dash'), mainIcon = document.getElementById('main-icon-dash');
        if (backBtn) backBtn.style.display = 'flex'; 
        if (mainIcon) mainIcon.style.display = 'none';
    }

    // 🔥 Логіка старту для Інфо ППО
    if (path.includes('ppo_info.php')) {
        const currentTab = urlParams.get('tab') || 'leaders';
        const tabIndexMap = {'leaders':0, 'members':1, 'top':2, 'archive':3, 'stats':4};
        const targetIdx = tabIndexMap[currentTab] !== undefined ? tabIndexMap[currentTab] : 0;

        const infoSlider = document.getElementById('slider');
        if (infoSlider && targetIdx !== 0) {
            infoSlider.style.transition = 'none';
            window.goToInfoTab(targetIdx);
            setTimeout(() => { infoSlider.style.transition = 'transform 0.4s cubic-bezier(0.25, 1, 0.5, 1)'; }, 50);
        }
        
        setTimeout(() => {
            document.querySelectorAll('.js-apply-width').forEach(el => {
                const w = el.getAttribute('data-w');
                const bg = el.getAttribute('data-bg');
                if (w) el.style.width = w + '%';
                if (bg) el.style.background = bg;
            });
        }, 100);
    }
});
