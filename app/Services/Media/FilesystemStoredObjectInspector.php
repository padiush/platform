<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Storage;

class FilesystemStoredObjectInspector implements StoredObjectInspector
{
    public function inspect(string $disk, string $key): ?array
    {
        $storage = Storage::disk($disk);

        if (! $storage->exists($key)) {
            return null;
        }

        $contentType = $storage->mimeType($key);

        return [
            'byte_size' => $storage->size($key),
            'content_type' => is_string($contentType) ? $contentType : null,
        ];
    }
}
