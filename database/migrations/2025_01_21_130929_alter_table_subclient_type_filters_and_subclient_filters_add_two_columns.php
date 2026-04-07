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
            $table->boolean('court_certified')->default(0)->after('spanish_to_target');
            $table->boolean('medical_certified')->default(0)->after('court_certified');
        });

        Schema::table('subclient_filters', function (Blueprint $table) {
            $table->boolean('court_certified')->default(0)->after('spanish_to_target');
            $table->boolean('medical_certified')->default(0)->after('court_certified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subclient_type_filters', function (Blueprint $table) {
            $table->dropColumn(['court_certified', 'medical_certified']);
        });

        Schema::table('subclient_filters', function (Blueprint $table) {
            $table->dropColumn(['court_certified', 'medical_certified']);
        });
    }
};
