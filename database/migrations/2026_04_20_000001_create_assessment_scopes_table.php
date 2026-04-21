<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->nullable();
            $table->string('hostname', 255)->nullable();
            $table->string('system_name', 255)->nullable();
            $table->unsignedTinyInteger('system_criticality')->nullable()->comment('1=Mission-Critical 2=Business-Critical 3=Business Operational 4=Administrative 5=None-Bank');
            $table->string('system_owner', 100)->nullable();
            $table->string('identified_scope', 50)->nullable()->comment('PCI|DMZ|Internal');
            $table->string('environment', 20)->nullable()->comment('PROD|UAT|STAGE');
            $table->string('location', 50)->nullable()->comment('DC|DR|Cloud');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_scopes');
    }
};
