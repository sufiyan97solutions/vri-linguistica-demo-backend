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
        Schema::create('interpreters', function (Blueprint $table) {
            $table->id();
            $table->string('code',50)->nullable();
            $table->string('first_name',150)->nullable();
            $table->string('last_name',150)->nullable();
            $table->string('phone',20)->nullable();
            $table->string('secondary_number',20)->nullable();
            $table->string('secondary_email',200)->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('zip_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interpreters');
    }
};
