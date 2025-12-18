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
            $table->unsignedInteger('location_id')->nullable()->after('identifier');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');

            $table->unsignedInteger('business_id')->nullable()->after('location_id');
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
        Schema::table('imei_numbers', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');

            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
        });
    }
};