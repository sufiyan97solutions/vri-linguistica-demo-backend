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
        Schema::create('translation_invites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('translation_id');
            $table->foreign('translation_id')->references('id')->on('translations')->onDelete('cascade');
            $table->unsignedBigInteger('interpreter_id');
            $table->foreign('interpreter_id')->references('id')->on('interpreters')->onDelete('cascade');
            $table->string('status', 50)->default('pending');
            $table->dateTime('invited_at')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_invites');
    }
};
