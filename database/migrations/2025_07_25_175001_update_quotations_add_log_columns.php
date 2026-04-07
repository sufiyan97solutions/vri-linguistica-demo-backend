<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('quotations', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('pdf_path');
            }
            if (!Schema::hasColumn('quotations', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('quotations', 'status')) {
                $table->string('status')->default('initial')->after('updated_by');
            }
            if (!Schema::hasColumn('quotations', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
        });
    }
    public function down() {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('quotations', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
            if (Schema::hasColumn('quotations', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('quotations', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
