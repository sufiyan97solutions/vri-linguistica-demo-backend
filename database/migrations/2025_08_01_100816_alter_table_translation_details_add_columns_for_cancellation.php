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
        Schema::table('translation_details', function (Blueprint $table) {
            $table->string('cancel_reason')->nullable()->after('translation_decline_reason');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancel_reason');
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
            $table->datetime('cancelled_at')->nullable()->after('cancelled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('translation_details', function (Blueprint $table) {
            //
        });
    }
};
