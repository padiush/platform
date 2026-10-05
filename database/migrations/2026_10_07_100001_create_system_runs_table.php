<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When each maintenance job last ran, and what it found: the scheduler's own
 * heartbeat, and the orphaned-media check. The admin panel reads it to say
 * whether the upkeep the platform relies on is actually happening.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_runs', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->timestamp('ran_at');
            $table->json('summary')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_runs');
    }
};
