<?php

namespace App\Providers;

use App\Services\Media\S3UploadUrlFactory;
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
        // Companion media: presigned uploads via S3/MinIO, and a transcriber
        // that is a no-op until Whisper is provisioned (ADR 0005). Both are
        // swappable in tests and when a real driver lands.
        $this->app->bind(UploadUrlFactory::class, S3UploadUrlFactory::class);
        $this->app->bind(Transcriber::class, NullTranscriber::class);
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
