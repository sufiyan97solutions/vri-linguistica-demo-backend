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
        Schema::create('subclient_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subclient_id');
            $table->foreign('subclient_id')->references('id')->on('subclients')->onDelete('cascade');
            $table->boolean('us_based')->default(0);
            $table->boolean('non_us_based')->default(0);
            $table->boolean('english_to_target')->default(0);
            $table->boolean('spanish_to_target')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subclient_filters');
    }
};
