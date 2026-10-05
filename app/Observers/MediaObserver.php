<?php

namespace App\Observers;

use App\Models\Media;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * A media row and its bytes go together. Nothing else references the stored
 * object, and one left behind is both a cost and a recording or photograph
 * that outlives what its owner deleted.
 *
 * The object is deleted only once the deletion has committed, so a rolled
 * back transaction never leaves a row whose bytes are gone. A storage failure
 * does not undo the deletion: it is logged, and `media:prune-orphans` finds
 * the object later. An unfinished multipart upload is not an object yet; the
 * daily `media:abort-stale-uploads` aborts it once no row names it.
 */
class MediaObserver implements ShouldHandleEventsAfterCommit
{
    public function deleted(Media $media): void
    {
        try {
            Storage::disk($media->storage_disk)->delete($media->storage_key);
        } catch (Throwable $e) {
            Log::warning('Could not delete a deleted media object from storage.', [
                'media_id' => $media->id,
                'disk' => $media->storage_disk,
                'key' => $media->storage_key,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
