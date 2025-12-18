<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('purchase_item_id');
            $table->foreign('purchase_item_id')->references('id')->on('purchase_lines')->onDelete('cascade');
            $table->string('serial_number');
            $table->boolean('is_sold')->default(false);
            $table->unsignedInteger('sell_line_id')->nullable();
            $table->foreign('sell_line_id')->references('id')->on('transaction_sell_lines')->nullOnDelete();
            $table->timestamp('sold_at')->nullable();
            $table->unsignedInteger('return_transaction_id')->nullable();
            $table->foreign('return_transaction_id')->references('id')->on('transactions');
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('serial_numbers');
    }
};
