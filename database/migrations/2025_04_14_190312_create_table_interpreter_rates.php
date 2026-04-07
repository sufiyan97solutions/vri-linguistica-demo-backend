<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('interpreter_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('interpreter_id');
            $table->foreign('interpreter_id')->references('id')->on('interpreters')->onDelete('cascade');
            
            $table->string('normal_hour_start_time')->nullable();
            $table->string('normal_hour_end_time')->nullable();
            $table->string('after_hour_start_time')->nullable();
            $table->string('after_hour_end_time')->nullable();
            $table->integer('grace_period')->nullable();

            $table->integer('opi_normal_rate');
            $table->integer('vri_normal_rate');
            $table->integer('inperson_normal_rate');
            $table->string('opi_normal_rate_time_unit');
            $table->string('vri_normal_rate_time_unit');
            $table->string('inperson_normal_rate_time_unit');

            $table->integer('opi_normal_mins');
            $table->integer('vri_normal_mins');
            $table->integer('inperson_normal_mins');
            $table->string('opi_normal_min_time_unit');
            $table->string('vri_normal_min_time_unit');
            $table->string('inperson_normal_min_time_unit');

            $table->integer('opi_after_rate');
            $table->integer('vri_after_rate');
            $table->integer('inperson_after_rate');
            $table->string('opi_after_rate_time_unit');
            $table->string('vri_after_rate_time_unit');
            $table->string('inperson_after_rate_time_unit');

            $table->integer('opi_after_mins');
            $table->integer('vri_after_mins');
            $table->integer('inperson_after_mins');
            $table->string('opi_after_min_time_unit');
            $table->string('vri_after_min_time_unit');
            $table->string('inperson_after_min_time_unit');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interpreter_rates');
    }
};
