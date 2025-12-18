<?php

namespace Modules\BusinessIntelligence\Http\Controllers;

use App\System;
use Composer\Semver\Comparator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    public function __construct()
    {
        $this->module_name = 'businessintelligence';
        $this->appVersion = config('businessintelligence.module_version');
        $this->module_display_name = 'Business Intelligence';
    }

    /**
     * Display installation page
     *
     * @return Response
     */
    public function index()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->installSettings();

        //Check if Business Intelligence installed or not.
        $is_installed = System::getProperty($this->module_name . '_version');
        if (!empty($is_installed)) {
            abort(404);
        }

        $action_url = action([\Modules\BusinessIntelligence\Http\Controllers\InstallController::class, 'install']);
        $intruction_type = 'uf';
        $action_type = 'install';
        $module_display_name = $this->module_display_name;
        
        return view('install.install-module')
            ->with(compact('action_url', 'intruction_type', 'action_type', 'module_display_name'));
    }

    /**
     * Initialize all install functions
     */
    private function installSettings()
    {
        config(['app.debug' => true]);
        Artisan::call('config:clear');
    }

    /**
     * Install the module (No License Required)
     *
     * @return Response
     */
    public function install(Request $request)
    {
        try {
            DB::beginTransaction();
            DB::statement('SET default_storage_engine=INNODB;');
            
            // Run migrations
            Artisan::call('module:migrate', ['module' => 'BusinessIntelligence', '--force' => true]);
            Artisan::call('module:publish', ['module' => 'BusinessIntelligence']);
            
            // Add module version to system properties
            System::addProperty($this->module_name . '_version', $this->appVersion);

            // Create default configurations
            $this->createDefaultConfigurations($request->session()->get('user.business_id'));

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => 'Business Intelligence module installed successfully (No License Required)',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Verify all required tables exist
     */
    protected function verifyTablesExist()
    {
        $tables = [
            'bi_configurations',
            'bi_insights',
            'bi_reports',
            'bi_alerts',
            'bi_metrics_cache',
            'bi_predictions',
        ];

        foreach ($tables as $table) {
            $exists = DB::select("SHOW TABLES LIKE '{$table}'");
            if (empty($exists)) {
                \Log::error("Table {$table} does not exist after migration");
                return false;
            }
        }

        return true;
    }

    /**
     * Clean existing tables before installation
     */
    protected function cleanExistingTables()
    {
        $tables = [
            'bi_predictions',
            'bi_metrics_cache',
            'bi_alerts',
            'bi_reports',
            'bi_insights',
            'bi_configurations',
        ];

        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS {$table}");
        }

        // Also remove migration records so migrations can run again
        DB::table('migrations')->where('migration', 'LIKE', '%_create_bi_%')->delete();
    }

    /**
     * Update module
     *
     * @return Response
     */
    public function update()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->installSettings();

        //Check if Business Intelligence installed or not.
        $is_installed = System::getProperty($this->module_name . '_version');
        if (empty($is_installed)) {
            abort(404);
        }

        //Check if businessintelligence_version is same as appVersion then 404
        if (config('businessintelligence.module_version') == $this->appVersion) {
            abort(404);
        }

        $action_url = action([\Modules\BusinessIntelligence\Http\Controllers\InstallController::class, 'install']);
        $intruction_type = 'uf';
        $action_type = 'update';
        $module_display_name = $this->module_display_name;

        return view('install.install-module')
            ->with(compact('action_url', 'intruction_type', 'action_type', 'module_display_name'));
    }

    /**
     * Uninstall the module
     *
     * @return Response
     */
    public function uninstall()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty($this->module_name . '_version');

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            $output = [
                'success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * Seed default data
     */
    protected function seedDefaultData($businessId)
    {
        // No default data to seed for now
        // Can add sample insights or configurations here
    }

    /**
     * Create default configurations
     */
    protected function createDefaultConfigurations($businessId)
    {
        $defaultConfigs = [
            [
                'business_id' => $businessId,
                'config_key' => 'refresh_interval',
                'config_value' => '300',
                'config_type' => 'integer',
                'category' => 'dashboard',
                'description' => 'Dashboard refresh interval in seconds',
                'is_active' => true,
            ],
            [
                'business_id' => $businessId,
                'config_key' => 'enable_ai_insights',
                'config_value' => '1',
                'config_type' => 'boolean',
                'category' => 'ai',
                'description' => 'Enable AI-powered insights',
                'is_active' => true,
            ],
            [
                'business_id' => $businessId,
                'config_key' => 'low_stock_threshold',
                'config_value' => '10',
                'config_type' => 'integer',
                'category' => 'alerts',
                'description' => 'Low stock alert threshold',
                'is_active' => true,
            ],
            [
                'business_id' => $businessId,
                'config_key' => 'overdue_days_threshold',
                'config_value' => '30',
                'config_type' => 'integer',
                'category' => 'alerts',
                'description' => 'Overdue payment threshold in days',
                'is_active' => true,
            ],
        ];

        foreach ($defaultConfigs as $config) {
            DB::table('bi_configurations')->insert($config);
        }
    }

    /**
     * Check module status
     */
    public function status()
    {
        $tables = [
            'bi_configurations',
            'bi_insights',
            'bi_reports',
            'bi_alerts',
            'bi_metrics_cache',
            'bi_predictions',
        ];

        $status = [];
        foreach ($tables as $table) {
            $exists = DB::select("SHOW TABLES LIKE '{$table}'");
            $status[$table] = !empty($exists);
        }

        return response()->json([
            'success' => true,
            'installed' => !in_array(false, $status),
            'tables' => $status,
        ]);
    }
}

