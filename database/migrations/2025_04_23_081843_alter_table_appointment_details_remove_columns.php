<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('appointment_details', function (Blueprint $table) {
            // Drop the unnecessary columns
            $table->dropColumn([
                'mrn_number',
                'patient_name',
                'birth_date',
                'provider_name',
                'medicaid_id',
                'medicaid_plan',
            ]);

            // Add the foreign key patient_id
            $table->unsignedBigInteger('patient_id')->after('priority_level')->nullable(); // adjust 'after' as needed
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('appointment_details', function (Blueprint $table) {
            // Revert: add columns back (optional: type match previous schema)
            $table->string('mrn_number')->nullable();
            $table->string('patient_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('medicaid_id')->nullable();
            $table->string('medicaid_plan')->nullable();

            // Drop the foreign key
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
        });
    }
};
