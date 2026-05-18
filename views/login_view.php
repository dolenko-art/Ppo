<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo-glow">
                <i class="fa-solid fa-fingerprint"></i>
            </div>
            <h2>Вітаємо!</h2>
            <p>Увійдіть у кабінет ППО</p>
        </div>

        <?php if(isset($error) && $error): ?>
            <div class="auth-alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- 🛡️ ФІКС СЕМАНТИКИ: Видалено зайвий div, класи перенесено сюди -->
        <form method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="input-group">
                <label class="label">Телефон</label>
                <i class="fa-solid fa-phone"></i>
                <!-- 🛡️ ФІКС ВАЛІДАЦІЇ: Додано pattern та title -->
                <input type="tel" name="phone" class="input-dark auth-input" placeholder="380XXXXXXXXX" pattern="380[0-9]{9}" title="Введіть 12 цифр номера, починаючи з 380" required>
            </div>

            <div class="input-group">
                <label class="label">Пароль</label>
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" class="input-dark auth-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-login">УВІЙТИ</button>
            <a href="register.php" class="btn-reg">РЕЄСТРАЦІЯ</a>
        </form>

        <div class="auth-footer">
            <!-- 🛡️ ФІКС ПОСИЛАНЬ: Чесне направлення до підтримки замість "заглушки" -->
            Проблеми зі входом? <a href="https://t.me/YourSupportBot" target="_blank" rel="noopener noreferrer">Написати підтримці</a>
        </div>
    </div>
</div>
