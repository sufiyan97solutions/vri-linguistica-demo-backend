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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('transid',20)->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('subclient_types')->onDelete('set null');            
            $table->unsignedBigInteger('source_language_id')->nullable();
            $table->foreign('source_language_id')->references('id')->on('languages')->onDelete('set null');
            $table->string('requester_name',150)->nullable();
            $table->enum('status',['New Request','Quote Approved','Accepted','Pending','Invoice Sent','Rejected/Decline','Client Request Editing','Quote Sent','Translation Decline'])->default('New Request');
            $table->unsignedBigInteger('interpreter_id')->nullable();
            $table->foreign('interpreter_id')->references('id')->on('interpreters')->onDelete('set null');
            $table->integer('total_words_count')->default(0);
            $table->integer('est_words_count')->default(0);
            $table->double('total_amount')->default(0);
            $table->double('est_amount')->default(0);
            $table->timestamps();
        });
        
        Schema::create('translation_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('translation_id')->nullable();
            $table->foreign('translation_id')->references('id')->on('translations')->onDelete('cascade');
            $table->date('due_date')->nullable();
            $table->string('requester_phone',20)->nullable();
            $table->string('requester_email',200)->nullable();
            $table->text('comment')->nullable();         
            $table->boolean('formatting')->default(0);
            $table->boolean('rush')->default(0);
            $table->string('client_request_editing_reason')->nullable();
            $table->string('translation_decline_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('translation_target_languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('translation_id')->nullable();
            $table->foreign('translation_id')->references('id')->on('translations')->onDelete('cascade');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->foreign('language_id')->references('id')->on('languages')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('translation_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('translation_id')->nullable();
            $table->foreign('translation_id')->references('id')->on('translations')->onDelete('cascade');
            $table->string('original_file',100);
            $table->string('translated_file',100);
            $table->enum('file_status',['Pending','In Progress','Under Review','Completed'])->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
