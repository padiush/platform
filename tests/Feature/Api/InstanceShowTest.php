<?php

namespace Tests\Feature\Api;

use App\Models\InstanceMedia;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class InstanceShowTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewInstance $instance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $form = InterviewForm::factory()->create(['project_id' => $this->project->id]);

        $recorder = User::factory()->create();
        $this->giveAccess($recorder, $this->project, 'record_data');

        $this->instance = new InterviewInstance;
        $this->instance->id = (string) Str::uuid();
        $this->instance->interview_form_id = $form->id;
        $this->instance->user_id = $recorder->id;
        $this->instance->location_lat = -12.05;
        $this->instance->location_lng = -77.04;
        $this->instance->location_accuracy_m = 8.0;
        $this->instance->save();

        InstanceMedia::create([
            'interview_instance_id' => $this->instance->id,
            'client_id' => (string) Str::uuid(),
            'kind' => 'audio',
            'storage_disk' => 's3',
            'storage_key' => 'projects/1/audio.m4a',
            'content_type' => 'audio/mp4',
            'status' => 'stored',
            'transcription_status' => 'done',
            'transcription_text' => 'la guaba se usa para la fiebre',
        ]);
        InstanceMedia::create([
            'interview_instance_id' => $this->instance->id,
            'client_id' => (string) Str::uuid(),
            'kind' => 'photo',
            'storage_disk' => 's3',
            'storage_key' => 'projects/1/photo.jpg',
            'content_type' => 'image/jpeg',
            'status' => 'stored',
        ]);

        Sanctum::actingAs($recorder, ['capture']);
    }

    public function test_returns_instance_detail_with_media_and_transcription(): void
    {
        $response = $this->getJson("/api/v1/instances/{$this->instance->id}");

        $response->assertOk()
            ->assertJsonPath('id', $this->instance->id)
            ->assertJsonPath('location.lat', -12.05)
            ->assertJsonCount(2, 'media');

        $audio = collect($response->json('media'))->firstWhere('kind', 'audio');
        $photo = collect($response->json('media'))->firstWhere('kind', 'photo');

        $this->assertSame('done', $audio['transcription']['status']);
        $this->assertSame('la guaba se usa para la fiebre', $audio['transcription']['text']);
        $this->assertNull($photo['transcription']);
    }

    public function test_transcription_text_is_withheld_until_done(): void
    {
        InstanceMedia::where('interview_instance_id', $this->instance->id)
            ->where('kind', 'audio')
            ->update(['transcription_status' => 'processing']);

        $response = $this->getJson("/api/v1/instances/{$this->instance->id}");

        $audio = collect($response->json('media'))->firstWhere('kind', 'audio');
        $this->assertSame('processing', $audio['transcription']['status']);
        $this->assertNull($audio['transcription']['text']);
    }

    public function test_outsider_is_forbidden(): void
    {
        $outsider = User::factory()->create();
        Sanctum::actingAs($outsider, ['capture']);

        $this->getJson("/api/v1/instances/{$this->instance->id}")
            ->assertStatus(403);
    }
}
