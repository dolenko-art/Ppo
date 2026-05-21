# 🛠️ DEVELOPMENT & SECURITY GUIDE

## Installation & Setup

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Apache 2.4+ (with mod_rewrite)
- OpenSSL extension

### Environment Configuration

```bash
# Create .env file
cp .env.example .env

# Development
ENVIRONMENT=development
DEBUG=1

# Production
ENVIRONMENT=production
DEBUG=0
```

## Security Practices for Developers

### 1. Always Use Prepared Statements

```php
// ✅ CORRECT
$user = \Core\DB::fetch("SELECT * FROM users WHERE id = ?", [$user_id]);

// ❌ WRONG
$user = \Core\DB::fetch("SELECT * FROM users WHERE id = $user_id");
```

### 2. Always Escape Output

```php
// ✅ CORRECT
echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

// In JSON
echo json_encode(['name' => $name], JSON_UNESCAPED_UNICODE);

// ❌ WRONG
echo $name;
echo "<div>$name</div>";
```

### 3. Validate All User Input

```php
// ✅ CORRECT
$phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
if (strlen($phone) < 10 || strlen($phone) > 15) {
    die('Invalid phone number');
}

// ❌ WRONG
$phone = $_POST['phone'];
```

### 4. Implement Authorization Checks

```php
// ✅ CORRECT - Check ownership AND role
$is_author = (int)$petition['user_id'] === $user_id;
$is_admin = in_array($user['role'], ['head', 'admin']);

if (!$is_author && !$is_admin) {
    http_response_code(403);
    die('Access denied');
}

// ❌ WRONG - Only check authentication
if (!\Core\Auth::loggedIn()) {
    die('Login required');
}
// But user can access ANY petition!
```

### 5. Use Rate Limiting for APIs

```php
// ✅ CORRECT
$rate_limiter = new \Core\RateLimiter($_SESSION['user_id'], $_SERVER['REMOTE_ADDR']);
if (!$rate_limiter->isAllowed(100, 3600)) {
    http_response_code(429);
    die('Too many requests');
}

// ❌ WRONG - No protection
echo json_encode($data);
```

### 6. Handle Decryption Safely

```php
// ✅ CORRECT
$decrypted = \Core\Security::decrypt($encrypted_data);
if ($decrypted === false) {
    $name = 'Unknown User';
} else {
    $name = $decrypted;
}

// ❌ WRONG - Returns encrypted data on failure
$name = \Core\Security::decrypt($encrypted_data) ?: $encrypted_data;
```

### 7. Set Secure Headers

```php
// ✅ In db.php (already implemented)
header('Content-Security-Policy: ...');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// ❌ WRONG - No security headers
// (User's browser is vulnerable to many attacks)
```

### 8. Log Security Events

```php
// ✅ CORRECT
error_log("Auth Failed: Phone {$phone} from IP {$ip_address} at {$now}");

// Database logging
\Core\DB::query("INSERT INTO login_attempts (ip_address, phone, status, attempted_at) VALUES (?, ?, ?, ?)", 
    [$ip_address, $phone, 'failed', $now]
);

// ❌ WRONG - No logging
// Attackers can probe without detection
```

---

## Testing Security

### Manual Testing Checklist

- [ ] Test SQL injection: `'; DROP TABLE users; --`
- [ ] Test XSS: `<script>alert('XSS')</script>`
- [ ] Test CSRF: Forge cross-site request
- [ ] Test authorization: Access others' data
- [ ] Test rate limiting: Send 100+ requests/min
- [ ] Test password hashing: Verify hash is never reversed
- [ ] Test session fixation: Check session ID changes after login
- [ ] Test HTTPS redirect: Try http:// access

### Automated Testing

```bash
# OWASP ZAP Scan
zap-cli quick-scan --self-contained http://localhost

# SQLMap
sqlmap -u "http://localhost/search.php?q=test" --dbs

# Burp Suite Pro
# (Professional penetration testing)
```

---

## Code Review Checklist

Before merging any PR:

- [ ] All database queries use prepared statements?
- [ ] All user output is escaped with htmlspecialchars()?
- [ ] Authorization checks present on sensitive operations?
- [ ] No hardcoded credentials or API keys?
- [ ] Error messages don't leak system information?
- [ ] Rate limiting implemented for APIs?
- [ ] Decryption failures handled safely?
- [ ] Logging implemented for security events?
- [ ] No new dependencies with known CVEs?

---

## Reporting Vulnerabilities

### Responsible Disclosure Process

1. **Do NOT** post vulnerability publicly
2. Email security@profspilka.ua with:
   - Vulnerability description
   - Steps to reproduce
   - Potential impact
   - Suggested fix (optional)
3. We will respond within 24 hours
4. Timeline for patch: 30 days
5. Credit in security advisories (optional)

### Sample Report

```
Subject: [Security] SQL Injection in search.php

Description:
The search function is vulnerable to SQL injection.

Steps to Reproduce:
1. Navigate to /search.php
2. Enter: ' OR 1=1 --
3. View results (should return all users)

Impact:
Database disclosure, potential data exfiltration

Suggested Fix:
Use prepared statements: "WHERE name LIKE ?" with [$search]
```

---

## Continuous Security

### Monthly Tasks

- [ ] Review authentication logs for anomalies
- [ ] Check dependency updates for CVEs
- [ ] Audit file/directory permissions
- [ ] Verify backups are working
- [ ] Test disaster recovery procedures

### Quarterly Tasks

- [ ] Run OWASP ZAP full scan
- [ ] Code security audit by independent team
- [ ] Penetration testing
- [ ] Policy review and updates

### Annually

- [ ] Full security assessment
- [ ] Compliance verification (GDPR, etc.)
- [ ] Disaster recovery drill
- [ ] Security awareness training

---

## Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [NIST Cybersecurity Framework](https://www.nist.gov/cyberframework)
- [CWE - Common Weakness Enumeration](https://cwe.mitre.org/)
- [CVE - Common Vulnerabilities and Exposures](https://cve.mitre.org/)

---

**Last Updated:** 2026-05-21
**Maintained By:** Security Team
**License:** MIT
