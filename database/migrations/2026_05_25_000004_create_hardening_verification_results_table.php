<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hardening_verification_results')) {
            return;
        }

        Schema::create('hardening_verification_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hardening_verification_id')->constrained()->cascadeOnDelete();
            // Null when verification found a new finding not present in initial assessment
            $table->foreignId('hardening_finding_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plugin_id')->index();
            $table->string('plugin_name');
            $table->string('plugin_family')->nullable();
            $table->text('description')->nullable();
            $table->text('solution')->nullable();
            $table->mediumText('plugin_output')->nullable();
            $table->string('severity')->nullable();
            $table->decimal('cvss_score', 4, 1)->nullable();
            $table->string('compliance_result')->nullable();
            $table->string('compliance_status')->nullable();
            $table->enum('verification_status', [
                'Resolved',
                'Still Open',
                'New Finding',
                'Accepted Risk',
                'Not Found in Verification',
            ])->default('Still Open');
            $table->string('finding_key', 40)->index();
            $table->timestamps();

            $table->index(['hardening_verification_id', 'verification_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardening_verification_results');
    }
};
