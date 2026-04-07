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
        Schema::table('appointment_assigns', function (Blueprint $table) {
            $table->string('notes', 500)->nullable();
            $table->enum('comments', ['Admission/Intake', 'Discharge', 'Informed Consent', 'Service Decline', 'Other'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_assigns', function (Blueprint $table) {
            //
        });
    }
};
