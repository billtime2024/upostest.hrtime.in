<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBiAlertsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bi_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('alert_type', 50)->index(); // 'low_stock', 'overdue_payment', 'cash_flow', 'expense_spike'
            $table->string('title', 255);
            $table->text('message');
            $table->enum('severity', ['info', 'warning', 'danger', 'critical'])->default('info');
            $table->json('related_data')->nullable(); // IDs, amounts, etc.
            $table->string('action_url')->nullable(); // Direct link to related page
            $table->string('action_label', 100)->nullable(); // Button text
            $table->enum('status', ['active', 'acknowledged', 'resolved', 'dismissed'])->default('active');
            $table->dateTime('triggered_at');
            $table->dateTime('resolved_at')->nullable();
            $table->unsignedInteger('resolved_by')->nullable();
            $table->text('resolution_note')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->dateTime('notification_sent_at')->nullable();
            $table->json('notified_users')->nullable(); // User IDs who were notified
            $table->timestamps();
            
            $table->index(['business_id', 'alert_type', 'status']);
            $table->index(['business_id', 'triggered_at']);
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
        Schema::dropIfExists('bi_alerts');
    }
}

