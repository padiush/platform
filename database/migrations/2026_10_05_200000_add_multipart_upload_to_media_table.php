<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A large file can arrive as an S3 multipart upload, so that a dropped
 * connection costs one part rather than the whole recording
 * (docs/decisions/0012-resumable-media-upload.md).
 *
 * The server, not the device, is the record of that upload: the row keeps the
 * upload id storage issued and the part size the file was split by, and the
 * parts themselves are listed from storage whenever they are asked about. Both
 * are cleared once the object is assembled, or once the upload is found gone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('upload_id', 1024)->nullable()->after('byte_size');
            $table->unsignedInteger('upload_part_size')->nullable()->after('upload_id');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['upload_id', 'upload_part_size']);
        });
    }
};
