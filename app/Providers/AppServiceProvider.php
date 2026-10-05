<?php

namespace App\Providers;

use App\Services\ActiveProject;
use App\Services\Media\FilesystemStoredObjectInspector;
use App\Services\Media\MultipartUploads;
use App\Services\Media\S3MultipartUploads;
use App\Services\Media\S3UploadUrlFactory;
use App\Services\Media\StoredObjectInspector;
use App\Services\Media\UploadUrlFactory;
use App\Services\Transcription\NullTranscriber;
use App\Services\Transcription\Transcriber;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Companion media: presigned uploads, resumable multipart uploads
        // (ADR 0012) and stored-object inspection via S3, plus a transcriber
        // that is a no-op until Whisper is provisioned (ADR 0005). All are
        // swappable in tests.
        $this->app->bind(UploadUrlFactory::class, S3UploadUrlFactory::class);
        $this->app->bind(MultipartUploads::class, S3MultipartUploads::class);
        $this->app->bind(StoredObjectInspector::class, FilesystemStoredObjectInspector::class);
        $this->app->bind(Transcriber::class, NullTranscriber::class);

        // One per request: the middleware that remembers the project and the
        // shared props that draw the sidebar read the same accesses.
        $this->app->scoped(ActiveProject::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
