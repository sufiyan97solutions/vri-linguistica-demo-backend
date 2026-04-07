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
        Schema::table('client_templates', function (Blueprint $table) {
            $table->integer('rush_fee')->nullable()->after("grace_period");
            $table->string('incremental')->nullable()->after("rush_fee");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_templates', function (Blueprint $table) {
            $table->dropColumn(['rush_fee', 'incremental']);
        });
    }
};
