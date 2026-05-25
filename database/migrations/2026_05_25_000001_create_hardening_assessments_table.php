<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardening_assessments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('name');
            $table->string('system_name');
            $table->string('hostname')->nullable();
            $table->string('ip_address');
            $table->string('operating_system')->nullable();
            $table->enum('environment', ['Production', 'UAT', 'Development', 'Internal', 'DMZ', 'Cloud'])->default('Production');
            $table->string('scope_type')->nullable();
            $table->string('asset_owner')->nullable();
            $table->string('system_owner')->nullable();
            $table->enum('criticality_level', ['Critical', 'High', 'Medium', 'Low'])->default('Medium');
            $table->date('assessment_date');
            $table->text('remarks')->nullable();
            $table->string('nessus_file_path')->nullable();
            $table->string('nessus_file_name')->nullable();
            $table->unsignedBigInteger('nessus_file_size')->nullable();
            $table->enum('upload_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('upload_error')->nullable();
            $table->unsignedInteger('total_findings')->default(0);
            $table->unsignedInteger('compliant_count')->default(0);
            $table->unsignedInteger('non_compliant_count')->default(0);
            $table->unsignedInteger('partially_compliant_count')->default(0);
            $table->unsignedInteger('not_applicable_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('upload_status');
            $table->index('assessment_date');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardening_assessments');
    }
};
