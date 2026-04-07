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
            // Foreign keys drop properly
            $table->dropForeign(['billing_state_id']);
            $table->dropForeign(['billing_city_id']);
            $table->dropForeign(['client_template_id']);

            // Drop the columns
            $table->dropColumn([
                'est_duration',
                'billing_email',
                'billing_person',
                'billing_state_id',
                'billing_city_id',
                'billing_zipcode',
                'billing_phone',
                'invoicing_period',
                'client_template_id',
                'billing_state',
                'billing_city',
            ]);
        });
        Schema::dropIfExists('subclient_types_dynamic_fields');
        Schema::dropIfExists('subclient_type_filters');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subclient_types', function (Blueprint $table) {
            $table->integer('est_duration')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_person')->nullable();
            $table->unsignedBigInteger('billing_state_id')->nullable();
            $table->unsignedBigInteger('billing_city_id')->nullable();
            $table->unsignedBigInteger('client_template_id')->nullable();
            $table->string('billing_zipcode')->nullable();
            $table->string('billing_phone')->nullable();
            $table->string('invoicing_period')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_city')->nullable();

            $table->foreign('billing_state_id')->references('id')->on('states')->onDelete('set null');
            $table->foreign('billing_city_id')->references('id')->on('cities')->onDelete('set null');
            $table->foreign('client_template_id')->references('id')->on('client_templates')->onDelete('set null');
        });

        // Recreate deleted tables (with placeholder columns)
        Schema::create('subclient_types_dynamic_fields', function (Blueprint $table) {
            $table->id();
            $table->string('field_name')->nullable();
            $table->string('field_value')->nullable();
            $table->timestamps();
        });

        Schema::create('subclient_type_filters', function (Blueprint $table) {
            $table->id();
            $table->string('field_name')->nullable();
            $table->string('field_value')->nullable();
            $table->timestamps();
        });

    }
};
