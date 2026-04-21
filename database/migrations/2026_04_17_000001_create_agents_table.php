<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('uuid', 64)->unique()->comment('Unique identifier sent by the agent');
            $table->string('hostname', 255);
            $table->string('ip_address', 45)->comment('IPv4 or IPv6');
            $table->string('os', 255)->nullable()->comment('OS family reported on registration');

            // Lifecycle
            $table->enum('status', ['online', 'offline'])->default('online')->index();
            $table->timestamp('last_seen')->nullable()->index();

            // Auth: stores SHA-256 hash of the raw Bearer token
            $table->string('api_token', 64)->nullable()->unique();

            $table->timestamps();

            $table->index('uuid');
            $table->index('hostname');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
