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
        Schema::table('subclient_type_filters', function (Blueprint $table) {
            $table->boolean('us_based_locked')->default(0)->after('us_based');
            $table->boolean('non_us_based_locked')->default(0)->after('non_us_based');
            $table->boolean('english_to_target_locked')->default(0)->after('english_to_target');
            $table->boolean('spanish_to_target_locked')->default(0)->after('spanish_to_target');
            $table->boolean('court_certified_locked')->default(0)->after('court_certified');
            $table->boolean('medical_certified_locked')->default(0)->after('medical_certified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subclient_type_filters', function (Blueprint $table) {
            //
        });
    }
};
