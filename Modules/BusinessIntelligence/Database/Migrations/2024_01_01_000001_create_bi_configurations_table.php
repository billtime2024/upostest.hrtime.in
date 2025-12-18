<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBiConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bi_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('config_key', 100)->index();
            $table->text('config_value')->nullable();
            $table->enum('config_type', ['string', 'boolean', 'integer', 'json', 'array'])->default('string');
            $table->string('category', 50)->nullable(); // 'dashboard', 'analytics', 'ai', 'alerts'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->index(['business_id', 'config_key']);
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
        Schema::dropIfExists('bi_configurations');
    }
}

