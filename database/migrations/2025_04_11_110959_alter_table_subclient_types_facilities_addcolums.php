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
            $table->renameColumn('facility_name', 'abbreviation');
            $table->renameColumn('street_address', 'address');
            $table->string('phone')->nullable();
            $table->boolean('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subclient_types_facilities', function (Blueprint $table) {
            $table->renameColumn('abbreviation', 'facility_name');
            $table->renameColumn('address', 'street_address');
            $table->dropColumn('phone');
            $table->dropColumn('status');
        });
    }
};
