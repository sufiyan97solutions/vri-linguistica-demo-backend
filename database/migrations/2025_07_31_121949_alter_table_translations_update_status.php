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
        Schema::table('translations', function (Blueprint $table) {
            \DB::statement("ALTER TABLE translations MODIFY COLUMN status ENUM('New Request','Quote Sent','Quote Rejected','Quote Revised','Quote Approved','Translators Invited','Assigned','Under Review','Client Request Editing','Client Cancelled','Submission Rejected','Invoice Sent','Translator Declined','Cancelled','Completed') NOT NULL DEFAULT 'New Request'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            //
        });
    }
};
