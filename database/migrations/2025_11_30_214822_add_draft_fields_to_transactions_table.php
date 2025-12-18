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
            $table->timestamp('draft_created_at')->nullable();
            $table->timestamp('draft_updated_at')->nullable();
            $table->unsignedInteger('draft_created_by')->nullable();
            $table->foreign('draft_created_by')->references('id')->on('users')->onDelete('set null');
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
            $table->dropForeign(['draft_created_by']);
            $table->dropColumn(['draft_created_at', 'draft_updated_at', 'draft_created_by']);
        });
    }
};
