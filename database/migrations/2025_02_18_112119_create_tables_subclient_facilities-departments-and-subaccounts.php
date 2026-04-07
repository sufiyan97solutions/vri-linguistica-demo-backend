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
        Schema::table('subclient_types_facilities', function (Blueprint $table) {
            $table->string('facility_name')->after('type_id');
            $table->dropColumn('facility_id');
        });

        Schema::table('subclient_types_departments', function (Blueprint $table) {
            $table->string('department_name')->after('type_id');
            $table->dropColumn('departments_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subclient_types_facilities', function (Blueprint $table) {
            $table->dropColumn('facility_name'); // Remove added column
            $table->string('facility_id')->after('type_id'); // Restore deleted column
        });

        Schema::table('subclient_types_departments', function (Blueprint $table) {
            $table->dropColumn('department_name'); // Remove added column
            $table->string('departments_id')->after('type_id'); // Restore deleted column
        });
    }
};
