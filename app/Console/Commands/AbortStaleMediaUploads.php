<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\Media\MultipartUploads;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Abort the multipart uploads nobody is coming back for.
 *
 * Storage keeps an unfinished upload's parts, and bills for them, until it is
 * told to assemble or abort. A device that started a large file and was then
 * reset, signed out or simply never returned leaves one behind, as does a
 * recording deleted mid-upload. This aborts any media upload started more than
 * `--days` ago, and any that no media row names. It also clears the row, so
 * the device's next intent starts a fresh upload instead of being told the old
 * one expired (docs/decisions/0012-resumable-media-upload.md).
 */
class AbortStaleMediaUploads extends Command
{
    protected $signature = 'media:abort-stale-uploads
        {--days=7 : Abort uploads started more than this many days ago}
        {--disk=s3 : The disk device uploads go to}';

    protected $description = 'Abort media multipart uploads that were abandoned, and forget them on their media rows.';

    /** Every device upload is keyed under a project. */
    private const KEY_PREFIX = 'projects/';

    /**
     * How long an upload no row names is left alone: intent starts the upload
     * a moment before it saves the row, and the two must not be told apart as
     * an orphan.
     */
    private const ORPHAN_GRACE_MINUTES = 60;

    public function handle(MultipartUploads $multipart): int
    {
        $disk = (string) $this->option('disk');
        $staleBefore = CarbonImmutable::now()->subDays((int) $this->option('days'));
        $orphanBefore = CarbonImmutable::now()->subMinutes(self::ORPHAN_GRACE_MINUTES);

        $rows = Media::where('storage_disk', $disk)
            ->whereNotNull('upload_id')
            ->get()
            ->keyBy('upload_id');

        $seen = [];
        $aborted = 0;

        foreach ($multipart->incomplete($disk, self::KEY_PREFIX) as $upload) {
            $media = $rows->get($upload['upload_id']);
            $initiated = CarbonImmutable::instance($upload['initiated']);

            if ($media && $media->storage_key !== $upload['key']) {
                $media = null;
            }

            $seen[$upload['upload_id']] = true;

            $abandoned = $media
                ? $initiated->lt($staleBefore)
                : $initiated->lt($orphanBefore);

            if (! $abandoned) {
                continue;
            }

            $multipart->abort($disk, $upload['key'], $upload['upload_id']);
            $aborted++;

            if ($media) {
                $this->forget($media);
            }
        }

        // A row whose upload storage no longer lists — aborted by a lifecycle
        // rule, say — would only send the device a round trip to learn it.
        $forgotten = 0;

        foreach ($rows as $uploadId => $media) {
            if (! isset($seen[$uploadId]) && $media->updated_at->lt($orphanBefore)) {
                $this->forget($media);
                $forgotten++;
            }
        }

        $this->info("Aborted {$aborted} stale upload(s); forgot {$forgotten} upload(s) storage no longer had.");

        return self::SUCCESS;
    }

    private function forget(Media $media): void
    {
        $media->upload_id = null;
        $media->upload_part_size = null;
        $media->save();
    }
}
