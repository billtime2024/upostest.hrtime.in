<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schemes', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->unsigned();
            $table->string('scheme_name');
            $table->enum('scheme_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('scheme_amount', 22, 4)->default(0);
            $table->boolean('enable_slab')->default(false);
            $table->enum('slab_calculation_type', ['flat', 'incremental'])->nullable();
            $table->integer('supplier_id')->unsigned()->nullable();
            $table->integer('product_id')->unsigned()->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->text('scheme_note')->nullable();
            $table->integer('created_by')->unsigned();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('business_id');
            $table->index('supplier_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('schemes');
    }
};
