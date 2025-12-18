<?php

namespace Modules\Daybook\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Utils\ModuleUtil;
use Menu;

class DataController extends Controller
{
    /**
     * Defines user permissions for the module.
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'daybook.view',
                'label' => __('daybook::lang.view_daybook'),
                'default' => false
            ],
            [
                'value' => 'daybook.export',
                'label' => __('daybook::lang.export_daybook'),
                'default' => false
            ]
        ];
    }

    /**
     * Modify admin menu to add Daybook menu item
     */
    public function modifyAdminMenu()
    {
        try {
            // Check if module is installed - try both possible property names
            $is_installed = \App\System::getProperty('daybook_version') 
                         ?? \App\System::getProperty('Daybook_version');
            
            if (empty($is_installed)) {
                return; // Module not installed, skip menu modification
            }

            // Check permission, but also allow if user is superadmin or has any admin role
            $can_view = auth()->user()->can('daybook.view') 
                     || auth()->user()->can('superadmin')
                     || auth()->user()->hasRole('Admin#' . session('business.id'));

            if ($can_view) {
                // Get translations with fallbacks
                $daybook_label = __('daybook::lang.daybook');
                if ($daybook_label === 'daybook::lang.daybook') {
                    $daybook_label = 'Daybook'; // Fallback if translation missing
                }
                
                $monthly_dashboard_label = __('daybook::lang.monthly_dashboard');
                if ($monthly_dashboard_label === 'daybook::lang.monthly_dashboard') {
                    $monthly_dashboard_label = 'Monthly Dashboard';
                }
                
                $monthly_cashbook_label = __('daybook::lang.monthly_cashbook');
                if ($monthly_cashbook_label === 'daybook::lang.monthly_cashbook') {
                    $monthly_cashbook_label = 'Monthly Cashbook';
                }
                
                $daily_cashbook_label = __('daybook::lang.daily_cashbook');
                if ($daily_cashbook_label === 'daybook::lang.daily_cashbook') {
                    $daily_cashbook_label = 'Daily Cashbook';
                }

                $daily_payment_label = __('daybook::lang.daily_payment');
                if ($daily_payment_label === 'daybook::lang.daily_payment') {
                    $daily_payment_label = 'Daily Payment';
                }

                // Daybook dropdown menu
                $menu_instance = Menu::instance('admin-sidebar-menu');
                
                if ($menu_instance) {
                    $menu_instance->dropdown(
                        $daybook_label,
                        function ($sub) use ($daybook_label, $monthly_dashboard_label, $monthly_cashbook_label, $daily_cashbook_label, $daily_payment_label) {
                            // Main Daybook link
                            $sub->url(
                                action([\Modules\Daybook\Http\Controllers\DaybookController::class, 'index']), 
                                $daybook_label, 
                                ['icon' => '', 'active' => request()->segment(1) == 'daybook' && request()->segment(2) == null]
                            );
                            
                            // Monthly Dashboard
                            $sub->url(
                                action([\Modules\Daybook\Http\Controllers\DaybookController::class, 'monthlyDashboard']), 
                                $monthly_dashboard_label, 
                                ['icon' => '', 'active' => request()->segment(1) == 'daybook' && request()->segment(2) == 'monthly-dashboard']
                            );
                            
                            // Monthly Cashbook Report
                            $sub->url(
                                action([\Modules\Daybook\Http\Controllers\DaybookController::class, 'monthlyCashbook']), 
                                $monthly_cashbook_label, 
                                ['icon' => '', 'active' => request()->segment(1) == 'daybook' && request()->segment(2) == 'monthly-cashbook']
                            );
                            
                            // Daily Cashbook Report
                            $sub->url(
                                action([\Modules\Daybook\Http\Controllers\DaybookController::class, 'dailyCashbook']),
                                $daily_cashbook_label,
                                ['icon' => '', 'active' => request()->segment(1) == 'daybook' && request()->segment(2) == 'daily-cashbook']
                            );

                            // Daily Payment Report
                            $sub->url(
                                action([\Modules\Daybook\Http\Controllers\DaybookController::class, 'dailyPayment']),
                                $daily_payment_label,
                                ['icon' => '', 'active' => request()->segment(1) == 'daybook' && request()->segment(2) == 'daily-payment']
                            );
                        },
                        [
                            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                <path d="M9 9l1 0"/>
                                <path d="M9 13l6 0"/>
                                <path d="M9 17l6 0"/>
                            </svg>', 
                            'active' => request()->segment(1) == 'daybook'
                        ]
                    )
                    ->order(85);
                } else {
                    \Log::error('Daybook: Menu instance not found');
                }
            }
        } catch (\Exception $e) {
            \Log::error('Daybook menu modification error: ' . $e->getMessage());
            \Log::error('Daybook menu error trace: ' . $e->getTraceAsString());
        }
    }
}

