# Business Intelligence Module - Troubleshooting Guide

## Installation Issues

### Issue: "Table already exists" Error

**Error Message:**
```
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'bi_insights' already exists
```

**Cause:** The module was previously installed (partially or fully), and tables still exist in the database.

**Solution 1: Use the Updated Installer (Recommended)**

The installer has been updated to automatically clean existing tables. Simply:

1. **Reload the installation page**
   ```
   http://your-pos-url/business-intelligence/install
   ```

2. **You'll see a warning** if tables exist

3. **Click "Reinstall Module"** - it will automatically drop old tables and reinstall

**Solution 2: Manual Database Cleanup**

If you prefer to manually clean the database first:

```sql
-- Run these SQL commands in your database
DROP TABLE IF EXISTS bi_predictions;
DROP TABLE IF EXISTS bi_metrics_cache;
DROP TABLE IF EXISTS bi_alerts;
DROP TABLE IF EXISTS bi_reports;
DROP TABLE IF EXISTS bi_insights;
DROP TABLE IF EXISTS bi_configurations;
```

Then try installation again.

**Solution 3: Using Artisan Commands**

```bash
# From your Ultimate POS directory
cd C:\laragonpro\www\utp

# Drop all module tables
php artisan db:wipe

# Or manually rollback migrations
php artisan module:migrate-rollback BusinessIntelligence

# Then try install again
```

---

## Other Common Issues

### Issue: Views Not Loading

**Error:** "View [businessintelligence::...] not found"

**Solution:**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Publish module views (optional)
php artisan module:publish BusinessIntelligence
```

### Issue: Routes Not Working

**Error:** 404 on `/business-intelligence/*` routes

**Solution:**
```bash
# Clear route cache
php artisan route:clear

# Check if module is enabled
php artisan module:list

# If not enabled
php artisan module:enable BusinessIntelligence
```

### Issue: Charts Not Displaying

**Causes:**
1. No data in database
2. JavaScript errors
3. Chart.js not loaded

**Solution:**

1. **Check browser console** (F12) for errors

2. **Verify data exists:**
   ```
   http://your-pos-url/business-intelligence/dashboard/chart-data?chart_type=sales_trend
   ```

3. **Check Chart.js is loaded:**
   - View page source
   - Look for Chart.js script tag

4. **Clear browser cache** (Ctrl+Shift+Delete)

### Issue: Insights Not Generating

**Error:** "No insights found" or generation fails

**Solution:**

1. **Check you have data:**
   - At least 7 days of transactions
   - Some products in inventory
   - Some expenses recorded

2. **Try manual generation:**
   ```
   http://your-pos-url/business-intelligence/insights/generate
   ```

3. **Check error logs:**
   ```
   storage/logs/laravel.log
   ```

4. **Verify permissions:**
   - User must have `bi.view_insights` permission

### Issue: Permission Denied

**Error:** "Unauthorized action" or 403 error

**Solution:**

1. **Check user role permissions:**
   - Go to: Settings → Roles
   - Edit role
   - Enable BI permissions

2. **Required permissions:**
   - `bi.view_dashboard`
   - `bi.view_analytics`
   - `bi.view_insights`
   - `bi.manage_configuration`

### Issue: OpenAI Integration Not Working

**Error:** "OpenAI API Error" or insights fail

**Solution:**

1. **Check API key:**
   ```env
   # In .env file
   OPENAI_API_KEY=sk-your-actual-key-here
   ```

2. **Verify API key is valid:**
   - Login to OpenAI
   - Check API key status
   - Check billing/credits

3. **Test with rule-based first:**
   ```env
   BI_AI_PROVIDER=rule-based
   ```

4. **Check error logs:**
   ```
   storage/logs/laravel.log
   ```

---

## Database Issues

### Check Module Status

Visit: `http://your-pos-url/business-intelligence/status`

**Expected Response:**
```json
{
    "success": true,
    "installed": true,
    "tables": {
        "bi_configurations": true,
        "bi_insights": true,
        "bi_reports": true,
        "bi_alerts": true,
        "bi_metrics_cache": true,
        "bi_predictions": true
    }
}
```

### Verify Database Connection

```bash
php artisan tinker
DB::select('SHOW TABLES LIKE "bi_%"');
```

---

## Performance Issues

### Slow Dashboard Loading

**Solutions:**

1. **Enable caching:**
   - Go to Configuration
   - Enable "Enable Metrics Caching"

2. **Increase cache TTL:**
   - Configuration → Performance
   - Set higher cache TTL (600-1800 seconds)

3. **Check database indexes:**
   ```sql
   SHOW INDEX FROM bi_insights;
   SHOW INDEX FROM bi_metrics_cache;
   ```

4. **Optimize database:**
   ```bash
   php artisan optimize
   ```

### Memory Issues

**Error:** "Allowed memory size exhausted"

**Solution:**

1. **Increase PHP memory limit:**
   ```ini
   # php.ini
   memory_limit = 256M
   ```

2. **Use smaller date ranges:**
   - Filter to last 30 days instead of 90

3. **Enable database query optimization**

---

## Uninstallation Issues

### Clean Uninstall

If you need to completely remove the module:

```bash
# 1. Uninstall via web interface
http://your-pos-url/business-intelligence/uninstall

# 2. Or manually:
# Drop all tables
DROP TABLE IF EXISTS bi_predictions;
DROP TABLE IF EXISTS bi_metrics_cache;
DROP TABLE IF EXISTS bi_alerts;
DROP TABLE IF EXISTS bi_reports;
DROP TABLE IF EXISTS bi_insights;
DROP TABLE IF EXISTS bi_configurations;

# 3. Remove module files
rm -rf Modules/BusinessIntelligence

# 4. Update modules_statuses.json
# Remove "BusinessIntelligence": true

# 5. Clear caches
php artisan cache:clear
php artisan config:clear
```

---

## Getting Help

### Check Logs

**Laravel Log:**
```
C:\laragonpro\www\utp\storage\logs\laravel.log
```

**Look for:**
- `BI Module Installation Error:`
- `OpenAI API Error:`
- SQL errors
- Permission errors

### Debug Mode

Enable debug mode temporarily:

```env
# .env
APP_DEBUG=true
```

**⚠️ Remember to disable in production!**

### System Requirements

Verify your system meets requirements:

```bash
# Check PHP version
php -v
# Required: PHP 7.4+ or 8.0+

# Check Laravel version
php artisan --version
# Required: Laravel 8+

# Check MySQL version
mysql --version
# Required: MySQL 5.7+ or MariaDB 10.3+
```

---

## Quick Fix Commands

**Clear everything:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

**Reset module:**
```bash
# Uninstall
php artisan module:migrate-rollback BusinessIntelligence

# Reinstall
php artisan module:migrate BusinessIntelligence
```

**Test API endpoints:**
```bash
# Test dashboard
curl http://localhost/business-intelligence/dashboard/kpis

# Test status
curl http://localhost/business-intelligence/status

# Test insights generation
curl -X POST http://localhost/business-intelligence/insights/generate
```

---

## Still Having Issues?

1. **Check documentation:**
   - `README.md`
   - `INSTALLATION.md`
   - `USER_GUIDE.md`

2. **Verify file permissions:**
   ```bash
   chmod -R 755 Modules/BusinessIntelligence
   chmod -R 775 storage/
   ```

3. **Check disk space:**
   ```bash
   df -h
   ```

4. **Review PHP error log**

5. **Contact support with:**
   - Error message
   - Laravel log excerpt
   - PHP version
   - Steps to reproduce

---

**Most issues can be resolved by clearing caches and reinstalling!** 🔧

