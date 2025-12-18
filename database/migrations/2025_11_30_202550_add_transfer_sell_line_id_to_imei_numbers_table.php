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
        Schema::table('imei_numbers', function (Blueprint $table) {
            $table->unsignedInteger('transfer_sell_line_id')->nullable()->after('sell_line_id');
            $table->foreign('transfer_sell_line_id')->references('id')->on('transaction_sell_lines');
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
            $table->dropColumn('transfer_sell_line_id');
        });
    }
};
