# Business Intelligence Module

**Version:** 1.2.3
**Author:** Ultimate POS Team  
**Module Type:** Premium Add-on  

## Overview

The Business Intelligence module provides AI-powered analytics, automated insights, and predictive recommendations for comprehensive business performance monitoring in Ultimate POS.

## Features

### 📊 **Analytics Dashboard**
- **Real-time KPI Cards**: Revenue, Profit, Orders, Customers, Products
- **Interactive Charts**: Sales trends, Revenue sources, Profit analysis
- **Advanced Visualizations**: Modern bar charts, donut charts, area charts
- **Date Range Filters**: 7 days, 30 days, 90 days, 1 year

### 🤖 **AI-Powered Insights**
- Automated business insights generation
- Rule-based AI engine (extensible to LLM integration)
- Trend analysis and anomaly detection
- Actionable recommendations

### 📈 **Complete Reports**
- **Sales, Purchase & Expense Analytics**: 6-month trend analysis
- **Profit & Loss Statement**: Complete P&L breakdown with charts
- **Inventory Analysis**: Stock levels and inventory health
- **Customer Growth**: Customer acquisition trends
- **Cash Flow Analysis**: Income vs expenses

### 🎨 **Modern Design**
- Beautiful gradient charts with smooth animations
- Responsive layout for all devices
- Professional color schemes
- Interactive tooltips and legends

## Installation

### Requirements
- Ultimate POS v6.0 or higher
- PHP 7.4+
- MySQL 5.7+

### Installation Steps

1. **Upload Module**
   - Go to `System Settings` → `Manage Modules`
   - Click "Upload Module"
   - Upload the BusinessIntelligence.zip file

2. **Install Module**
   - After upload, click "Install" button
   - Enter your license code
   - Wait for installation to complete

3. **Verify Installation**
   - Check if "Business Intelligence" appears in the sidebar menu
   - Navigate to the BI Dashboard
   - Verify that all charts are loading correctly

## Configuration

### Module Settings
Located in: `Config/config.php`

```php
'dashboard' => [
    'refresh_interval' => 300,  // 5 minutes
    'cache_ttl' => 600,         // 10 minutes
    'date_range_default' => 30,  // days
],
```

### Permissions
Available permissions:
- `businessintelligence.access` - Access to BI module
- `businessintelligence.view_dashboard` - View dashboard
- `businessintelligence.view_insights` - View AI insights
- `businessintelligence.view_analytics` - View analytics reports
- `businessintelligence.manage_config` - Manage configuration
- `businessintelligence.export_reports` - Export reports

## Usage

### Accessing the Dashboard
1. Click "Business Intelligence" in the sidebar menu
2. Select "BI Dashboard"
3. Choose date range filter
4. View charts and KPIs

### Generating Insights
1. Click "Generate AI Insights" button
2. Wait for analysis to complete
3. View generated insights in the insights panel

### Exporting Reports
1. Select desired date range
2. Click "Export Dashboard" button
3. Choose export format (PDF/Excel)

## Architecture

### Backend Components
- **BiAnalyzer**: Core analytics engine
- **DataProcessor**: Data processing utilities
- **InsightGenerator**: AI insight generation
- **AiEngine**: Rule-based AI logic

### Database Tables
- `bi_configurations` - Module configurations
- `bi_insights` - Generated insights
- `bi_reports` - Saved reports
- `bi_alerts` - System alerts
- `bi_metrics_cache` - Performance cache
- `bi_predictions` - Predictive data

### Frontend Stack
- **Charts**: ApexCharts.js
- **Framework**: Laravel Blade + Vue.js components
- **Styling**: Bootstrap 4 + Custom CSS

## API Endpoints

### Dashboard Data
```
GET /business-intelligence/dashboard
GET /business-intelligence/dashboard/data
GET /business-intelligence/dashboard/chart-data?chart_type=sales_trend&date_range=30
```

### Insights
```
GET /business-intelligence/insights
POST /business-intelligence/insights/refresh
```

### Analytics
```
GET /business-intelligence/analytics/sales
GET /business-intelligence/analytics/financial
GET /business-intelligence/analytics/comprehensive
```

## Troubleshooting

### Dashboard not showing data
1. Clear cache: `php artisan cache:clear`
2. Clear config: `php artisan config:clear`
3. Clear views: `php artisan view:clear`
4. Check browser console for JavaScript errors

### Module not appearing in manage-modules
1. Verify `modules_statuses.json` has `"BusinessIntelligence": true`
2. Run: `php artisan module:discover`
3. Clear all caches

### Charts not loading
1. Check ApexCharts.js is loaded
2. Verify AJAX routes are accessible
3. Check browser console for errors
4. Verify database has transaction data

## Support

For support and updates:
- **Website**: [https://ultimatepos.com](https://ultimatepos.com)
- **Documentation**: Check the `Docs` folder
- **Version**: 1.2.2

## Changelog

### Version 1.2.3 (2025-10-25)
- **Currency Dynamic Support**: All monetary values now use dynamic currency symbols and formatting
- **Dashboard Currency**: KPI cards and charts now display correct currency (৳ for BDT, $ for USD, etc.)
- **Sales Analytics Currency**: Metric cards, product revenues, and chart tooltips use dynamic currency
- **Currency Symbol Placement**: Supports both before and after amount placement based on business settings
- **Proper Number Formatting**: Uses business decimal precision and separators
- **Session-Based Currency**: Reads currency settings from Ultimate POS session data

### Version 1.2.2 (2025-10-24)
- Added complete Profit & Loss analysis with charts
- Implemented modern bar chart with gradient fills
- Added Sales, Purchase & Expense analytics (6 months)
- Fixed date range calculations for accurate reporting
- Improved chart animations and interactions
- Added donut chart for P&L component breakdown
- Enhanced tooltip formatting with currency
- Fixed dynamic data loading for all charts
- Integrated with existing TransactionUtil for P&L data
- Added comprehensive error logging and debugging

### Version 1.0.0
- Initial release
- Core analytics dashboard
- AI-powered insights
- KPI cards and charts
- Basic reporting features

## License

This is a premium add-on for Ultimate POS. License required for production use.

---

**Made with ❤️ by Ultimate POS Team**
