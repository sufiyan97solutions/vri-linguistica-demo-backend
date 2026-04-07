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
        Schema::table('appointments', function (Blueprint $table) {
            // Update the 'type' enum values
            $table->enum('type', [
                'In Person',
                'VRI',
                'OPI',
                'OPI On Demand',
            ])->change();

            // Drop foreign key and column for subclient_id
            // if (Schema::hasColumn('appointments', 'subclient_id')) {
            //     $table->dropForeign(['subclient_id']); // remove foreign key constraint
            //     $table->dropColumn('subclient_id');    // remove the column
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Reverse 'type' enum (⚠️ replace with old enum if needed)
            $table->enum('type', [
                'In Person',
                'VRI',
                'OPI',
            ])->change();

            // Restore subclient_id column
            $table->unsignedBigInteger('subclient_id')->nullable();

            // Restore foreign key (assuming it was pointing to 'subclients' table)
            $table->foreign('subclient_id')->references('id')->on('subclients')->onDelete('set null');
        });
    }
};
