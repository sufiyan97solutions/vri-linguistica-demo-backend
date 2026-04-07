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
            $table->string('billing_email')->nullable();
            $table->string('billing_person')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_zipcode')->nullable();
            $table->boolean('est_duration')->default(1)->change();

            $table->unsignedBigInteger('client_template_id')->nullable();
            $table->foreign('client_template_id')->references('id')->on('client_templates');
            $table->string('normal_hour_start_time')->nullable();
            $table->string('normal_hour_end_time')->nullable();
            $table->string('after_hour_start_time')->nullable();
            $table->string('after_hour_end_time')->nullable();
            $table->integer('grace_period')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('credentials_send')->default(0);
            $table->boolean('notifications')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subclient_types', function (Blueprint $table) {
            $table->dropColumn([
                'billing_email',
                'billing_person',
                'billing_state',
                'billing_city',
                'billing_zipcode',
                'normal_hour_start_time',
                'normal_hour_end_time',
                'after_hour_start_time',
                'after_hour_end_time',
                'grace_period'
            ]);

            $table->dropForeign(['client_template_id']);
            $table->dropColumn('client_template_id');

            $table->boolean('est_duration')->default(0)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['credentials_send', 'notifications']);
        });
    }
};
