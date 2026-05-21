# Deployment Security Checklist

## 🔒 До запуску на production

### Фаза 1: Підготовка сервера

- [ ] Оновити ОС (`apt update && apt upgrade`)
- [ ] Встановити PHP 7.4+ (`php -v`)
- [ ] Встановити MySQL 5.7+
- [ ] Встановити OpenSSL
- [ ] Встановити Git
- [ ] Налаштувати Firewall (UFW/iptables)
  - [ ] Закрити всі порти крім 80, 443, 22
  - [ ] Обмежити SSH доступ до конкретних IP
- [ ] Встановити SSL сертифікат (Let's Encrypt)
- [ ] Налаштувати логування

### Фаза 2: Додаток

- [ ] Скопіювати файли на сервер
- [ ] Встановити дозволи: `chmod 750 /var/www/ppo`
- [ ] Встановити .env: `ENVIRONMENT=production`
- [ ] Встановити дозволи для логів: `chmod 700 /var/www/ppo/logs`
- [ ] Встановити дозволи для кешу: `chmod 755 /var/www/ppo/cache`
- [ ] Встановити дозволи для uploads: `chmod 755 /var/www/ppo/uploads`
- [ ] Запустити migration скрипти
- [ ] Запустити security audit

### Фаза 3: Веб сервер

#### Apache
- [ ] Увімкнути mod_rewrite: `a2enmod rewrite`
- [ ] Увімкнути mod_headers: `a2enmod headers`
- [ ] Увімкнути mod_ssl: `a2enmod ssl`
- [ ] Завантажити .htaccess
- [ ] Налаштувати VirtualHost для HTTPS
- [ ] Перенаправити HTTP на HTTPS
- [ ] Вимкнути directory listing: `Options -Indexes`

#### Nginx
```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /var/www/ppo;
    
    # SSL
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
    
    # Заборонити доступ до чутливих файлів
    location ~ /\. { deny all; }
    location ~ /\.env { deny all; }
}
```

### Фаза 4: База Даних

- [ ] Створити user з мінімальними дозволами
- [ ] Вимкнути remote access: `bind-address = 127.0.0.1`
- [ ] Встановити strong password
- [ ] Запустити: `mysql_secure_installation`
- [ ] Налаштувати бекап cron job
- [ ] Протестувати復restore

### Фаза 5: Моніторинг & Логування

- [ ] Налаштувати centralized logging (ELK/Splunk)
- [ ] Налаштувати error tracking (Sentry/Rollbar)
- [ ] Налаштувати application monitoring (New Relic/DataDog)
- [ ] Встановити intrusion detection (fail2ban)
- [ ] Налаштувати alerting для критичних помилок
- [ ] Налаштувати automated backup
- [ ] Налаштувати backup monitoring

### Фаза 6: Безпека

- [ ] Запустити OWASP Zap scan
- [ ] Запустити SQLMap тести
- [ ] Перевірити SSL rating на ssllabs.com (A+ rating)
- [ ] Перевірити security headers на securityheaders.com
- [ ] Встановити WAF (Cloudflare/ModSecurity)
- [ ] Встановити DDoS protection
- [ ] Налаштувати rate limiting на сервері
- [ ] Налаштувати IP whitelisting для admin

### Фаза 7: Тестування

- [ ] Функціональні тести (Selenium)
- [ ] Security тести (Burp Suite)
- [ ] Load тести (Apache JMeter)
- [ ] Penetration test (якщо можливо)
- [ ] User acceptance testing (UAT)

### Фаза 8: Запуск

- [ ] Активувати DNS
- [ ] Запустити додаток
- [ ] Моніторити помилки (перші 24 години)
- [ ] Інформувати користувачів
- [ ] Документувати процес запуску

---

## 🚨 Post-deployment

### День 1-7
- [ ] Моніторити error logs щодня
- [ ] Перевіряти performance metrics
- [ ] Збирати feedback від користувачів
- [ ] Готуватися до rollback

### Тиждень 1-4
- [ ] Аналізувати security logs
- [ ] Аналізувати користувацьку поведінку
- [ ] Оптимізувати slow queries
- [ ] Видалити старі logs

### Місяць 1+
- [ ] Регулярні security audits
- [ ] Регулярні backup тести
- [ ] Оновлення dependencies
- [ ] Моніторинг CVE для установлених пакетів

---

## 📊 Моніторинг метрик

```bash
# CPU Usage
top -bn1 | head -n 3

# Memory Usage
free -h

# Disk Usage
df -h

# Network Traffic
iftop -n

# MySQL Performance
mysql -u root -p -e "SHOW STATUS LIKE 'Threads_connected';"

# PHP-FPM Status
curl http://localhost/php-fpm-status

# Log Analysis
tail -f /var/log/apache2/error.log
tail -f /var/www/ppo/logs/error.log

# Security Logs
grep "Security\|failed\|denied" /var/log/auth.log
```

---

## 🔧 Emergency Procedures

### Якщо знайдено вразливість

1. Оцінити критичність (CVSS score)
2. Розробити патч
3. Протестувати на staging
4. Розгорнути на production
5. Переконатися, що exploit більше неможливий
6. Логувати дату и часы
7. Сповістити користувачів

### Якщо відбувся вторгнення

1. ⚠️ Негайно відключити від мережі
2. Збереження доказів (forensics)
3. Визначити вектор атаки
4. Переinstall ОС
5. Restore з clean backup
6. Усунення вразливості
7. Моніторинг 30 днів

---

**Версія:** 2.0  
**Дата:** 2026-05-21  
**Автор:** Senior DevSecOps Engineer
