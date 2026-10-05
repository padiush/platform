<?php

namespace Tests\Unit\Services\Media;

use App\Services\Media\S3MultipartUploads;
use App\Services\Media\UploadGone;
use Aws\Command;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Against the real S3 client with its transport replaced, so the requests are
 * built and signed for real and no storage is touched.
 */
class S3MultipartUploadsTest extends TestCase
{
    private MockHandler $responses;

    protected function setUp(): void
    {
        parent::setUp();

        $this->responses = new MockHandler;
        Storage::disk('s3')->getClient()->getHandlerList()->setHandler($this->responses);
    }

    private function noSuchUpload(): S3Exception
    {
        return new S3Exception('The specified upload does not exist.', new Command('ListParts'), [
            'code' => 'NoSuchUpload',
        ]);
    }

    public function test_a_part_url_is_signed_for_that_upload_and_part(): void
    {
        $signed = (new S3MultipartUploads)->partUrl('s3', 'projects/1/a.m4a', 'up-1', 3, now()->addMinutes(15));

        $this->assertStringContainsString('uploadId=up-1', $signed['url']);
        $this->assertStringContainsString('partNumber=3', $signed['url']);
        $this->assertStringContainsString('X-Amz-Signature=', $signed['url']);
        $this->assertSame([], $signed['headers']);
    }

    public function test_parts_are_listed_by_number(): void
    {
        $this->responses->append(new Result(['Parts' => [
            ['PartNumber' => 2, 'Size' => 4, 'ETag' => '"b"'],
            ['PartNumber' => 1, 'Size' => 8, 'ETag' => '"a"'],
        ]]));

        $this->assertSame(
            [1 => ['size' => 8, 'etag' => '"a"'], 2 => ['size' => 4, 'etag' => '"b"']],
            (new S3MultipartUploads)->parts('s3', 'projects/1/a.m4a', 'up-1')
        );
    }

    public function test_an_upload_storage_does_not_have_is_gone(): void
    {
        $this->responses->append($this->noSuchUpload());

        $this->expectException(UploadGone::class);

        (new S3MultipartUploads)->parts('s3', 'projects/1/a.m4a', 'up-1');
    }

    public function test_completing_sends_the_parts_in_order(): void
    {
        $this->responses->append(new Result([]));

        (new S3MultipartUploads)->complete('s3', 'projects/1/a.m4a', 'up-1', [2 => '"b"', 1 => '"a"']);

        $this->assertSame(
            [['PartNumber' => 1, 'ETag' => '"a"'], ['PartNumber' => 2, 'ETag' => '"b"']],
            $this->responses->getLastCommand()['MultipartUpload']['Parts']
        );
    }

    public function test_aborting_an_upload_already_gone_is_not_an_error(): void
    {
        $this->responses->append($this->noSuchUpload());

        (new S3MultipartUploads)->abort('s3', 'projects/1/a.m4a', 'up-1');

        $this->assertSame('AbortMultipartUpload', $this->responses->getLastCommand()->getName());
    }
}
