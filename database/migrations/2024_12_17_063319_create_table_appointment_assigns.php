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
        Schema::create('appointment_assigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->unsignedBigInteger('interpreter_id')->nullable();
            $table->foreign('interpreter_id')->references('id')->on('interpreters')->onDelete('cascade');
            $table->date('checkin_date')->nullable();
            $table->time('checkin_time')->nullable();
            $table->date('checkout_date')->nullable();
            $table->time('checkout_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_assigns');
    }
};
