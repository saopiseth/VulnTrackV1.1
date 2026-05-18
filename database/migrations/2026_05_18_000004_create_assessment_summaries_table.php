<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_summaries', function (Blueprint $table) {
            $table->unsignedBigInteger('assessment_id')->primary();
            $table->foreign('assessment_id')
                  ->references('id')->on('vuln_assessments')
                  ->cascadeOnDelete();

            $table->unsignedInteger('active_total')->default(0);
            $table->unsignedInteger('resolved_total')->default(0);
            $table->unsignedInteger('critical')->default(0);
            $table->unsignedInteger('high')->default(0);
            $table->unsignedInteger('medium')->default(0);
            $table->unsignedInteger('low')->default(0);
            $table->unsignedInteger('host_count')->default(0);

            // JSON array of top-20 host objects (ip, severity counts, scope metadata)
            $table->json('top_hosts_json')->nullable();

            // No created_at — only updated_at tracks when the cache was last built
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_summaries');
    }
};
