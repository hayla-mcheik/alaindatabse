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
        Schema::create('analytics_records', function (Blueprint $table) {
        $table->id();
        $table->string('client')->nullable();
        $table->string('agency')->nullable();
        $table->string('budget')->nullable();
        $table->string('platform')->nullable();
        $table->string('country')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_records');
    }
};
