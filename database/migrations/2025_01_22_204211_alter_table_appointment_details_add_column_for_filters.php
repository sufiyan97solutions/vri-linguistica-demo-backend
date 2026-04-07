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
            $table->boolean('medical_certified')->default(0)->after('department_text');
            $table->boolean('court_certified')->default(0)->after('department_text');
            $table->boolean('spanish_to_target')->default(0)->after('department_text');
            $table->boolean('english_to_target')->default(0)->after('department_text');
            $table->boolean('non_us_based')->default(0)->after('department_text');
            $table->boolean('us_based')->default(0)->after('department_text');
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
