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
        if (!Schema::hasColumn('project_assessments', 'slug')) {
            Schema::table('project_assessments', function (Blueprint $table) {
                $table->string('slug', 12)->unique()->nullable()->after('id');
            });
        }

        // Backfill existing rows
        \DB::table('project_assessments')->whereNull('slug')->each(function ($row) {
            \DB::table('project_assessments')
                ->where('id', $row->id)
                ->update(['slug' => \Illuminate\Support\Str::random(12)]);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('project_assessments', 'slug')) {
            Schema::table('project_assessments', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
