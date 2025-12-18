<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBiInsightsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bi_insights', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('insight_type', 50)->index(); // 'sales', 'inventory', 'finance', 'customer', 'supplier', 'prediction'
            $table->string('category', 50)->nullable(); // 'opportunity', 'risk', 'alert', 'recommendation'
            $table->string('title', 255);
            $table->text('description');
            $table->json('data')->nullable(); // Related data/metrics
            $table->decimal('confidence_score', 5, 2)->default(0.00); // AI confidence 0-100
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['active', 'acknowledged', 'resolved', 'dismissed'])->default('active');
            $table->json('action_items')->nullable(); // Suggested actions
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->default('blue'); // For UI display
            $table->dateTime('insight_date');
            $table->dateTime('acknowledged_at')->nullable();
            $table->unsignedInteger('acknowledged_by')->nullable();
            $table->text('acknowledgement_note')->nullable();
            $table->timestamps();
            
            $table->index(['business_id', 'insight_type', 'status']);
            $table->index(['business_id', 'insight_date']);
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
        Schema::dropIfExists('bi_insights');
    }
}

