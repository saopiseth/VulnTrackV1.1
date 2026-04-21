<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hardware snapshots are append-only — every collection run creates a NEW row.
     * This preserves a full history of asset changes over time.
     * DO NOT store software here; use installed_software instead.
     */
    public function up(): void
    {
        Schema::create('agent_hardware_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')
                  ->constrained('agents')
                  ->cascadeOnDelete();

            // Hardware fields — all nullable so partial reports are accepted
            $table->string('cpu', 255)->nullable()->comment('CPU model string');
            $table->unsignedInteger('ram')->nullable()->comment('Total RAM in MB');
            $table->unsignedInteger('disk')->nullable()->comment('Total disk in GB');
            $table->string('os_version', 255)->nullable()->comment('Detailed OS version string');

            // When the agent collected this data (agent clock, not server clock)
            $table->timestamp('collected_at')->useCurrent();

            $table->timestamps();

            // Fast queries: "latest snapshot for agent X" and "history between dates"
            $table->index(['agent_id', 'collected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_hardware_snapshots');
    }
};
