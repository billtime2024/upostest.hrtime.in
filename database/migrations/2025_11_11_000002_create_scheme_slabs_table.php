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
        Schema::create('scheme_slabs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scheme_id');
            $table->decimal('from_amount', 22, 4);
            $table->decimal('to_amount', 22, 4)->nullable();
            $table->enum('commission_type', ['fixed', 'percentage']);
            $table->decimal('value', 22, 4);
            $table->timestamps();

            $table->foreign('scheme_id')->references('id')->on('schemes')->onDelete('cascade');
            $table->index('scheme_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scheme_slabs');
    }
};