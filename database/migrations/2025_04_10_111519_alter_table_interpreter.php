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
        Schema::table('interpreters', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('address')->nullable();
            $table->dropColumn('email');
            $table->dropColumn('code');
            $table->dropColumn('secondary_number');
            $table->dropColumn('secondary_email');
            $table->dropColumn('notes');
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interpreters', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropColumn('address');
            $table->string('email')->nullable();
            $table->string('code')->nullable();
            $table->string('secondary_number')->nullable();
            $table->string('secondary_email')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->nullable();
        });
    }
};
