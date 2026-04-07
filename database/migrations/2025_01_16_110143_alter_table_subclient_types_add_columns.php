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
        Schema::table('subclient_types', function (Blueprint $table) {
            $table->string('email')->nullable()->after('name');
            $table->string('password')->nullable()->after('email');
            $table->string('phone')->nullable()->after('password');
        });

        Schema::create('subclient_type_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subclient_type_id');
            $table->foreign('subclient_type_id')->references('id')->on('subclient_types')->onDelete('cascade');
            $table->boolean('us_based')->default(0);
            $table->boolean('non_us_based')->default(0);
            $table->boolean('english_to_target')->default(0);
            $table->boolean('spanish_to_target')->default(0);
            $table->timestamps();
        });

        Schema::create('subclient_types_dynamic_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subclient_type_id');
            $table->foreign('subclient_type_id')->references('id')->on('subclient_types')->onDelete('cascade');
            $table->string('name', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
