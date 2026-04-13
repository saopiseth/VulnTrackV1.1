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
        Schema::create('vuln_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('vuln_assessments')->cascadeOnDelete();
            $table->string('filename');
            $table->boolean('is_baseline')->default(false);
            $table->unsignedInteger('finding_count')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vuln_scans');
    }
};
