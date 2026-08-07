<?php

namespace App\Services\Media;

interface StoredObjectInspector
{
    /**
     * @return array{byte_size:int, content_type:?string}|null
     */
    public function inspect(string $disk, string $key): ?array;
}
