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
        Schema::table('users', function (Blueprint $table) {
            \DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','client','staff_agent','staff_interpreter','staff_remote_operator','main_account', 'vendor', 'vendor_interpreter')");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            \DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','client','staff_agent','staff_interpreter','staff_remote_operator','main_account', 'vendor')");
        });
    }
};
