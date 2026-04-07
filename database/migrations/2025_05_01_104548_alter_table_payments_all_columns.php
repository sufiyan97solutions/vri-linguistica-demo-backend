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
        Schema::table('payments', function (Blueprint $table) {
            $table->integer('rate')->nullable();
            $table->string('rate_unit')->nullable();
            $table->integer('min_duration')->nullable();
            $table->string('duration_unit')->nullable();
            $table->integer('extra_duration')->nullable();
            $table->integer('total_hours')->nullable();
            $table->integer('extra_mileage')->nullable();
            $table->integer('payment')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('rate');
            $table->dropColumn('rate_unit');
            $table->dropColumn('min_duration');
            $table->dropColumn('duration_unit');
            $table->dropColumn('extra_duration');
            $table->dropColumn('total_hours');
            $table->dropColumn('extra_mileage');
            $table->string('payment')->change();
        });
    }
    
};
