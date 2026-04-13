<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_assessments', function (Blueprint $table) {
            $table->id();
            $table->string('assessment_type');
            $table->date('project_kickoff')->nullable();
            $table->date('due_date')->nullable();
            $table->date('complete_date')->nullable();
            $table->string('project_coordinator')->nullable();
            $table->string('assessor')->nullable();
            $table->enum('priority', ['Critical', 'High', 'Medium', 'Low'])->default('Medium');
            $table->string('bcd_id')->nullable();
            $table->boolean('vulnerability_assessment')->default(false);
            $table->boolean('hardening')->default(false);
            $table->boolean('penetration_testing')->default(false);
            $table->boolean('secure_code_review')->default(false);
            $table->enum('status', ['Open', 'In Progress', 'Closed'])->default('Open');
            $table->string('bcd_url')->nullable();
            $table->text('comments')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_assessments');
    }
};
