<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integrity events reported by the companion app. A handful of failures on a
 * device destroy unsynced captures or leave an unencrypted recording behind,
 * and until now they were written to a console nobody reads — a researcher
 * could lose a day's interviews and no one would ever learn it happened.
 *
 * Deliberately not a log. There is no free-text column and no payload: an
 * event is one of a closed set of codes plus the build it happened on, so
 * nothing an informant said can travel this channel even by mistake. That is
 * the whole reason this exists here rather than at a crash-reporting vendor.
 *
 * `client_id` is the device-minted idempotency key, so a report that is
 * retried after a failed response lands once. See
 * docs/contracts/companion-api.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_diagnostics', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('client_id')->unique();
            $table->string('code');
            $table->timestamp('occurred_at');
            $table->string('app_version')->nullable();
            $table->string('platform')->nullable();
            $table->string('os_version')->nullable();

            // The questions this table exists to answer: is a given code
            // spiking, and is one device or account seeing all of them?
            $table->index(['code', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_diagnostics');
    }
};
