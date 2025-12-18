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
        Schema::table('academy_classes', function (Blueprint $table) {
            $table->boolean('auto_generate_sessions')->default(false);
            $table->time('default_session_start_time')->nullable();
            $table->time('default_session_end_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('academy_classes', function (Blueprint $table) {
            $table->dropColumn(['auto_generate_sessions', 'default_session_start_time', 'default_session_end_time']);
        });
    }
};
