<?php

namespace Modules\BusinessIntelligence\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BusinessIntelligence\Entities\BiConfiguration;

class ConfigurationController extends Controller
{
    /**
     * Display configuration page
     */
    public function index(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        
        $configurations = BiConfiguration::where('business_id', $businessId)
            ->orderBy('category')
            ->orderBy('config_key')
            ->get();

        return view('businessintelligence::configuration.index', compact('configurations'));
    }

    /**
     * Get configurations
     */
    public function getConfigurations(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $category = $request->get('category');

        $query = BiConfiguration::where('business_id', $businessId);

        if ($category) {
            $query->where('category', $category);
        }

        $configurations = $query->get();

        return response()->json([
            'success' => true,
            'data' => $configurations
        ]);
    }

    /**
     * Get single configuration
     */
    public function getConfiguration($key, Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        
        $config = BiConfiguration::where('business_id', $businessId)
            ->where('config_key', $key)
            ->first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $config->config_key,
                'value' => $config->getTypedValue(),
                'type' => $config->config_type,
                'category' => $config->category,
                'description' => $config->description,
            ]
        ]);
    }

    /**
     * Update configuration
     */
    public function updateConfiguration(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = $request->session()->get('user.id');
        
        $request->validate([
            'config_key' => 'required|string',
            'config_value' => 'required',
        ]);

        $config = BiConfiguration::where('business_id', $businessId)
            ->where('config_key', $request->config_key)
            ->first();

        if (!$config) {
            // Create new configuration
            $config = new BiConfiguration();
            $config->business_id = $businessId;
            $config->config_key = $request->config_key;
            $config->category = $request->get('category', 'general');
            $config->description = $request->get('description');
            $config->created_by = $userId;
        }

        $config->setTypedValue($request->config_value);
        $config->is_active = $request->get('is_active', true);
        $config->updated_by = $userId;
        $config->save();

        return response()->json([
            'success' => true,
            'message' => 'Configuration updated successfully',
            'data' => $config
        ]);
    }

    /**
     * Update multiple configurations
     */
    public function updateMultiple(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = $request->session()->get('user.id');
        
        $configurations = $request->get('configurations', []);

        foreach ($configurations as $configData) {
            $config = BiConfiguration::where('business_id', $businessId)
                ->where('config_key', $configData['key'])
                ->first();

            if ($config) {
                $config->setTypedValue($configData['value']);
                $config->updated_by = $userId;
                $config->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Configurations updated successfully'
        ]);
    }

    /**
     * Delete configuration
     */
    public function deleteConfiguration($key, Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        
        $config = BiConfiguration::where('business_id', $businessId)
            ->where('config_key', $key)
            ->first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration not found'
            ], 404);
        }

        $config->delete();

        return response()->json([
            'success' => true,
            'message' => 'Configuration deleted successfully'
        ]);
    }

    /**
     * Reset to defaults
     */
    public function resetToDefaults(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        
        // Delete all existing configurations
        BiConfiguration::where('business_id', $businessId)->delete();

        // Recreate default configurations
        $installController = new InstallController();
        $installController->createDefaultConfigurations($businessId);

        return response()->json([
            'success' => true,
            'message' => 'Configurations reset to defaults successfully'
        ]);
    }
}

