<?php

namespace Tests\Unit;

use App\Models\Media;
use App\Services\Media\MediaStream;
use Aws\CommandInterface;
use Aws\Result;
use Aws\S3\S3Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as FlysystemS3;
use League\Flysystem\Filesystem;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

/**
 * A long recording should not be downloaded from storage up to the point being
 * sought, so object storage is asked for only the bytes in the range.
 */
class MediaStreamTest extends TestCase
{
    /** @var list<CommandInterface> */
    private array $commands = [];

    protected function setUp(): void
    {
        parent::setUp();

        $client = new S3Client([
            'region' => 'us-east-1',
            'version' => 'latest',
            'credentials' => ['key' => 'k', 'secret' => 's'],
            'handler' => function (CommandInterface $command, RequestInterface $request) {
                $this->commands[] = $command;

                return Create::promiseFor(new Result([
                    'ContentLength' => 10,
                    'Body' => Utils::streamFor($command->hasParam('Range') ? 'cdef' : 'abcdefghij'),
                ]));
            },
        ]);

        $adapter = new FlysystemS3($client, 'the-bucket');

        Storage::set('s3', new AwsS3V3Adapter(new Filesystem($adapter), $adapter, ['bucket' => 'the-bucket'], $client));
    }

    private function respond(?string $range)
    {
        $request = Request::create('/media', 'GET', server: $range === null ? [] : ['HTTP_RANGE' => $range]);
        $medium = new Media([
            'storage_disk' => 's3',
            'storage_key' => 'projects/1/audio.m4a',
            'content_type' => 'audio/mp4',
        ]);

        return app(MediaStream::class)->respond($request, $medium);
    }

    private function getObject(): CommandInterface
    {
        return collect($this->commands)->firstOrFail(fn ($command) => $command->getName() === 'GetObject');
    }

    public function test_it_asks_storage_for_only_the_range(): void
    {
        $response = $this->respond('bytes=2-5');

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('bytes=2-5', $this->getObject()['Range']);
        $this->assertSame('projects/1/audio.m4a', $this->getObject()['Key']);
        $this->assertSame('the-bucket', $this->getObject()['Bucket']);

        ob_start();
        $response->sendContent();
        $this->assertSame('cdef', ob_get_clean());
    }

    public function test_it_reads_a_whole_file_without_a_range(): void
    {
        $response = $this->respond(null);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($this->getObject()->hasParam('Range'));

        ob_start();
        $response->sendContent();
        $this->assertSame('abcdefghij', ob_get_clean());
    }
}
