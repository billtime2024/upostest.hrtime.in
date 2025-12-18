<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBiPredictionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bi_predictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('prediction_type', 50)->index(); // 'sales_forecast', 'inventory_demand', 'cash_flow'
            $table->string('target_entity_type', 50)->nullable(); // 'product', 'category', 'customer', 'business'
            $table->unsignedBigInteger('target_entity_id')->nullable();
            $table->date('prediction_date'); // Date the prediction is for
            $table->json('predicted_values'); // The prediction results
            $table->json('confidence_intervals')->nullable(); // Statistical confidence ranges
            $table->decimal('accuracy_score', 5, 2)->nullable(); // 0-100 for past predictions
            $table->json('actual_values')->nullable(); // Filled in after prediction date passes
            $table->string('model_used', 50)->default('rule-based'); // 'rule-based', 'openai', 'ml-model'
            $table->json('model_parameters')->nullable();
            $table->json('training_data_summary')->nullable(); // Info about data used
            $table->dateTime('predicted_at');
            $table->dateTime('validated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['business_id', 'prediction_type', 'prediction_date']);
            $table->index(['target_entity_type', 'target_entity_id']);
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
        Schema::dropIfExists('bi_predictions');
    }
}

