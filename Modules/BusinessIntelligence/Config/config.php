<?php

return [
    'name' => 'BusinessIntelligence',
    'module_version' => '1.2.4',
    'pid' => 36,
    'description' => 'AI-Powered Business Intelligence Dashboard with automated analytics and insights',
    'author' => 'Ultimate POS Team',
    'website' => 'https://ultimatepos.com',
    
    // AI Configuration
    'ai' => [
        'enabled' => env('BI_AI_ENABLED', true),
        'provider' => env('BI_AI_PROVIDER', 'rule-based'), // 'rule-based' or 'openai'
        'openai' => [
            'api_key' => env('OPENAI_API_KEY', ''),
            'model' => env('OPENAI_MODEL', 'gpt-4'),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 1000),
        ],
    ],
    
    // Dashboard Configuration
    'dashboard' => [
        'refresh_interval' => 300, // 5 minutes in seconds
        'cache_ttl' => 600, // 10 minutes
        'date_range_default' => 30, // days
    ],
    
    // Analytics Configuration
    'analytics' => [
        'min_confidence_score' => 0.7,
        'enable_predictions' => true,
        'enable_anomaly_detection' => true,
        'enable_trend_analysis' => true,
    ],
    
    // Alert Thresholds
    'alerts' => [
        'low_stock_threshold' => 10,
        'overdue_days_threshold' => 30,
        'profit_margin_threshold' => 15, // percentage
        'expense_spike_threshold' => 50, // percentage increase
        'cash_flow_warning_days' => 7,
    ],
    
    // Chart Configuration
    'charts' => [
        'default_type' => 'line',
        'color_scheme' => ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'],
        'animation_enabled' => true,
    ],
    
    // Permissions
    'permissions' => [
        'bi.view_dashboard',
        'bi.view_analytics',
        'bi.view_insights',
        'bi.manage_configuration',
        'bi.export_reports',
        'bi.manage_alerts',
    ],
];

