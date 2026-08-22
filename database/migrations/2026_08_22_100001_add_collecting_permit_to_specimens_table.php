<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ties a collection to the authorisation it was taken under, or records that
 * none was needed.
 *
 * An absent permit is ambiguous in a way an absent voucher is not: it can mean
 * "not recorded" or it can mean "not required" — material from private land
 * with the owner's agreement, cultivated plants, specimens bought in a market.
 * Keeping the two apart is what lets coverage read "38 under a permit, 3
 * exempt, 0 unrecorded" instead of flagging three lawful collections as gaps.
 *
 * A specimen carries a permit, or an exemption, or neither. Never both — that
 * pairing has no meaning, and it is enforced at the validation layer rather
 * than with a check constraint the two supported databases express differently.
 * See docs/decisions/0009-collecting-permits.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specimens', function (Blueprint $table) {
            $table->unsignedBigInteger('collecting_permit_id')->nullable()->after('repository');
            // 'private_land' | 'cultivated' | 'market' | 'other'; the specimen's
            // notes carry the detail. A claim by the researcher, like a
            // determination is.
            $table->string('permit_exemption')->nullable()->after('collecting_permit_id');

            // Deleting a permit record must not delete the physical collections
            // taken under it, the same way deleting a taxon does not.
            $table->foreign('collecting_permit_id')
                ->references('id')
                ->on('collecting_permits')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('specimens', function (Blueprint $table) {
            $table->dropForeign(['collecting_permit_id']);
            $table->dropColumn(['collecting_permit_id', 'permit_exemption']);
        });
    }
};
