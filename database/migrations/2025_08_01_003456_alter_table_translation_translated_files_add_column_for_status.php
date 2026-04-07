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
        Schema::table('translation_translated_files', function (Blueprint $table) {
            $table->enum('status',[
                'Under Review',
                'Approved',
                'Rejected',
            ])->default('Under Review')->after('rejected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('translation_translated_files', function (Blueprint $table) {
            //
        });
    }
};
