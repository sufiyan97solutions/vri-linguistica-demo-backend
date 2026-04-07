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
            if (!Schema::hasColumn('subclient_types', 'billing_state_id')) {
                $table->unsignedBigInteger('billing_state_id')->after('billing_person');
                $table->foreign('billing_state_id')->references('id')->on('states')->onDelete('cascade');
            }

            if (!Schema::hasColumn('subclient_types', 'billing_city_id')) {
                $table->unsignedBigInteger('billing_city_id')->after('billing_state_id');
                $table->foreign('billing_city_id')->references('id')->on('cities')->onDelete('cascade');
            }

            if (Schema::hasColumn('subclient_types', 'billing_state')) {
                $table->dropColumn('billing_state');
            }

            if (Schema::hasColumn('subclient_types', 'billing_city')) {
                $table->dropColumn('billing_city');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subclient_types', function (Blueprint $table) {
            if (Schema::hasColumn('subclient_types', 'billing_state_id')) {
                $table->dropForeign(['billing_state_id']);
                $table->dropColumn('billing_state_id');
            }

            if (Schema::hasColumn('subclient_types', 'billing_city_id')) {
                $table->dropForeign(['billing_city_id']);
                $table->dropColumn('billing_city_id');
            }

            $table->string('billing_state')->nullable();
            $table->string('billing_city')->nullable();
        });
    }
};
