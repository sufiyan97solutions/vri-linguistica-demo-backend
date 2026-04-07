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
            // Add columns if they do not exist
            if (!Schema::hasColumn('subclient_types', 'billing_email')) {
                $table->string('billing_email')->nullable();
            }
            if (!Schema::hasColumn('subclient_types', 'billing_person')) {
                $table->string('billing_person')->nullable();
            }
            if (!Schema::hasColumn('subclient_types', 'billing_state')) {
                $table->string('billing_state')->nullable();
            }
            if (!Schema::hasColumn('subclient_types', 'billing_city')) {
                $table->string('billing_city')->nullable();
            }
            if (!Schema::hasColumn('subclient_types', 'billing_zipcode')) {
                $table->string('billing_zipcode')->nullable();
            }
            if (!Schema::hasColumn('subclient_types', 'est_duration')) {
                $table->boolean('est_duration')->default(1)->change();
            }
            if (!Schema::hasColumn('subclient_types', 'normal_hour_start_time')) {
                $table->string('normal_hour_start_time')->nullable();
            }
            if (!Schema::hasColumn('subclient_types', 'normal_hour_end_time')) {
                $table->string('normal_hour_end_time')->nullable();
            }
            if (!Schema::hasColumn('subclient_types', 'after_hour_start_time')) {
                $table->string('after_hour_start_time')->nullable();
            }
            if (!Schema::hasColumn('subclient_types', 'after_hour_end_time')) {
                $table->string('after_hour_end_time')->nullable();
            }
            if (!Schema::hasColumn('subclient_types', 'grace_period')) {
                $table->integer('grace_period')->nullable();
            }
            if (!Schema::hasColumn('subclient_types', 'client_template_id')) {
                $table->unsignedBigInteger('client_template_id');
                $table->foreign('client_template_id')->references('id')->on('client_templates');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
