<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only event log — no updated_at column by design.
     * Useful for auditing, debugging agent connectivity issues, and
     * tracking registration/re-registration events.
     */
    public function up(): void
    {
        Schema::create('agent_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')
                  ->constrained('agents')
                  ->cascadeOnDelete();

            $table->enum('event_type', ['register', 'heartbeat', 'update', 'error'])
                  ->index();

            $table->text('message')->nullable();

            // Intentionally only created_at — logs are immutable
            $table->timestamp('created_at')->useCurrent();

            $table->index(['agent_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_logs');
    }
};
