<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\SystemRun;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Find stored media objects that no media row names, and delete them on
 * request.
 *
 * Until deleting an interview, a field record, a form, a project or an
 * account deleted its stored files, those files stayed behind in storage:
 * paid for, and holding recordings and photographs their owners had deleted.
 * This lists what is left, with how much space it takes, and with `--delete`
 * removes it. It only reports by default, because what it deletes cannot be
 * recovered.
 *
 * Objects written in the last day are left alone: a device's intent saves the
 * row and the upload that follows writes the object, and a web upload writes
 * the object a moment before its row.
 */
class PruneOrphanedMedia extends Command
{
    protected $signature = 'media:prune-orphans
        {--disk=s3 : The disk to look in}
        {--delete : Delete the orphaned objects instead of only listing them}';

    protected $description = 'List, and with --delete remove, stored media objects that no media row names.';

    /** Every media object is keyed under a project. */
    private const KEY_PREFIX = 'projects';

    private const GRACE_HOURS = 24;

    public function handle(): int
    {
        $diskName = (string) $this->option('disk');
        $disk = Storage::disk($diskName);
        $delete = (bool) $this->option('delete');
        $recentAfter = CarbonImmutable::now()->subHours(self::GRACE_HOURS)->getTimestamp();

        $known = array_flip(
            Media::where('storage_disk', $diskName)->pluck('storage_key')->all()
        );

        $orphans = 0;
        $bytes = 0;

        foreach ($disk->allFiles(self::KEY_PREFIX) as $key) {
            if (isset($known[$key]) || $disk->lastModified($key) > $recentAfter) {
                continue;
            }

            $orphans++;
            $bytes += $disk->size($key);

            if ($delete) {
                $disk->delete($key);
            } else {
                $this->line($key);
            }
        }

        // The admin panel says when this last looked, and what it found.
        SystemRun::mark(SystemRun::PRUNE_ORPHANS, [
            'found' => $orphans,
            'bytes' => $bytes,
            'deleted' => $delete,
            'disk' => $diskName,
        ]);

        $size = sprintf('%.1f MB', $bytes / 1_048_576);

        $this->info($delete
            ? "Deleted {$orphans} orphaned object(s), {$size}."
            : "Found {$orphans} orphaned object(s), {$size}. Run again with --delete to remove them.");

        return self::SUCCESS;
    }
}
