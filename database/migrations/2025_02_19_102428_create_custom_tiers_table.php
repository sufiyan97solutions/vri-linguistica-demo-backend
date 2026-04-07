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
        Schema::create('custom_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subclient_id');
            $table->foreign('subclient_id')->references('id')->on('subclient_types')->onDelete('cascade');

            $table->integer('opi_normal_rate');
            $table->integer('vri_normal_rate');
            $table->integer('inperson_normal_rate');
            $table->string('opi_normal_rate_time_unit');
            $table->string('vri_normal_rate_time_unit');
            $table->string('inperson_normal_time_unit');

            $table->integer('opi_normal_mins');
            $table->integer('vri_normal_mins');
            $table->integer('inperson_normal_mins');

            $table->integer('opi_after_rate');
            $table->integer('vri_after_rate');
            $table->integer('inperson_after_rate');
            $table->string('opi_after_rate_time_unit');
            $table->string('vri_after_rate_time_unit');
            $table->string('inperson_after_time_unit');

            $table->integer('opi_after_mins');
            $table->integer('vri_after_mins');
            $table->integer('inperson_after_mins');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_tiers');
    }
};
