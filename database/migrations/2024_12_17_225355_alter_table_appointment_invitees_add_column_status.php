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
        Schema::table('appointment_invitees', function (Blueprint $table) {
            $table->enum('status',['pending','accepted','rejected','expired'])->default('pending')->after('interpreter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_invitees', function (Blueprint $table) {
            //
        });
    }
};
