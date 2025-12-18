<?php

namespace Modules\CustomerMonthlySales\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Utils\ModuleUtil;
use Menu;

class DataController extends Controller
{
    /**
     * Defines module as a superadmin package.
     * @return Array
     */
    public function superadmin_package()
    {
        return [
            [
                'name' => 'CustomerMonthlySales',
                'label' => __('Customer Monthly Sales Report Module'),
                'default' => false
            ]
        ];
    }

    /**
     * Defines user permissions for the module.
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'customermonthlysales.view',
                'label' => __('View Customer Monthly Sales Report'),
                'default' => false
            ],
            [
                'value' => 'customermonthlysales.access',
                'label' => __('Access Customer Monthly Sales Module'),
                'default' => false
            ],
        ];
    }

    /**
     * Add menu items to admin sidebar
     * Note: Customer Monthly Sales is now added inside the Reports menu in AdminSidebarMenu.php
     */
    public function modifyAdminMenu()
    {
        // Menu is now handled in the main AdminSidebarMenu middleware
        // No action needed here
    }
}

