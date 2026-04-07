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
        Schema::table('appointment_details', function (Blueprint $table) {
            $table->string('requester_pincode')->nullable()->after('requester_phone');
            $table->enum('requester_economic_service', ['Yes', 'No'])->nullable()->after('requester_pincode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_details', function (Blueprint $table) {
            $table->dropColumn(['requester_pincode', 'requester_economic_service']);
        });
    }
};
