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
        Schema::create('translation_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('translation_id');
            $table->foreign('translation_id')->references('id')->on('translations')->onDelete('cascade');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('subclient_types')->onDelete('set null');            
            $table->unsignedBigInteger('interpreter_id')->nullable();
            $table->foreign('interpreter_id')->references('id')->on('interpreters')->onDelete('set null');
            $table->string('invoice_number', 100)->unique();
            $table->string('invoice_path', 100)->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('status', 50)->default('unpaid');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_invoices');
    }
};
