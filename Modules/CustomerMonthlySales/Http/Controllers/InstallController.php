<?php

namespace Modules\CustomerMonthlySales\Http\Controllers;

use App\System;
use Composer\Semver\Comparator;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    public function __construct()
    {
        $this->module_name = 'customermonthlysales';
        $this->appVersion = config('customermonthlysales.module_version', '1.1');
    }

    /**
     * Install - Following Exchange module pattern
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

        //Check if CustomerMonthlySales installed or not.
        $is_installed = System::getProperty($this->module_name . '_version');
        
        // If not installed, install it directly (like Exchange module)
        if (empty($is_installed)) {
            try {
                DB::statement('SET default_storage_engine=INNODB;');

                // Run migrations if they exist
                try {
                    Artisan::call('module:migrate', ['module' => 'CustomerMonthlySales', '--force' => true]);
                    \Log::info('CustomerMonthlySales migrations completed');
                } catch (\Exception $e) {
                    // No migrations found, continue anyway
                    \Log::info('No migrations found for CustomerMonthlySales module: ' . $e->getMessage());
                }

                // Publish module assets/config if any
                try {
                    Artisan::call('module:publish', ['module' => 'CustomerMonthlySales']);
                    \Log::info('CustomerMonthlySales assets published');
                } catch (\Exception $e) {
                    // No assets to publish, continue anyway
                    \Log::info('No assets to publish for CustomerMonthlySales module: ' . $e->getMessage());
                }

                // Save module version
                System::addProperty($this->module_name . '_version', $this->appVersion);
                \Log::info('CustomerMonthlySales module version saved: ' . $this->appVersion);
            } catch (\Exception $e) {
                \Log::error('Installation error: ' . $e->getMessage());
                \Log::error('Installation error trace: ' . $e->getTraceAsString());
                throw $e;
            }
        } else {
            \Log::info('CustomerMonthlySales module already installed with version: ' . $is_installed);
        }

        $output = [
            'success' => 1,
            'msg' => 'Customer Monthly Sales module installed successfully',
        ];

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Initialize all install functions
     */
    private function installSettings()
    {
        config(['app.debug' => true]);
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
    }

    /**
     * Uninstall
     * @return Response
     */
    public function uninstall()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty($this->module_name . '_version');

            $output = ['success' => true,
                            'msg' => __("lang_v1.success")
                        ];
        } catch (\Exception $e) {
            $output = ['success' => false,
                        'msg' => $e->getMessage()
                    ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * update module - Following Exchange module pattern
     * @return Response
     */
    public function update()
    {
        //Check if customermonthlysales_version is same as appVersion then 404
        //If appVersion > customermonthlysales_version - run update script.
        //Else there is some problem.
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $customermonthlysales_version = System::getProperty($this->module_name . '_version');

            if (Comparator::greaterThan($this->appVersion, $customermonthlysales_version)) {
                ini_set('max_execution_time', 0);
                ini_set('memory_limit', '512M');
                $this->installSettings();

                DB::statement('SET default_storage_engine=INNODB;');
                
                // Run migrations
                try {
                    Artisan::call('module:migrate', ['module' => 'CustomerMonthlySales', '--force' => true]);
                } catch (\Exception $e) {
                    \Log::info('No migrations to run during update');
                }

                System::setProperty($this->module_name . '_version', $this->appVersion);
            } else {
                abort(404);
            }

            DB::commit();
            
            $output = [
                'success' => 1,
                'msg' => 'Customer Monthly Sales module updated successfully to version ' . $this->appVersion . ' !!'
            ];

            return redirect()
                ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
                ->with('status', $output);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = [
                'success' => false,
                'msg' => $e->getMessage()
            ];
            
            return redirect()
                ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
                ->with('status', $output);
        }
    }
}

