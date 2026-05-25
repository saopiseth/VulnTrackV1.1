<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardening_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('hardening_assessment_id')->constrained()->cascadeOnDelete();
            $table->date('verification_date');
            $table->string('verified_by')->nullable();
            $table->text('remarks')->nullable();
            $table->string('nessus_file_path')->nullable();
            $table->string('nessus_file_name')->nullable();
            $table->unsignedBigInteger('nessus_file_size')->nullable();
            $table->enum('upload_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('upload_error')->nullable();
            $table->unsignedInteger('resolved_count')->default(0);
            $table->unsignedInteger('still_open_count')->default(0);
            $table->unsignedInteger('new_finding_count')->default(0);
            $table->unsignedInteger('not_found_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['hardening_assessment_id', 'upload_status']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardening_verifications');
    }
};
