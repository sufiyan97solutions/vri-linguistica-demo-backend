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
        Schema::table('min_durations', function (Blueprint $table) {
            $table->unsignedBigInteger('type_id')->nullable()->change();
            $table->unsignedBigInteger('subclient_id')->nullable()->after('type_id');
            $table->foreign('subclient_id')->references('id')->on('subclients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('min_durations');
    }
};
