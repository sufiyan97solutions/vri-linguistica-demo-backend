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
        Schema::table('translation_files', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->nullable()->after('file_status');
            // Ensure the 'amount' column is nullable and placed after 'file_status'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('translation_files', function (Blueprint $table) {
            //
        });
    }
};
