<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_records', function (Blueprint $table) {
            $table->string('source_file')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('analytics_records', function (Blueprint $table) {
            $table->dropColumn('source_file');
        });
    }
};