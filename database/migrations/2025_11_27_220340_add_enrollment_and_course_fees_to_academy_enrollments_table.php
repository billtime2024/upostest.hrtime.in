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
        Schema::table('academy_enrollments', function (Blueprint $table) {
            $table->decimal('enrollment_fee', 22, 4)->default(0)->after('fee');
            $table->decimal('course_fee', 22, 4)->default(0)->after('enrollment_fee');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('academy_enrollments', function (Blueprint $table) {
            $table->dropColumn(['enrollment_fee', 'course_fee']);
        });
    }
};
