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
        Schema::create('vuln_remediations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('vuln_assessments')->cascadeOnDelete();
            $table->string('plugin_id');
            $table->string('ip_address');
            $table->enum('status', ['Open', 'In Progress', 'Resolved', 'Accepted Risk'])->default('Open');
            $table->string('assigned_to')->nullable();
            $table->date('due_date')->nullable();
            $table->text('comments')->nullable();
            $table->string('evidence_path')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['assessment_id', 'plugin_id', 'ip_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vuln_remediations');
    }
};
