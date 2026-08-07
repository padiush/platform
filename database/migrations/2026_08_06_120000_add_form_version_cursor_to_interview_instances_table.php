<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The bundle cursor a captured interview was recorded against — the structure
 * the device was holding at the time.
 *
 * The companion has always sent this and the sync has always validated it, but
 * nothing stored it, so it was discarded on arrival. It is what makes a
 * skew rejection explicable after the fact: an answer refused because its item
 * no longer exists is otherwise indistinguishable from a malformed one, and the
 * cursor says whether the device was recording against a structure that has
 * since changed. Null for anything recorded on the web.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_instances', function (Blueprint $table) {
            $table->timestamp('form_version_cursor')->nullable()->after('location_captured_at');
        });
    }

    public function down(): void
    {
        Schema::table('interview_instances', function (Blueprint $table) {
            $table->dropColumn('form_version_cursor');
        });
    }
};
