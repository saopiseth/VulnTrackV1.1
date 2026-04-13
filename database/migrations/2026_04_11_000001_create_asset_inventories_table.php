<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->string('hostname')->nullable();
            $table->enum('identified_scope', ['PCI', 'DMZ', 'Internal', 'External', 'Third-Party'])->default('Internal');
            $table->enum('environment', ['PROD', 'UAT', 'STAGE'])->default('PROD');
            $table->string('system_name')->nullable();
            $table->unsignedTinyInteger('classification_level')->default(3); // 1–5
            $table->enum('critical_level', [
                'Mission-Critical',
                'Business-Critical',
                'Business Operational',
                'Administrative',
                'None-Bank',
            ])->default('Business Operational');
            $table->string('os')->nullable();
            $table->string('open_ports')->nullable();         // e.g. "22,80,443"
            $table->unsignedInteger('vuln_critical')->default(0);
            $table->unsignedInteger('vuln_high')->default(0);
            $table->unsignedInteger('vuln_medium')->default(0);
            $table->unsignedInteger('vuln_low')->default(0);
            $table->string('tags')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Decommissioned'])->default('Active');
            $table->timestamp('last_scanned_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('ip_address');
            $table->index('identified_scope');
            $table->index('environment');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_inventories');
    }
};
