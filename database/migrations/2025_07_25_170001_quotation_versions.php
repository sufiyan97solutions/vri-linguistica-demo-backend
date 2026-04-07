<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('translation_id');
            $table->integer('version')->default(1);
            $table->string('pdf_path');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('status')->default('initial');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('translation_id')->references('id')->on('translations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('quotations');
    }
};
