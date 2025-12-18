<?php

namespace Modules\BusinessIntelligence\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    /**
     * Defines user permissions for the module.
     * Required by Ultimate POS module system.
     * 
     * NO LICENSE REQUIRED - All permissions default to true for admin users
     *
     * @return array
     */
    public function userPermissions()
    {
        return [
            [
                'value' => 'businessintelligence.access',
                'label' => __('businessintelligence::lang.access_business_intelligence'),
                'default' => true // Changed to true - no license needed
            ],
            [
                'value' => 'businessintelligence.view_dashboard',
                'label' => __('businessintelligence::lang.view_dashboard'),
                'default' => true // Changed to true - no license needed
            ],
            [
                'value' => 'businessintelligence.view_insights',
                'label' => __('businessintelligence::lang.view_insights'),
                'default' => true // Changed to true - no license needed
            ],
            [
                'value' => 'businessintelligence.view_analytics',
                'label' => __('businessintelligence::lang.view_analytics'),
                'default' => true // Changed to true - no license needed
            ],
            [
                'value' => 'businessintelligence.manage_config',
                'label' => __('businessintelligence::lang.manage_configuration'),
                'default' => true // Changed to true - no license needed
            ],
            [
                'value' => 'businessintelligence.export_reports',
                'label' => __('businessintelligence::lang.export_reports'),
                'default' => true // Changed to true - no license needed
            ],
            [
                'value' => 'businessintelligence.manage_alerts',
                'label' => __('businessintelligence::lang.manage_alerts'),
                'default' => true // Changed to true - no license needed
            ],
        ];
    }

    /**
     * Adds Business Intelligence menu items to the admin sidebar.
     * Called by AdminSidebarMenu middleware.
     * 
     * NO LICENSE REQUIRED - Module is always available
     *
     * @return void
     */
    public function modifyAdminMenu()
    {
        // NO LICENSE CHECK - Module is always enabled
        $is_bi_enabled = true;

        // Add menu if user has access permission (or is admin/superadmin)
        if ($is_bi_enabled && (auth()->user()->can('businessintelligence.access') || auth()->user()->can('admin') || auth()->user()->can('superadmin'))) {
            $menu = Menu::instance('admin-sidebar-menu');

            // Add as dropdown menu with sub-items
            $menu->dropdown(
                __('businessintelligence::lang.business_intelligence'),
                function ($sub) {
                    // Dashboard
                    if (auth()->user()->can('businessintelligence.view_dashboard')) {
                        $sub->url(
                            route('businessintelligence.dashboard'),
                            __('businessintelligence::lang.dashboard'),
                            [
                                'active' => request()->segment(1) == 'business-intelligence' 
                                    && request()->segment(2) == 'dashboard'
                            ]
                        );
                    }

                    // Insights
                    if (auth()->user()->can('businessintelligence.view_insights')) {
                        $sub->url(
                            route('businessintelligence.insights.index'),
                            __('businessintelligence::lang.insights'),
                            [
                                'active' => request()->segment(1) == 'business-intelligence' 
                                    && request()->segment(2) == 'insights'
                            ]
                        );
                    }

                    // Analytics
                    if (auth()->user()->can('businessintelligence.view_analytics')) {
                        $sub->url(
                            route('businessintelligence.analytics.sales'),
                            __('businessintelligence::lang.analytics'),
                            [
                                'active' => request()->segment(1) == 'business-intelligence'
                                    && request()->segment(2) == 'analytics'
                            ]
                        );
                    }


                    // Advanced KPI Analytics
                    if (auth()->user()->can('businessintelligence.view_analytics')) {
                        $sub->url(
                            route('businessintelligence.advanced-kpi'),
                            __('Advanced KPI Analytics'),
                            [
                                'active' => request()->segment(1) == 'business-intelligence'
                                    && request()->segment(2) == 'advanced-kpi'
                            ]
                        );
                    }

                    // Configuration (Admin only)
                    if (auth()->user()->can('businessintelligence.manage_config')) {
                        $sub->url(
                            route('businessintelligence.configuration.index'),
                            __('businessintelligence::lang.configuration'),
                            [
                                'active' => request()->segment(1) == 'business-intelligence' 
                                    && request()->segment(2) == 'configuration'
                            ]
                        );
                    }
                },
                [
                    'icon' => 'fas fa-chart-line',
                    'active' => request()->segment(1) == 'business-intelligence'
                ]
            )->order(6); // Position immediately after Home (order 5)
        }
    }

    /**
     * Returns module configuration for superadmin
     * 
     * NO LICENSE REQUIRED - Module is always enabled by default
     *
     * @return array
     */
    public function superadmin_package()
    {
        return [
            [
                'name' => 'business_intelligence_module',
                'label' => __('businessintelligence::lang.business_intelligence'),
                'default' => true // Changed to true - always enabled, no license needed
            ]
        ];
    }

    /**
     * Adds module constants
     *
     * @return array
     */
    public function constants()
    {
        return [
            'bi_dashboard_refresh_interval' => 300, // 5 minutes
            'bi_max_cache_time' => 3600, // 1 hour
            'bi_max_insights' => 10,
        ];
    }
}

