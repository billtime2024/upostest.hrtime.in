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
        Schema::table('schemes', function (Blueprint $table) {
            $table->enum('scheme_type', ['fixed', 'percentage'])->default('fixed')->after('scheme_amount');
            $table->boolean('enable_slab')->default(false)->after('scheme_type');
            $table->enum('slab_calculation_type', ['flat', 'incremental'])->nullable()->after('enable_slab');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('schemes', function (Blueprint $table) {
            $table->dropColumn(['scheme_type', 'enable_slab', 'slab_calculation_type']);
        });
    }
};