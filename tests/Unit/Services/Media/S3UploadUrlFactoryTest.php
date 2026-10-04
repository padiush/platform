<?php

namespace Tests\Unit\Services\Media;

use App\Services\Media\S3UploadUrlFactory;
use Tests\TestCase;

/**
 * Against the real presigner — signing is offline, so no storage is touched.
 * The contract (docs/api/openapi.yaml, MediaIntentResponse) promises string
 * header values, and a device sends them as given.
 */
class S3UploadUrlFactoryTest extends TestCase
{
    public function test_the_headers_are_strings_the_device_can_send(): void
    {
        $presigned = (new S3UploadUrlFactory)->create(
            's3',
            'projects/1/field-records/8/media/photo.jpg',
            'image/jpeg',
            15
        );

        $this->assertSame('image/jpeg', $presigned['headers']['Content-Type']);

        foreach ($presigned['headers'] as $name => $value) {
            $this->assertIsString($value, "header {$name} must be a string");
        }
    }

    /** The client sets Host from the URL, which is what the signature covers. */
    public function test_host_is_left_to_the_client(): void
    {
        $presigned = (new S3UploadUrlFactory)->create('s3', 'k.jpg', 'image/jpeg', 15);

        $this->assertArrayNotHasKey('host', array_change_key_case($presigned['headers']));
        $this->assertStringContainsString('X-Amz-Signature=', $presigned['url']);
    }
}
