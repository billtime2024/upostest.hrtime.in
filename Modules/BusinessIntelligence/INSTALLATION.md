# Business Intelligence Module - Installation Guide

## ✅ Module Status
**Status:** Enabled and Ready  
**Version:** 1.2.2  
**Location:** `Modules/BusinessIntelligence`  

## 🚀 Quick Installation Steps

### Method 1: Via Manage Modules Interface (Recommended)

1. **Access Manage Modules**
   ```
   Navigate to: Settings → Manage Modules
   Or visit: http://your-domain/manage-modules
   ```

2. **Locate Business Intelligence**
   - You should see "BusinessIntelligence" in the modules list
   - Status should show "Version 1.2.2"

3. **Install the Module**
   - Click the "Install" button next to Business Intelligence
   - Enter your license information:
     - License Code: [Your license code]
     - Email: [Your email]
     - Username: [Your username]
   - Click "Submit" to complete installation

4. **Verify Installation**
   - After installation, the module version should appear
   - Check for "Business Intelligence" menu in the sidebar
   - If you don't see the menu, proceed to "Enable Sidebar Menu" section below

### Method 2: Manual Installation via Routes

If the module interface doesn't show the install button:

1. **Visit Install URL Directly**
   ```
   http://your-domain/business-intelligence/install
   ```

2. **Complete Installation Form**
   - Fill in license details
   - Submit the form

3. **Clear Caches**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

## 🔐 Enable Sidebar Menu

The sidebar menu requires proper permissions. Follow these steps:

### Step 1: Enable Module in Superadmin Package

If you're using the Superadmin module:

1. Go to `Packages` (in Superadmin)
2. Edit your active package
3. Look for "Business Intelligence Module" checkbox
4. ✅ Enable it
5. Save the package

### Step 2: Grant User Permissions

1. **Go to User Management**
   ```
   Settings → Roles → Edit Role (e.g., Admin role)
   ```

2. **Enable BI Permissions**
   - ✅ Access Business Intelligence
   - ✅ View BI Dashboard
   - ✅ View AI Insights
   - ✅ View Analytics
   - ✅ Manage Configuration
   - ✅ Export Reports
   - ✅ Manage Alerts

3. **Save Role**

4. **Logout and Login Again**
   - Log out from the system
   - Log back in
   - The "Business Intelligence" menu should now appear in the sidebar

### Step 3: Verify Menu Appears

After setting permissions:

1. **Check Sidebar**
   - Look for a menu item with a chart icon
   - Menu label: "Business Intelligence"

2. **Expand Submenu**
   - BI Dashboard
   - AI Insights
   - Analytics
   - Configuration

## 📍 Access Points

Once installed and permissions are set:

| Feature | URL | Permission Required |
|---------|-----|---------------------|
| BI Dashboard | `/business-intelligence/dashboard` | `businessintelligence.view_dashboard` |
| AI Insights | `/business-intelligence/insights` | `businessintelligence.view_insights` |
| Sales Analytics | `/business-intelligence/analytics/sales` | `businessintelligence.view_analytics` |
| Configuration | `/business-intelligence/configuration` | `businessintelligence.manage_config` |

## 🔧 Troubleshooting

### Issue 1: Module Not Showing in Sidebar

**Solution:**
```bash
# 1. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 2. Check module status
php artisan module:list

# 3. Verify BusinessIntelligence shows as [Enabled]
```

### Issue 2: Module Not in Manage Modules List

**Solution:**
1. Check `modules_statuses.json` in project root
2. Ensure it contains: `"BusinessIntelligence": true`
3. If missing, add it manually and clear cache

### Issue 3: Permission Denied

**Solution:**
1. Make sure you're logged in as Admin or Superadmin
2. Check role permissions include BI access
3. If using Superadmin module, ensure BI is enabled in your package

### Issue 4: Database Tables Not Created

**Solution:**
```bash
# Run migrations manually
php artisan module:migrate BusinessIntelligence --force

# Check if tables exist
# Should see: bi_configurations, bi_insights, bi_reports, bi_alerts, bi_metrics_cache, bi_predictions
```

### Issue 5: Charts Not Loading

**Solution:**
1. Open browser Developer Tools (F12)
2. Check Console tab for errors
3. Verify ApexCharts.js is loaded
4. Check Network tab for failed AJAX requests
5. Ensure you have transaction data in the database

## ✨ Post-Installation

After successful installation:

1. **Access Dashboard**
   - Click "Business Intelligence" in sidebar
   - Select "BI Dashboard"

2. **Explore Features**
   - View real-time KPIs
   - Explore interactive charts
   - Generate AI insights
   - Export reports

3. **Configure Settings**
   - Go to Configuration section
   - Set refresh intervals
   - Configure alert thresholds
   - Customize dashboard preferences

## 📊 Database Tables

The module creates these tables:

| Table | Purpose |
|-------|---------|
| `bi_configurations` | Module settings and configurations |
| `bi_insights` | AI-generated insights and recommendations |
| `bi_reports` | Saved report definitions |
| `bi_alerts` | Business alerts and notifications |
| `bi_metrics_cache` | Cached metrics for performance |
| `bi_predictions` | Predictive analytics data |

## 🆘 Support

If you encounter issues:

1. **Check Logs**
   ```
   storage/logs/laravel.log
   ```

2. **Enable Debug Mode** (temporary)
   ```env
   APP_DEBUG=true
   ```

3. **Contact Support**
   - Email: support@ultimatepos.com
   - Documentation: Check README.md
   - Module Version: 1.2.2

## 🎉 Success Checklist

- ✅ Module appears in `Manage Modules` list
- ✅ Module status shows "Version 1.2.2"
- ✅ "Business Intelligence" menu appears in sidebar
- ✅ Can access BI Dashboard
- ✅ Charts are loading with data
- ✅ KPI cards show correct values
- ✅ No errors in browser console
- ✅ Date range filter works
- ✅ AI insights can be generated
- ✅ Export functionality works

---

**🎯 You're all set! Enjoy your AI-Powered Business Intelligence Dashboard!**
