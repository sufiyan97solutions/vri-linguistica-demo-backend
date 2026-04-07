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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('duration_unit')->nullable()->after('min_duration');
            $table->string('incremental')->nullable()->after('duration_unit');
            $table->decimal('rush_fee', 8, 2)->nullable()->after('total_amount');
            $table->integer('extra_duration')->nullable()->after('min_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down() {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['duration_unit', 'incremental', 'rush_fee', 'extra_duration']);
        });
    }
};
