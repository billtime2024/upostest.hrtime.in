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
        Schema::table('academy_courses', function (Blueprint $table) {
            $table->boolean('is_fixed_session')->default(false)->after('capacity');
            $table->integer('session_duration_minutes')->nullable()->after('is_fixed_session');
            $table->integer('sessions_per_week')->nullable()->after('session_duration_minutes');
            $table->integer('total_sessions')->nullable()->after('sessions_per_week');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('academy_courses', function (Blueprint $table) {
            $table->dropColumn(['is_fixed_session', 'session_duration_minutes', 'sessions_per_week', 'total_sessions']);
        });
    }
};
