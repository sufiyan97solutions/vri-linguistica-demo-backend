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
        Schema::create('subclient_dynamic_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subclient_id');
            $table->foreign('subclient_id')->references('id')->on('subclients')->onDelete('cascade');
            $table->string('name',100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subclient_dynamic_fields');
    }
};
