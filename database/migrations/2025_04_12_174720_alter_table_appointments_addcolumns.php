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
            // end_time column drop
            $table->dropColumn('end_time');

            // rename estimated_duration to duration
            $table->renameColumn('estimated_duration', 'duration');

            // add vendor_id with foreign key to users table
            $table->unsignedBigInteger('vendor_id')->nullable()->after('duration');
            $table->foreign('vendor_id')->references('id')->on('users')->onDelete('set null');

            // Update status enum
            $table->enum('status', [
                'open',
                'pending',
                'assigned',
                'active',
                'completed',
                'cancelled',
                'cnc',
                'dnc',
                'declined',
                'active_escalation',
                'expire_escalation',
                'vendor_cancelled',
                'vendor_declined'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // rollback vendor_id foreign key + column
            $table->dropForeign(['vendor_id']);
            $table->dropColumn('vendor_id');

            // rename duration back to estimated_duration
            $table->renameColumn('duration', 'estimated_duration');

            // add end_time back
            $table->time('end_time')->nullable();
        });
    }
};
