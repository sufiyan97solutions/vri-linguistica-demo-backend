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
        Schema::table('subclient_types_facilities', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
        });

        Schema::table('subclient_types_facilities', function (Blueprint $table) {
            $table->string('city_id', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subclient_types_facilities', function (Blueprint $table) {
            //
        });
    }
};
