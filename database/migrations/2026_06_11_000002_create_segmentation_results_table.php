<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segmentation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segmentation_test_id')->constrained('segmentation_tests')->cascadeOnDelete();
            $table->string('target_subnet', 50);
            $table->enum('status', ['accessible', 'not_accessible'])->default('not_accessible');
            $table->unsignedSmallInteger('host_count')->default(0);
            $table->timestamps();

            $table->index(['segmentation_test_id', 'status']);
            $table->index('target_subnet');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segmentation_results');
    }
};
