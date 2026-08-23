<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The two columns a device-authored field record needs, mirroring exactly what
 * `instance_answers` was given when the companion first learned to sync
 * (2026_07_12_100000). See docs/decisions/0011-companion-field-records.md.
 *
 *  - client_id: a device-minted uuid, the idempotency key for sync upserts. A
 *    record created offline has no server id to be keyed on, and re-sending a
 *    batch after a lost acknowledgement must not create it twice. Nullable
 *    because the web creates records too and has no device to mint one —
 *    unique, so two devices cannot claim the same key.
 *  - edited_at: the device's own edit time, which is what last-writer-wins
 *    compares. The server's `updated_at` cannot stand in for it: it moves when
 *    the web adds a determination, which is not an edit to anything the device
 *    authored, and would make every later device push look stale.
 *
 * Only the recorded stage is device-authored. Determination, accession number
 * and repository are set on the web and are never written by a sync, so the two
 * sides never write the same fields and this needs no merge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_records', function (Blueprint $table) {
            $table->uuid('client_id')->nullable()->unique()->after('id');
            $table->timestamp('edited_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('field_records', function (Blueprint $table) {
            $table->dropUnique(['client_id']);
            $table->dropColumn(['client_id', 'edited_at']);
        });
    }
};
