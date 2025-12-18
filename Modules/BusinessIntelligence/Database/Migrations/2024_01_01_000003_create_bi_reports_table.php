<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBiReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bi_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('report_name', 255);
            $table->string('report_type', 50); // 'sales_analysis', 'inventory_analysis', 'financial_summary', etc.
            $table->text('description')->nullable();
            $table->date('report_date_from');
            $table->date('report_date_to');
            $table->json('filters')->nullable(); // User applied filters
            $table->longText('report_data'); // JSON stored report data
            $table->json('summary_metrics')->nullable(); // Key metrics summary
            $table->json('chart_configs')->nullable(); // Chart configurations
            $table->enum('status', ['generating', 'completed', 'failed'])->default('generating');
            $table->text('error_message')->nullable();
            $table->string('file_path')->nullable(); // For exported reports
            $table->unsignedInteger('generated_by');
            $table->dateTime('generated_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->dateTime('last_viewed_at')->nullable();
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency', 20)->nullable(); // 'daily', 'weekly', 'monthly'
            $table->timestamps();
            
            $table->index(['business_id', 'report_type']);
            $table->index(['business_id', 'report_date_from', 'report_date_to']);
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
        Schema::dropIfExists('bi_reports');
    }
}

