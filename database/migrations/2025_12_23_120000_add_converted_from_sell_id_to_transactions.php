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
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('converted_from_sell_id')->nullable()->after('id');
            
            // Add index for performance
            $table->index('converted_from_sell_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop index
            $table->dropIndex(['converted_from_sell_id']);
            
            // Drop column
            $table->dropColumn('converted_from_sell_id');
        });
    }
};
