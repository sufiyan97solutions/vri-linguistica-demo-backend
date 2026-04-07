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
        Schema::create('subclient_types_facilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('type_id');
            $table->foreign('type_id')->references('id')->on('subclient_types')->onDelete('cascade');
            $table->unsignedBigInteger('facility_id');
            $table->timestamps();
        });
        Schema::create('subclient_types_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('type_id');
            $table->foreign('type_id')->references('id')->on('subclient_types')->onDelete('cascade');
            $table->unsignedBigInteger('departments_id');
            $table->timestamps();
        });
        Schema::create('subclient_facilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subclient_id');
            $table->foreign('subclient_id')->references('id')->on('subclients')->onDelete('cascade');
            $table->unsignedBigInteger('facility_id');
            $table->timestamps();
        });
        Schema::create('subclient_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subclient_id');
            $table->foreign('subclient_id')->references('id')->on('subclients')->onDelete('cascade');
            $table->unsignedBigInteger('departments_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subclient_types_facilities');
        Schema::dropIfExists('subclient_types_departments');
        Schema::dropIfExists('subclient_facilities');
        Schema::dropIfExists('subclient_departments');
    }
};
