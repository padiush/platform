<?php

namespace Tests\Feature\Api;

use App\Jobs\TranscribeAudio;
use App\Models\InstanceMedia;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Project;
use App\Models\User;
use App\Services\Transcription\Transcriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TranscribeAudioTest extends TestCase
{
    use RefreshDatabase;

    private function audioMedia(): InstanceMedia
    {
        $project = Project::factory()->create();
        $form = InterviewForm::factory()->create(['project_id' => $project->id]);
        $user = User::factory()->create();

        $instance = new InterviewInstance;
        $instance->id = (string) Str::uuid();
        $instance->interview_form_id = $form->id;
        $instance->user_id = $user->id;
        $instance->save();

        return InstanceMedia::create([
            'interview_instance_id' => $instance->id,
            'client_id' => (string) Str::uuid(),
            'kind' => 'audio',
            'storage_disk' => 's3',
            'storage_key' => 'projects/1/audio.m4a',
            'content_type' => 'audio/mp4',
            'status' => 'stored',
            'transcription_status' => 'queued',
        ]);
    }

    public function test_a_configured_transcriber_writes_the_transcript(): void
    {
        $this->app->instance(Transcriber::class, new class implements Transcriber
        {
            public function transcribe(InstanceMedia $media): string
            {
                return 'transcribed text';
            }
        });

        $media = $this->audioMedia();

        TranscribeAudio::dispatchSync($media);

        $media->refresh();
        $this->assertSame('done', $media->transcription_status);
        $this->assertSame('transcribed text', $media->transcription_text);
    }

    public function test_the_default_null_transcriber_marks_it_failed(): void
    {
        // No binding override — the default NullTranscriber throws.
        $media = $this->audioMedia();

        TranscribeAudio::dispatchSync($media);

        $media->refresh();
        $this->assertSame('failed', $media->transcription_status);
        $this->assertNull($media->transcription_text);
    }
}
