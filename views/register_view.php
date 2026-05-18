<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo-glow">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h2>Реєстрація</h2>
            <p>Створення запиту на вступ</p>
        </div>

        <?php if(isset($error) && $error): ?>
            <div class="auth-alert error">
                <i class="fa-solid fa-circle-exclamation"></i> 
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if(isset($success) && $success): ?>
            <div class="auth-alert success">
                <i class="fa-solid fa-circle-check auth-alert-icon-lg"></i> 
                <div>
                    <?= htmlspecialchars($success) ?>
                    <div class="auth-alert-link-wrap">
                        <a href="login.php">Повернутися до входу</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                <div class="input-group">
                    <label class="label">Організація ППО</label>
                    <i class="fa-solid fa-sitemap left-icon"></i>
                    <select name="ppo_id" class="input-dark auth-input" required>
                        <option value="" disabled selected>Оберіть вашу ППО...</option>
                        <?php if(!empty($ppos)): foreach($ppos as $ppo): ?>
                            <!-- 🛡️ ФІКС 1: Приведення до (int) замість важкого htmlspecialchars -->
                            <option value="<?= (int)$ppo['id'] ?>"><?= htmlspecialchars($ppo['name']) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down right-icon"></i>
                </div>

                <div class="input-group">
                    <label class="label">Повне ім'я (ПІБ)</label>
                    <i class="fa-solid fa-id-card left-icon"></i>
                    <!-- 🛡️ ФІКС 2: Вимкнено Т9, увімкнено капіталізацію слів -->
                    <input type="text" name="full_name" class="input-dark auth-input" placeholder="Прізвище Ім'я По-батькові" maxlength="100" autocorrect="off" autocapitalize="words" required>
                </div>

                <div class="input-group">
                    <label class="label">Телефон</label>
                    <i class="fa-solid fa-phone left-icon"></i>
                    <!-- 🛡️ ФІКС 3: Додано autocomplete для швидкого автозаповнення -->
                    <input type="tel" name="phone" id="phone" class="input-dark auth-input" placeholder="380XXXXXXXXX" pattern="380[0-9]{9}" title="Введіть 12 цифр номера, починаючи з 380" autocomplete="tel" required>
                </div>

                <div class="input-group">
                    <label class="label">Пароль</label>
                    <i class="fa-solid fa-lock left-icon"></i>
                    <input type="password" name="password" class="input-dark auth-input" placeholder="Мінімум 6 символів" minlength="6" maxlength="72" autocomplete="new-password" required>
                </div>

                <div class="input-group">
                    <label class="label">Повторіть пароль</label>
                    <i class="fa-solid fa-lock left-icon"></i>
                    <input type="password" name="password_confirm" class="input-dark auth-input" placeholder="Повторіть пароль" minlength="6" maxlength="72" autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn-reg-submit">ПОДАТИ ЗАЯВКУ</button>
            </form>
        <?php endif; ?>

        <div class="auth-footer">
            Вже маєте акаунт? <a href="login.php">Увійти</a>
        </div>
    </div>
</div>
