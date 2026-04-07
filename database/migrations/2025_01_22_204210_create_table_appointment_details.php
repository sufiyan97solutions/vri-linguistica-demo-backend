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
        Schema::create('appointment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->string('requester_name')->nullable();
            $table->string('requester_email')->nullable();
            $table->string('requester_phone')->nullable();
            $table->unsignedBigInteger('facility_id')->nullable();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('department_text')->nullable();
            $table->longText('dynamic_fields')->nullable();
            $table->enum('interpreter_gender',['male','female','nonbinary'])->nullable();
            $table->string('notes')->nullable();
            $table->text('video_link')->nullable();
            $table->string('reschedule_reason')->nullable();
            $table->string('cnc_reason')->nullable();
            $table->string('dnc_reason')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
            // $table->dropColumn('account_id');
            $table->dropColumn('requester_name');
            $table->dropColumn('caller_phone');
            $table->dropColumn('gender');
            $table->dropColumn('dynamic_fields');
            $table->dropColumn('video_link');
            $table->dropColumn('reschedule_reason');
            $table->dropColumn('cnc_reason');
            $table->dropColumn('dnc_reason');
            $table->dropColumn('cancel_reason');
            $table->dropColumn('facility_id');
            $table->dropColumn('department_id');
            $table->unsignedBigInteger('account_id')->after('appid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_details');
    }
};
