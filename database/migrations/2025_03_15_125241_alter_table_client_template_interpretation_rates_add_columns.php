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
        Schema::table('client_template_interpretation_rates', function (Blueprint $table) {
            $table->string('opi_normal_mins_time_unit')->nullable();
            $table->string('vri_normal_mins_time_unit')->nullable();
            $table->string('inperson_normal_mins_time_unit')->nullable();
            $table->string('opi_after_mins_time_unit')->nullable();
            $table->string('vri_after_mins_time_unit')->nullable();
            $table->string('inperson_after_mins_time_unit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_template_interpretation_rates', function (Blueprint $table) {
            $table->dropColumn('opi_normal_mins_time_unit');
            $table->dropColumn('vri_normal_mins_time_unit');
            $table->dropColumn('inperson_normal_mins_time_unit');
            $table->dropColumn('opi_after_mins_time_unit');
            $table->dropColumn('vri_after_mins_time_unit');
            $table->dropColumn('inperson_after_mins_time_unit');
        });
    }
};
