<?php

namespace App\Http\Controllers;

use App\Models\FieldRecord;
use App\Models\Media;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Photographs and audio attached to a field record, from the web.
 *
 * Deliberately not the companion's flow. A device on a bad connection uploads
 * straight to object storage through a presigned URL and then tells the server
 * it finished (docs/contracts/companion-api.md); a browser sitting in front of
 * the researcher can simply post the file, and a two-step handshake would buy
 * nothing but a way to leave rows stranded in `pending`.
 *
 * For a record of something never collected, this is the whole of the evidence.
 * See docs/decisions/0010-field-records-and-basis.md.
 */
class FieldRecordMediaController extends Controller
{
    /** Photographs and audio only — nothing here renders anything else. */
    private const ACCEPTED = [
        'image/jpeg' => Media::KIND_PHOTO,
        'image/png' => Media::KIND_PHOTO,
        'image/webp' => Media::KIND_PHOTO,
        'audio/mpeg' => Media::KIND_AUDIO,
        'audio/mp4' => Media::KIND_AUDIO,
        'audio/aac' => Media::KIND_AUDIO,
        'audio/ogg' => Media::KIND_AUDIO,
        'audio/wav' => Media::KIND_AUDIO,
        'audio/x-wav' => Media::KIND_AUDIO,
    ];

    private const MAX_KILOBYTES = 25600;

    public function store(
        Request $request,
        Project $project,
        FieldRecord $fieldRecord
    ): RedirectResponse {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        if ($fieldRecord->project_id !== $project->id) {
            return $this->notFound();
        }

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.self::MAX_KILOBYTES,
                'mimetypes:'.implode(',', array_keys(self::ACCEPTED)),
            ],
            'caption' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $contentType = $file->getMimeType();

        $disk = config('filesystems.default');
        $key = sprintf(
            'projects/%d/field-records/%d/%s.%s',
            $project->id,
            $fieldRecord->id,
            Str::uuid(),
            $file->extension() ?: 'bin'
        );

        // Private by default: a photograph taken on someone's land, or audio of
        // them speaking, is not public because it happens to be a file.
        Storage::disk($disk)->put($key, $file->get(), 'private');

        $media = new Media([
            'field_record_id' => $fieldRecord->id,
            'client_id' => (string) Str::uuid(),
            'kind' => self::ACCEPTED[$contentType],
            'storage_disk' => $disk,
            'storage_key' => $key,
            'content_type' => $contentType,
            'byte_size' => $file->getSize(),
            // Posted, not handshaked: the bytes are already here.
            'status' => Media::STATUS_STORED,
            'captured_at' => now(),
        ]);
        $media->save();

        return back()
            ->with('message', 'catalogs.fieldRecords.media.added')
            ->with('message_type', 'success');
    }

    /**
     * Stream the bytes to someone allowed to see them.
     *
     * Not a public URL and not a signed one: the check is the same capability
     * that shows the record, evaluated per request, so revoking access to a
     * project revokes access to its photographs at the same moment.
     */
    public function show(
        Project $project,
        FieldRecord $fieldRecord,
        Media $medium
    ): StreamedResponse|RedirectResponse {
        if (! Auth::user()->can('viewCatalog', $project)) {
            return $this->noAccess();
        }

        if ($fieldRecord->project_id !== $project->id
            || $medium->field_record_id !== $fieldRecord->id) {
            return $this->notFound();
        }

        $disk = Storage::disk($medium->storage_disk);

        abort_unless($disk->exists($medium->storage_key), 404);

        return $disk->response(
            $medium->storage_key,
            null,
            ['Content-Type' => $medium->content_type]
        );
    }

    public function destroy(
        Project $project,
        FieldRecord $fieldRecord,
        Media $medium
    ): RedirectResponse {
        if ($denied = $this->denyUnlessEditable($project)) {
            return $denied;
        }

        if ($fieldRecord->project_id !== $project->id
            || $medium->field_record_id !== $fieldRecord->id) {
            return $this->notFound();
        }

        // The bytes go with the row. Nothing else references them, and leaving
        // orphaned objects in storage is how a bucket becomes a liability.
        Storage::disk($medium->storage_disk)->delete($medium->storage_key);
        $medium->delete();

        return back()
            ->with('message', 'catalogs.fieldRecords.media.removed')
            ->with('message_type', 'success');
    }

    private function denyUnlessEditable(Project $project): ?RedirectResponse
    {
        return Auth::user()->can('editCatalog', $project) ? null : $this->noAccess();
    }

    private function noAccess(): RedirectResponse
    {
        return redirect()
            ->route('catalogs.index')
            ->with('message', 'catalogs.no_access')
            ->with('message_type', 'error');
    }

    private function notFound(): RedirectResponse
    {
        return redirect()
            ->route('catalogs.index')
            ->with('message', 'catalogs.fieldRecords.not_found')
            ->with('message_type', 'error');
    }
}
