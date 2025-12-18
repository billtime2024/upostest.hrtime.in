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
            $table->enum('type', ['imei', 'serial'])->default('imei')->after('purchase_item_id');
            $table->renameColumn('imei_number', 'identifier');
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
            $table->renameColumn('identifier', 'imei_number');
            $table->dropColumn('type');
        });
    }
};
