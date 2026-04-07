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
        Schema::table('subclient_types_interpretation_rates', function (Blueprint $table) {
            $table->double('spanish_translation_rate')->default(0);
            $table->double('other_translation_rate')->default(0);
            $table->double('spanish_formatting_rate')->default(0);
            $table->double('other_formatting_rate')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subclient_types_interpretation_rates', function (Blueprint $table) {
            //
        });
    }
};
