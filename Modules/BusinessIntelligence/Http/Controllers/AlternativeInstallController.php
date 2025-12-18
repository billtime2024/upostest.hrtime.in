<?php

namespace Modules\BusinessIntelligence\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlternativeInstallController extends Controller
{
    /**
     * Alternative installation method - runs SQL directly
     */
    public function installDirect(Request $request)
    {
        try {
            $businessId = $request->session()->get('user.business_id');
            
            // Drop existing tables if any
            $this->dropTables();
            
            // Create tables manually
            $this->createTables();
            
            // Insert default configurations
            $this->insertDefaultConfigurations($businessId);
            
            // Insert migration records so Laravel knows these tables exist
            $this->insertMigrationRecords();
            
            return response()->json([
                'success' => true,
                'message' => 'Business Intelligence module installed successfully via direct method!',
            ]);
            
        } catch (\Exception $e) {
            \Log::error('BI Direct Installation Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    protected function dropTables()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('DROP TABLE IF EXISTS bi_predictions');
        DB::statement('DROP TABLE IF EXISTS bi_metrics_cache');
        DB::statement('DROP TABLE IF EXISTS bi_alerts');
        DB::statement('DROP TABLE IF EXISTS bi_reports');
        DB::statement('DROP TABLE IF EXISTS bi_insights');
        DB::statement('DROP TABLE IF EXISTS bi_configurations');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        // Also remove migration records so they can be run again if needed
        DB::table('migrations')->where('migration', 'LIKE', '%_create_bi_%')->delete();
    }
    
    protected function createTables()
    {
        // Create bi_configurations table
        DB::statement("
            CREATE TABLE `bi_configurations` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `business_id` int(10) unsigned NOT NULL,
                `config_key` varchar(100) NOT NULL,
                `config_value` text,
                `config_type` enum('string','boolean','integer','json','array') DEFAULT 'string',
                `category` varchar(50) DEFAULT NULL,
                `description` text,
                `is_active` tinyint(1) NOT NULL DEFAULT '1',
                `created_by` int(10) unsigned DEFAULT NULL,
                `updated_by` int(10) unsigned DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `bi_configurations_business_id_config_key_index` (`business_id`,`config_key`),
                CONSTRAINT `bi_configurations_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create bi_insights table
        DB::statement("
            CREATE TABLE `bi_insights` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `business_id` int(10) unsigned NOT NULL,
                `insight_type` varchar(50) NOT NULL,
                `category` varchar(50) DEFAULT NULL,
                `title` varchar(255) NOT NULL,
                `description` text NOT NULL,
                `data` json DEFAULT NULL,
                `confidence_score` decimal(5,2) NOT NULL DEFAULT '0.00',
                `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
                `status` enum('active','acknowledged','resolved','dismissed') NOT NULL DEFAULT 'active',
                `action_items` json DEFAULT NULL,
                `icon` varchar(50) DEFAULT NULL,
                `color` varchar(20) NOT NULL DEFAULT 'blue',
                `insight_date` datetime NOT NULL,
                `acknowledged_at` datetime DEFAULT NULL,
                `acknowledged_by` int(10) unsigned DEFAULT NULL,
                `acknowledgement_note` text,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `bi_insights_business_id_insight_type_status_index` (`business_id`,`insight_type`,`status`),
                KEY `bi_insights_business_id_insight_date_index` (`business_id`,`insight_date`),
                CONSTRAINT `bi_insights_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create bi_reports table
        DB::statement("
            CREATE TABLE `bi_reports` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `business_id` int(10) unsigned NOT NULL,
                `report_name` varchar(255) NOT NULL,
                `report_type` varchar(50) NOT NULL,
                `description` text,
                `report_date_from` date NOT NULL,
                `report_date_to` date NOT NULL,
                `filters` json DEFAULT NULL,
                `report_data` longtext NOT NULL,
                `summary_metrics` json DEFAULT NULL,
                `chart_configs` json DEFAULT NULL,
                `status` enum('generating','completed','failed') NOT NULL DEFAULT 'generating',
                `error_message` text,
                `file_path` varchar(255) DEFAULT NULL,
                `generated_by` int(10) unsigned NOT NULL,
                `generated_at` datetime DEFAULT NULL,
                `view_count` int(11) NOT NULL DEFAULT '0',
                `last_viewed_at` datetime DEFAULT NULL,
                `is_scheduled` tinyint(1) NOT NULL DEFAULT '0',
                `schedule_frequency` varchar(20) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `bi_reports_business_id_report_type_index` (`business_id`,`report_type`),
                KEY `bi_reports_business_id_report_date_from_report_date_to_index` (`business_id`,`report_date_from`,`report_date_to`),
                CONSTRAINT `bi_reports_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create bi_alerts table
        DB::statement("
            CREATE TABLE `bi_alerts` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `business_id` int(10) unsigned NOT NULL,
                `alert_type` varchar(50) NOT NULL,
                `title` varchar(255) NOT NULL,
                `message` text NOT NULL,
                `severity` enum('info','warning','danger','critical') NOT NULL DEFAULT 'info',
                `related_data` json DEFAULT NULL,
                `action_url` varchar(255) DEFAULT NULL,
                `action_label` varchar(100) DEFAULT NULL,
                `status` enum('active','acknowledged','resolved','dismissed') NOT NULL DEFAULT 'active',
                `triggered_at` datetime NOT NULL,
                `resolved_at` datetime DEFAULT NULL,
                `resolved_by` int(10) unsigned DEFAULT NULL,
                `resolution_note` text,
                `notification_sent` tinyint(1) NOT NULL DEFAULT '0',
                `notification_sent_at` datetime DEFAULT NULL,
                `notified_users` json DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `bi_alerts_business_id_alert_type_status_index` (`business_id`,`alert_type`,`status`),
                KEY `bi_alerts_business_id_triggered_at_index` (`business_id`,`triggered_at`),
                CONSTRAINT `bi_alerts_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create bi_metrics_cache table
        DB::statement("
            CREATE TABLE `bi_metrics_cache` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `business_id` int(10) unsigned NOT NULL,
                `metric_key` varchar(100) NOT NULL,
                `period_type` varchar(20) NOT NULL,
                `period_date` date NOT NULL,
                `metric_value` json NOT NULL,
                `metadata` json DEFAULT NULL,
                `calculated_at` datetime NOT NULL,
                `expires_at` datetime DEFAULT NULL,
                `is_stale` tinyint(1) NOT NULL DEFAULT '0',
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `bi_metrics_unique` (`business_id`,`metric_key`,`period_type`,`period_date`),
                KEY `bi_metrics_cache_business_id_metric_key_period_date_index` (`business_id`,`metric_key`,`period_date`),
                KEY `bi_metrics_cache_expires_at_is_stale_index` (`expires_at`,`is_stale`),
                CONSTRAINT `bi_metrics_cache_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create bi_predictions table
        DB::statement("
            CREATE TABLE `bi_predictions` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `business_id` int(10) unsigned NOT NULL,
                `prediction_type` varchar(50) NOT NULL,
                `target_entity_type` varchar(50) DEFAULT NULL,
                `target_entity_id` bigint(20) unsigned DEFAULT NULL,
                `prediction_date` date NOT NULL,
                `predicted_values` json NOT NULL,
                `confidence_intervals` json DEFAULT NULL,
                `accuracy_score` decimal(5,2) DEFAULT NULL,
                `actual_values` json DEFAULT NULL,
                `model_used` varchar(50) NOT NULL DEFAULT 'rule-based',
                `model_parameters` json DEFAULT NULL,
                `training_data_summary` json DEFAULT NULL,
                `predicted_at` datetime NOT NULL,
                `validated_at` datetime DEFAULT NULL,
                `notes` text,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `bi_predictions_business_id_prediction_type_prediction_date_index` (`business_id`,`prediction_type`,`prediction_date`),
                KEY `bi_predictions_target_entity_type_target_entity_id_index` (`target_entity_type`,`target_entity_id`),
                CONSTRAINT `bi_predictions_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    protected function insertDefaultConfigurations($businessId)
    {
        $configs = [
            [
                'business_id' => $businessId,
                'config_key' => 'refresh_interval',
                'config_value' => '300',
                'config_type' => 'integer',
                'category' => 'dashboard',
                'description' => 'Dashboard refresh interval in seconds',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => $businessId,
                'config_key' => 'enable_ai_insights',
                'config_value' => '1',
                'config_type' => 'boolean',
                'category' => 'ai',
                'description' => 'Enable AI-powered insights',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => $businessId,
                'config_key' => 'low_stock_threshold',
                'config_value' => '10',
                'config_type' => 'integer',
                'category' => 'alerts',
                'description' => 'Low stock alert threshold',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => $businessId,
                'config_key' => 'overdue_days_threshold',
                'config_value' => '30',
                'config_type' => 'integer',
                'category' => 'alerts',
                'description' => 'Overdue payment threshold in days',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        DB::table('bi_configurations')->insert($configs);
    }
    
    protected function insertMigrationRecords()
    {
        $migrations = [
            '2024_01_01_000001_create_bi_configurations_table',
            '2024_01_01_000002_create_bi_insights_table',
            '2024_01_01_000003_create_bi_reports_table',
            '2024_01_01_000004_create_bi_alerts_table',
            '2024_01_01_000005_create_bi_metrics_cache_table',
            '2024_01_01_000006_create_bi_predictions_table',
        ];
        
        $batch = DB::table('migrations')->max('batch') + 1;
        
        foreach ($migrations as $migration) {
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => $batch,
            ]);
        }
    }
}

