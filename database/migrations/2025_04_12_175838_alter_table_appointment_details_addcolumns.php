<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointment_details', function (Blueprint $table) {
            // Columns to drop
            $table->dropColumn([
                'department_text',
                'us_based',
                'non_us_based',
                'english_to_target',
                'spanish_to_target',
                'court_certified',
                'medical_certified',
                'dynamic_fields',
                'interpreter_gender',
                'notes'
            ]);

            // New columns
            $table->enum('business_division', ['Castell', 'Intermountain', 'Medical Group', 'Tellica'])->nullable();
            $table->string('special_instruction', 200)->nullable();
            $table->enum('encounter_source', ['Service Hub', 'Revenue Cycle', 'Telephonic', 'Other', 'List'])->nullable();
            $table->enum('priority_level', ['Regular', 'Low', 'High'])->nullable();

            $table->string('mrn_number')->nullable();
            $table->string('patient_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('medicaid_id')->nullable();
            $table->string('medicaid_plan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_details', function (Blueprint $table) {
            // Reverse new columns
            $table->dropColumn([
                'business_division',
                'special_instruction',
                'encounter_source',
                'priority_level',
                'mrn_number',
                'patient_name',
                'birth_date',
                'provider_name',
                'medicaid_id',
                'medicaid_plan'
            ]);

            // Restore dropped columns (you’ll need to define types again)
            $table->string('department_text')->nullable();
            $table->boolean('us_based')->nullable();
            $table->boolean('non_us_based')->nullable();
            $table->boolean('english_to_target')->nullable();
            $table->boolean('spanish_to_target')->nullable();
            $table->boolean('court_certified')->nullable();
            $table->boolean('medical_certified')->nullable();
            $table->json('dynamic_fields')->nullable();
            $table->string('interpreter_gender')->nullable();
            $table->text('notes')->nullable();

            // Old status enum can't be restored unless exact values are known, so warning
            // $table->enum('status', ['old', 'values'])->change(); // replace with actual old values if needed
        });
    }
};
