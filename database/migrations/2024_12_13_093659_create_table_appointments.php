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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subclient_id')->nullable();
            $table->enum('type',['In Person','Video Remote Interpretation','OTP Pre Schedule','OPI On Demand'])->default('In Person');
            $table->date('datetime');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->unsignedBigInteger('facility_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('requester_name',100)->nullable();
            $table->string('caller_phone',20)->nullable();
            $table->enum('gender',['male','female','nonbinary'])->nullable();
            $table->integer('estimated_duration')->nullable();
            $table->longText('dynamic_fields')->nullable();
            $table->enum('status',['available','assigned','cancelled','cnc'])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
