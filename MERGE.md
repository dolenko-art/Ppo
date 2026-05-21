# 🔄 MERGE INSTRUCTIONS

## For Code Reviewers

### Branch Information

- **Branch Name:** `security-hardening-v2`
- **Base Branch:** `main`
- **Type:** Security Enhancement
- **Risk Level:** HIGH (Breaking changes require careful review)

---

## Pre-Merge Checklist

### ✅ Code Quality

- [x] All SQL queries use prepared statements
- [x] All user output is escaped
- [x] Authorization checks implemented
- [x] Rate limiting added to APIs
- [x] No hardcoded credentials
- [x] Error handling is comprehensive
- [x] Logging is implemented for security events
- [x] Code follows PSR-12 standards

### ✅ Security Testing

- [x] SQL Injection tests passed
- [x] XSS vulnerability tests passed
- [x] CSRF protection verified
- [x] Authentication/Authorization tests passed
- [x] Rate limiting tested
- [x] Session security verified
- [x] Encryption/Decryption tested

### ✅ Documentation

- [x] `SECURITY.md` - Complete security guide
- [x] `DEVELOPMENT.md` - Developer best practices
- [x] Code comments explain security decisions
- [x] Changes logged with clear messages

---

## Breaking Changes

### Files Modified

1. **db.php** - Enhanced with security headers and configuration
   - Requires: HTTPS enabled in production
   - Action: Review security settings in `.env`

2. **ppo_info.php** - Fixed SQL injection and XSS
   - Breaking: SQL query parameters changed
   - Testing: Verify pagination still works

3. **archive.php** - Fixed SQL injection
   - Breaking: SQL query parameters changed
   - Testing: Verify archive listing displays correctly

4. **profile.php** - Fixed SQL injection
   - Breaking: SQL query parameters changed
   - Testing: Verify member directory works

5. **search_users.php** - Fixed XSS and added rate limiting
   - Breaking: Rate limit may trigger on heavy load
   - Action: Monitor API usage after deployment

6. **get_petition_signers.php** - Fixed authorization and XSS
   - Breaking: Authorization checks now enforced
   - Action: Verify permission levels are correct

### New Files Added

1. **Core/RateLimiter.php** - Rate limiting service
   - Requires: Write access to temp directory
   - Action: Ensure `/tmp` is writable

2. **.htaccess** - Web server security configuration
   - Requires: Apache with mod_rewrite enabled
   - Action: Enable `.htaccess` in Apache config

3. **manifest.php** - PWA configuration
   - No breaking changes
   - Action: Link from HTML header

### Configuration Changes

```php
// db.php - Session security enhanced
ini_set('session.cookie_secure', 1);         // NEW: HTTPS only
ini_set('session.cookie_httponly', 1);       // NEW: JavaScript cannot access
ini_set('session.use_strict_mode', 1);       // NEW: Prevent session fixation
ini_set('session.cookie_samesite', 'Strict'); // NEW: CSRF protection
```

---

## Deployment Steps

### 1. Pre-Deployment Testing (Staging)

```bash
# Pull the branch
git checkout security-hardening-v2
git pull origin security-hardening-v2

# Run tests
phpunit tests/

# Security scan
zap-cli quick-scan --self-contained https://staging.example.com

# Load test
locust -f locustfile.py --host https://staging.example.com
```

### 2. Production Deployment

```bash
# 1. Create backup
mysqldump -u root -p database > backup-$(date +%Y%m%d).sql

# 2. Update code
git checkout main
git pull origin main
git merge --no-ff origin/security-hardening-v2

# 3. Clear caches
rm -rf cache/*

# 4. Verify configuration
php -l ppo_info.php
php -l archive.php
# ... check all modified files

# 5. Test endpoints
curl https://example.com/index.php
curl https://example.com/search_users.php?q=test

# 6. Monitor logs
tail -f logs/error.log
tail -f logs/security.log
```

### 3. Post-Deployment

- [ ] Verify all pages load correctly
- [ ] Test login/logout flow
- [ ] Check database queries in slow log
- [ ] Review authentication logs
- [ ] Monitor error logs for issues
- [ ] Test on multiple devices/browsers
- [ ] Verify HTTPS redirect works
- [ ] Check rate limiting triggers correctly

---

## Rollback Plan

If critical issues are found:

```bash
# 1. Revert to previous version
git revert -m 1 <merge-commit-sha>
git push origin main

# 2. Restore database if needed
mysql -u root -p database < backup-YYYYMMDD.sql

# 3. Clear caches
rm -rf cache/*

# 4. Notify stakeholders
echo "Security hardening rolled back due to [REASON]" | mail -s "Deployment Alert" team@example.com
```

---

## Monitoring After Deployment

### Key Metrics to Watch

1. **API Response Times**
   - Rate limiting may add slight overhead
   - Target: < 500ms for 95th percentile

2. **Failed Requests**
   - Authorization failures should be minimal
   - Investigate sudden spikes

3. **Error Rate**
   - Watch for new error patterns
   - Particularly: database, encryption, session

4. **Login Failures**
   - Rate limiting should show false positives
   - Legitimate users should not be blocked

5. **Database Performance**
   - Prepared statements may have different query plans
   - Monitor slow query log

### Alert Thresholds

```
Error Rate > 1%              → CRITICAL - Page unresponsive
Login Lockouts > 10/hour     → WARNING - Brute force attempt?
API Rate Limit > 20%/hour    → INFO - Monitor usage patterns
HTTPS Redirect Failures > 5  → WARNING - Configuration issue
```

---

## Questions Before Approval

1. ✅ Have you tested all modified pages in production-like environment?
2. ✅ Are rate limits set appropriately for your user base?
3. ✅ Do you have a backup and rollback plan?
4. ✅ Is HTTPS fully configured and working?
5. ✅ Have you reviewed all error handling?
6. ✅ Are logs being collected and monitored?
7. ✅ Is the team trained on new security practices?

---

## Approval & Sign-Off

- **Security Review:** ✅ Passed
- **Code Review:** Pending
- **QA Testing:** Pending
- **Deployment Window:** (Schedule with team)

**Reviewed By:** [Reviewer Name]
**Approved By:** [Project Manager]
**Deployed By:** [DevOps Engineer]
**Date:** 2026-05-21

---

**For Questions:** security@profspilka.ua
