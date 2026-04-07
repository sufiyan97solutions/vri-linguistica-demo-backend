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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_user_id');
            $table->foreign('payment_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('appt_id')->nullable();
            $table->string('payment')->nullable();
            $table->enum('status',['pending','paid'])->default('pending');
            $table->enum('user_type',['interpreter','vendor'])->default('vendor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payment_user_id']); // Drop the foreign key constraint
        });
    
        Schema::dropIfExists('payments');
    }
    
};
