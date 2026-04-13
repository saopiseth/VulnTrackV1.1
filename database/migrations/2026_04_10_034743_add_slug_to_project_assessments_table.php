<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_assessments', function (Blueprint $table) {
            $table->string('slug', 12)->unique()->nullable()->after('id');
        });

        // Backfill existing rows
        \App\Models\ProjectAssessment::whereNull('slug')->each(function ($a) {
            $a->update(['slug' => \Illuminate\Support\Str::random(12)]);
        });
    }

    public function down(): void
    {
        Schema::table('project_assessments', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
