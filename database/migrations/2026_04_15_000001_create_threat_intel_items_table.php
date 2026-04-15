<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('threat_intel_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type')->default('CVE');            // CVE|Advisory|IOC|Exploit|Campaign
            $table->string('cve_id', 30)->nullable()->index();
            $table->decimal('cvss_score', 4, 1)->nullable();  // 0.0 – 10.0
            $table->string('severity')->default('Medium');    // Critical|High|Medium|Low|Info
            $table->text('description')->nullable();
            $table->text('affected_products')->nullable();
            $table->string('source', 100)->nullable();
            $table->string('source_url')->nullable();
            $table->date('published_at')->nullable();
            $table->string('status')->default('Active');      // Active|Monitoring|Mitigated|Archived
            $table->json('tags')->nullable();
            $table->string('ioc_type', 20)->nullable();       // IP|Domain|Hash|URL
            $table->string('ioc_value', 512)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('threat_intel_items');
    }
};
