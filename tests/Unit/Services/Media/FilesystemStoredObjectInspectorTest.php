<?php

namespace Tests\Unit\Services\Media;

use App\Services\Media\FilesystemStoredObjectInspector;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilesystemStoredObjectInspectorTest extends TestCase
{
    public function test_it_returns_size_and_content_type_for_a_stored_object(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('projects/1/media.txt', 'field notes');

        $metadata = app(FilesystemStoredObjectInspector::class)->inspect(
            's3',
            'projects/1/media.txt'
        );

        $this->assertSame(11, $metadata['byte_size']);
        $this->assertSame('text/plain', $metadata['content_type']);
    }

    public function test_it_returns_null_when_the_object_is_missing(): void
    {
        Storage::fake('s3');

        $this->assertNull(
            app(FilesystemStoredObjectInspector::class)->inspect(
                's3',
                'projects/1/missing.jpg'
            )
        );
    }
}
