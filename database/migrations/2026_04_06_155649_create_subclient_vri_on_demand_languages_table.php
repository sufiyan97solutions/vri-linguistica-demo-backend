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
        Schema::create('subclient_vri_on_demand_languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subclient_id');
            $table->unsignedBigInteger('language_id');
           
            $table->foreign('subclient_id')->references('id')->on('subclient_types')->onDelete('cascade');
            $table->foreign('language_id')->references('id')->on('languages')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subclient_vri_on_demand_languages');
    }
};
