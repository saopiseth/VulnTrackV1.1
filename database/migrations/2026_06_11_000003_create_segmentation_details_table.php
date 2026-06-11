<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segmentation_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segmentation_test_id')->constrained('segmentation_tests')->cascadeOnDelete();
            $table->foreignId('segmentation_result_id')->constrained('segmentation_results')->cascadeOnDelete();
            $table->string('host_ip', 45);
            $table->string('target_subnet', 50);
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('protocol', 10)->nullable();
            $table->string('service', 100)->nullable();
            $table->timestamps();

            $table->index(['segmentation_test_id', 'target_subnet']);
            $table->index(['segmentation_test_id', 'host_ip']);
            $table->index('segmentation_result_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segmentation_details');
    }
};
