<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Field capture records when and where an interview happened — data the
 * web-only flow never had. captured_at is the device's capture time (distinct
 * from created_at, which is when the server first stored it); the location_*
 * columns hold the GPS fix the companion app attaches. All nullable: a
 * web-recorded interview carries none of them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_instances', function (Blueprint $table) {
            $table->timestamp('captured_at')->nullable()->after('user_id');
            $table->decimal('location_lat', 10, 7)->nullable()->after('captured_at');
            $table->decimal('location_lng', 10, 7)->nullable()->after('location_lat');
            $table->decimal('location_accuracy_m', 8, 2)->nullable()->after('location_lng');
            $table->timestamp('location_captured_at')->nullable()->after('location_accuracy_m');
        });
    }

    public function down(): void
    {
        Schema::table('interview_instances', function (Blueprint $table) {
            $table->dropColumn([
                'captured_at',
                'location_lat',
                'location_lng',
                'location_accuracy_m',
                'location_captured_at',
            ]);
        });
    }
};
