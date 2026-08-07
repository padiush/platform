<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offline capture needs two things on an answer the web never did:
 *  - client_id: a device-minted uuid, the idempotency key for sync upserts
 *    (the server keeps its own integer PK). See docs/contracts/sync-protocol.md.
 *  - edited_at: the device-supplied edit time, the key for the post-sync
 *    last-writer-wins conflict policy (docs/decisions/0004-offline-sync-model.md).
 *
 * Both are nullable: answers recorded on the web carry neither at rest until
 * they are edited (the sync layer stamps edited_at when it applies a write).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instance_answers', function (Blueprint $table) {
            $table->uuid('client_id')->nullable()->unique()->after('id');
            $table->timestamp('edited_at')->nullable()->after('answer');
        });
    }

    public function down(): void
    {
        Schema::table('instance_answers', function (Blueprint $table) {
            $table->dropColumn(['client_id', 'edited_at']);
        });
    }
};
