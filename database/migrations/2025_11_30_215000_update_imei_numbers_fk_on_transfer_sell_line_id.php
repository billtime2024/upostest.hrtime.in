<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateImeiNumbersFkOnTransferSellLineId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imei_numbers', function (Blueprint $table) {
            // Drop existing foreign key (if present) and re-create with ON DELETE SET NULL.
            $table->dropForeign(['transfer_sell_line_id']);
            $table->foreign('transfer_sell_line_id')
                ->references('id')
                ->on('transaction_sell_lines')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('imei_numbers', function (Blueprint $table) {
            $table->dropForeign(['transfer_sell_line_id']);
            $table->foreign('transfer_sell_line_id')
                ->references('id')
                ->on('transaction_sell_lines');
        });
    }
}