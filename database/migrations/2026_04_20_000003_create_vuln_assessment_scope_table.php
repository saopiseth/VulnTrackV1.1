<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vuln_assessment_scope', function (Blueprint $table) {
            $table->foreignId('vuln_assessment_id')->constrained('vuln_assessments')->cascadeOnDelete();
            $table->foreignId('assessment_scope_id')->constrained('assessment_scopes')->cascadeOnDelete();
            $table->primary(['vuln_assessment_id', 'assessment_scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vuln_assessment_scope');
    }
};
