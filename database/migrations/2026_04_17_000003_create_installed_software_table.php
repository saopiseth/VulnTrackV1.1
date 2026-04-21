<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores the CURRENT software inventory per agent (sync/replace model).
     *
     * Design contract:
     *   - One row per unique (agent_id, name, version) combination.
     *   - Every sync run removes stale rows and upserts the fresh list.
     *   - collected_at reflects the most recent scan that confirmed this entry.
     *   - installed_at is the install timestamp reported by the OS (may be null).
     *
     * Future extensions:
     *   - Add a `cve_id` FK when vulnerability mapping is implemented.
     *   - Add a `is_vulnerable` boolean flag for quick dashboard queries.
     */
    public function up(): void
    {
        Schema::create('installed_software', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')
                  ->constrained('agents')
                  ->cascadeOnDelete();

            $table->string('name', 500)->comment('Application display name');
            $table->string('version', 100)->comment('Version string as reported by OS');

            // Timestamps reported by / derived from the agent
            $table->timestamp('installed_at')->nullable()->comment('Install date from OS (may be null)');
            $table->timestamp('collected_at')->useCurrent()->comment('Last scan that confirmed this entry');

            $table->timestamps();

            // Enforce one row per software-version per agent
            $table->unique(['agent_id', 'name', 'version'], 'uq_agent_software');

            // Indexes for searching and vulnerability mapping queries
            $table->index('name');
            $table->index(['agent_id', 'collected_at']);
            $table->index('version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installed_software');
    }
};
