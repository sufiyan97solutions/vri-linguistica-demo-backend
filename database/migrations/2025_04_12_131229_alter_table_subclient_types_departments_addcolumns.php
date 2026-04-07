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
        Schema::table('subclient_types_departments', function (Blueprint $table) {
            $table->unsignedBigInteger('facility_id')->nullable();
            $table->foreign('facility_id')->references('id')->on('subclient_types_facilities')->onDelete('cascade');
            $table->string('meeting_place')->nullable();
            $table->string('facility_billing_code')->nullable();
            $table->string('department_billing_code')->nullable();
            $table->boolean('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subclient_types_departments', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
            $table->dropColumn('facility_id');
            $table->dropColumn('meeting_place');
            $table->dropColumn('facility_billing_code');
            $table->dropColumn('department_billing_code');
            $table->dropColumn('status');
        });
    }
};
