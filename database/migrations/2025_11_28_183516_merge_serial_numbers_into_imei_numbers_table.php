<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Copy serial numbers to imei_numbers table with type 'serial'
        DB::statement("
            INSERT INTO imei_numbers (purchase_item_id, type, identifier, is_sold, sell_line_id, sold_at, return_transaction_id, returned_at, created_at, updated_at)
            SELECT purchase_item_id, 'serial', serial_number, is_sold, sell_line_id, sold_at, return_transaction_id, returned_at, created_at, updated_at
            FROM serial_numbers
        ");

        // Drop the serial_numbers table
        Schema::dropIfExists('serial_numbers');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Recreate serial_numbers table
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

        // Move serial data back from imei_numbers to serial_numbers
        DB::statement("
            INSERT INTO serial_numbers (purchase_item_id, serial_number, is_sold, sell_line_id, sold_at, return_transaction_id, returned_at, created_at, updated_at)
            SELECT purchase_item_id, identifier, is_sold, sell_line_id, sold_at, return_transaction_id, returned_at, created_at, updated_at
            FROM imei_numbers
            WHERE type = 'serial'
        ");

        // Delete serial records from imei_numbers
        DB::statement("DELETE FROM imei_numbers WHERE type = 'serial'");
    }
};
