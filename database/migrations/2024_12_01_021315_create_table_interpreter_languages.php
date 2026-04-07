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
        Schema::create('interpreter_languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('interpreter_id');
            $table->foreign('interpreter_id')->references('id')->on('interpreters')->onDelete('cascade');
            $table->unsignedBigInteger('language_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interpreter_languages');
    }
};
