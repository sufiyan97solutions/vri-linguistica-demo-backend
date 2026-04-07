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
        Schema::table('interpreter_rates', function (Blueprint $table) {

            $table->double('opi_normal_rate')->change();
            $table->double('vri_normal_rate')->change();
            $table->double('inperson_normal_rate')->change();

            
            $table->double('opi_after_rate')->change();
            $table->double('vri_after_rate')->change();
            $table->double('inperson_after_rate')->change();
            
            
            $table->double('spanish_translation_rate')->default(0);
            $table->double('other_translation_rate')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interpreter_rates', function (Blueprint $table) {
            //
        });
    }
};
