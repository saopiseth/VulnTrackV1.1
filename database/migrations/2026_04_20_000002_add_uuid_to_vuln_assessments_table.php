<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_assessments', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Back-fill UUIDs for any existing rows
        DB::table('vuln_assessments')->whereNull('uuid')->orderBy('id')->each(function ($row) {
            DB::table('vuln_assessments')
                ->where('id', $row->id)
                ->update(['uuid' => (string) Str::uuid()]);
        });

        // Make non-nullable now that all rows have a value
        Schema::table('vuln_assessments', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('vuln_assessments', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
