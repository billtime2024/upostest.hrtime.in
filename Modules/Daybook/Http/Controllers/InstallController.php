<?php

namespace Modules\Daybook\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\System;

class InstallController extends Controller
{
    protected $module_version = '1.0.0';
    protected $module_name = 'daybook'; // Use lowercase to match ModuleUtil pattern

    public function __construct()
    {
        $this->module_version = config('daybook.module_version', '1.0.0');
    }

    /**
     * Install the module
     */
    public function index()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->installSettings();

        // Check if installed or not
        $is_installed = System::getProperty($this->module_name . '_version');
        if (empty($is_installed)) {
            try {
                DB::statement('SET default_storage_engine=INNODB;');

                \Log::info('Daybook: About to run migration');

                Artisan::call('module:migrate', ['module' => 'Daybook', '--force' => true]); // Use module folder name for migration

                \Log::info('Daybook: Migration completed');

                // Publish assets if needed
                // Artisan::call('module:publish', ['module' => $this->module_name]);

                System::addProperty($this->module_name . '_version', $this->module_version);

                \Log::info('Daybook: Module installed successfully');
            } catch (\Exception $e) {
                \Log::error('Daybook Installation error: ' . $e->getMessage());
                \Log::error('Daybook Installation trace: ' . $e->getTraceAsString());
                
                $output = [
                    'success' => false,
                    'msg' => __('daybook::lang.install_error') . ': ' . $e->getMessage()
                ];

                return redirect()
                    ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
                    ->with('status', $output);
            }
        }

        $output = [
            'success' => 1,
            'msg' => __('daybook::lang.install_success'),
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
        Artisan::call('cache:clear'); // Clear cache like other modules
    }

    /**
     * Update the module
     */
    public function update()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $installed_version = System::getProperty($this->module_name . '_version');

            if (empty($installed_version) || version_compare($this->module_version, $installed_version, '>')) {
                $this->installSettings();

                DB::statement('SET default_storage_engine=INNODB;');
                Artisan::call('module:migrate', ['module' => 'Daybook', '--force' => true]); // Use module folder name for migration
                // Artisan::call('module:publish', ['module' => $this->module_name]);

                System::setProperty($this->module_name . '_version', $this->module_version);

                DB::commit();

                $output = [
                    'success' => 1,
                    'msg' => __('daybook::lang.install_success') . ' - Version ' . $this->module_version,
                ];

                return redirect()
                    ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
                    ->with('status', $output);
            } else {
                abort(404);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Daybook Update error: ' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('daybook::lang.install_error') . ': ' . $e->getMessage()
            ];

            return redirect()
                ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
                ->with('status', $output);
        }
    }

    /**
     * Uninstall the module
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
                'msg' => __('daybook::lang.uninstall_success')
            ];
        } catch (\Exception $e) {
            \Log::emergency("Daybook Module Uninstallation Error: " . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('daybook::lang.uninstall_error') . ': ' . $e->getMessage()
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }
}

