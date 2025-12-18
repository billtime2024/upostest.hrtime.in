<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBiMetricsCacheTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bi_metrics_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('metric_key', 100)->index(); // 'daily_sales', 'monthly_revenue', 'inventory_value'
            $table->string('period_type', 20); // 'daily', 'weekly', 'monthly', 'yearly', 'custom'
            $table->date('period_date')->index(); // The date this metric is for
            $table->json('metric_value'); // The calculated metric data
            $table->json('metadata')->nullable(); // Additional context
            $table->dateTime('calculated_at');
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_stale')->default(false);
            $table->timestamps();
            
            $table->unique(['business_id', 'metric_key', 'period_type', 'period_date'], 'bi_metrics_unique');
            $table->index(['business_id', 'metric_key', 'period_date']);
            $table->index(['expires_at', 'is_stale']);
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bi_metrics_cache');
    }
}

