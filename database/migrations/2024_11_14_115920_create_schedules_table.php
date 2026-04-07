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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_id')->nullable();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('meeting_place');
            $table->string('duration');
            $table->enum('priority_level', ['Low', 'High'])->default('High');
            $table->enum('encounter_type', ['Admin', 'On Site', 'OPI Avaya', 'OPI Other', 'Translation', 'VRI','Lunch']);
            $table->enum('type', ['Onsite', 'Lunch'])->nullable();
            $table->enum('job_type', ['Assign', 'Escalation', 'Open'])->nullable();
            $table->unsignedBigInteger('interpreter_id')->nullable();
            $table->foreign('interpreter_id')->references('id')->on('users');
            $table->timestamps();
        });

        // Fields for "Assign" job type
        Schema::create('schedule_assigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->foreign('schedule_id')->references('id')->on('schedules')->onDelete('cascade');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->foreign('vendor_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('interpreter_id')->nullable();
            $table->foreign('interpreter_id')->references('id')->on('users')->onDelete('cascade');
            $table->date('checkin_date')->nullable();
            $table->time('checkin_time')->nullable();
            $table->date('checkout_date')->nullable();
            $table->time('checkout_time')->nullable();
            $table->string('notes', 500)->nullable();
            $table->enum('comments', ['Admission/Intake', 'Discharge', 'Informed Consent', 'Service Decline', 'Other'])->nullable();

            $table->timestamps();
        });

        // Schema::create('schedule_pool_escalations', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('schedule_id');
        //     $table->unsignedBigInteger('pool_id');
        //     $table->dateTime('end');
        //     $table->foreign('schedule_id')->references('id')->on('schedules')->onDelete('cascade');
        //     $table->foreign('pool_id')->references('id')->on('pools')->onDelete('cascade');
        //     $table->timestamps();
        // });
    }

    public function down(): void
    {
        // Schema::dropIfExists('schedule_pool_escalations');
        Schema::dropIfExists('schedule_assigns');
        Schema::dropIfExists('schedules');
    }
};
