<?php

namespace Tests\Feature\Api;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\InstanceAnswerRevision;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class InstanceSyncTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewForm $form;

    private InterviewSection $section;

    private InterviewItem $taxon;

    private User $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->form = InterviewForm::factory()->create([
            'project_id' => $this->project->id,
            'is_active' => true,
        ]);
        $this->section = InterviewSection::factory()->create([
            'interview_form_id' => $this->form->id,
            'repeatable' => true,
        ]);
        $this->taxon = InterviewItem::factory()->create([
            'interview_section_id' => $this->section->id,
            'link_to_species' => true,
            'type' => 'text',
        ]);

        $this->recorder = User::factory()->create();
        $this->giveAccess($this->recorder, $this->project, 'record_data');
    }

    private function actingAsRecorder(): void
    {
        Sanctum::actingAs($this->recorder, ['capture']);
    }

    private function syncUrl(): string
    {
        return "/api/v1/projects/{$this->project->id}/instances:sync";
    }

    private function answerPayload(string $clientId, string $value, ?string $editedAt = null): array
    {
        return [
            'client_id' => $clientId,
            'interview_section_id' => $this->section->id,
            'interview_item_id' => $this->taxon->id,
            'repeatable_index' => 0,
            'value' => $value,
            'edited_at' => $editedAt ?? now()->toIso8601String(),
        ];
    }

    private function instancePayload(string $id, array $answers, array $overrides = []): array
    {
        return array_merge([
            'id' => $id,
            'interview_form_id' => $this->form->id,
            'captured_at' => now()->toIso8601String(),
            'answers' => $answers,
        ], $overrides);
    }

    public function test_creates_an_instance_and_encrypts_answers_at_rest(): void
    {
        $this->actingAsRecorder();

        $instanceId = (string) Str::uuid();
        $answerClientId = (string) Str::uuid();

        $response = $this->postJson($this->syncUrl(), [
            'instances' => [
                $this->instancePayload($instanceId, [
                    $this->answerPayload($answerClientId, 'guaba'),
                ], [
                    'location' => ['lat' => -12.05, 'lng' => -77.04, 'accuracy_m' => 8.5],
                ]),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('results.0.id', $instanceId)
            ->assertJsonPath('results.0.status', 'created');

        $instance = InterviewInstance::find($instanceId);
        $this->assertNotNull($instance);
        $this->assertSame($this->recorder->id, $instance->user_id);
        $this->assertEqualsWithDelta(-12.05, $instance->location_lat, 0.0001);

        $answer = InstanceAnswer::where('client_id', $answerClientId)->first();
        $this->assertNotNull($answer);
        $this->assertSame('guaba', $answer->answer);
        $this->assertNull($answer->catalog_species_id, 'linking is a web-side task');

        // The value is encrypted at rest, not stored as plaintext.
        $raw = DB::table('instance_answers')->where('client_id', $answerClientId)->value('answer');
        $this->assertNotSame('guaba', $raw);
    }

    public function test_resyncing_the_same_batch_is_a_no_op(): void
    {
        $this->actingAsRecorder();

        $instanceId = (string) Str::uuid();
        $answerClientId = (string) Str::uuid();
        $editedAt = now()->subMinutes(5)->toIso8601String();

        $batch = ['instances' => [
            $this->instancePayload($instanceId, [
                $this->answerPayload($answerClientId, 'guaba', $editedAt),
            ]),
        ]];

        $this->postJson($this->syncUrl(), $batch)->assertJsonPath('results.0.status', 'created');
        $this->postJson($this->syncUrl(), $batch)->assertJsonPath('results.0.status', 'unchanged');

        $this->assertSame(1, InstanceAnswer::where('client_id', $answerClientId)->count());
        $this->assertSame(0, InstanceAnswerRevision::count());
    }

    public function test_a_newer_edit_wins_and_snapshots_the_prior_value(): void
    {
        $this->actingAsRecorder();

        $instanceId = (string) Str::uuid();
        $answerClientId = (string) Str::uuid();

        $this->postJson($this->syncUrl(), ['instances' => [
            $this->instancePayload($instanceId, [
                $this->answerPayload($answerClientId, 'guaba', now()->subMinutes(10)->toIso8601String()),
            ]),
        ]])->assertOk();

        // Simulate a web-side species link that must survive the re-sync.
        $species = CatalogSpecies::factory()->create(['project_id' => $this->project->id]);
        $answer = InstanceAnswer::where('client_id', $answerClientId)->first();
        $answer->catalog_species_id = $species->id;
        $answer->save();

        $response = $this->postJson($this->syncUrl(), ['instances' => [
            $this->instancePayload($instanceId, [
                $this->answerPayload($answerClientId, 'guaba colorada', now()->subMinutes(1)->toIso8601String()),
            ]),
        ]]);

        $response->assertJsonPath('results.0.status', 'updated');

        $answer->refresh();
        $this->assertSame('guaba colorada', $answer->answer);
        $this->assertSame($species->id, $answer->catalog_species_id, 'linking preserved');

        $revision = InstanceAnswerRevision::where('instance_answer_id', $answer->id)->first();
        $this->assertNotNull($revision);
        $this->assertSame('guaba', $revision->answer);
    }

    public function test_an_older_edit_is_ignored(): void
    {
        $this->actingAsRecorder();

        $instanceId = (string) Str::uuid();
        $answerClientId = (string) Str::uuid();

        $this->postJson($this->syncUrl(), ['instances' => [
            $this->instancePayload($instanceId, [
                $this->answerPayload($answerClientId, 'current', now()->subMinutes(1)->toIso8601String()),
            ]),
        ]])->assertOk();

        $response = $this->postJson($this->syncUrl(), ['instances' => [
            $this->instancePayload($instanceId, [
                $this->answerPayload($answerClientId, 'stale', now()->subMinutes(30)->toIso8601String()),
            ]),
        ]]);

        $response->assertJsonPath('results.0.status', 'unchanged');
        $this->assertSame('current', InstanceAnswer::where('client_id', $answerClientId)->first()->answer);
        $this->assertSame(0, InstanceAnswerRevision::count());
    }

    public function test_an_answer_for_a_missing_item_is_rejected_but_the_instance_is_kept(): void
    {
        $this->actingAsRecorder();

        $instanceId = (string) Str::uuid();

        $response = $this->postJson($this->syncUrl(), ['instances' => [
            $this->instancePayload($instanceId, [[
                'client_id' => (string) Str::uuid(),
                'interview_section_id' => $this->section->id,
                'interview_item_id' => 999999,
                'repeatable_index' => 0,
                'value' => 'orphan',
            ]]),
        ]]);

        $response->assertOk()
            ->assertJsonPath('results.0.status', 'created')
            ->assertJsonPath('results.0.errors.answers.0.error', 'api.sync.item_not_in_form');

        $this->assertNotNull(InterviewInstance::find($instanceId));
    }

    public function test_a_non_owner_cannot_update_an_instance(): void
    {
        $this->actingAsRecorder();

        // Another recorder already owns this instance.
        $other = User::factory()->create();
        $this->giveAccess($other, $this->project, 'record_data');
        $instanceId = (string) Str::uuid();
        $owned = new InterviewInstance;
        $owned->id = $instanceId; // id is not mass-assignable
        $owned->interview_form_id = $this->form->id;
        $owned->user_id = $other->id;
        $owned->save();

        $response = $this->postJson($this->syncUrl(), ['instances' => [
            $this->instancePayload($instanceId, [
                $this->answerPayload((string) Str::uuid(), 'guaba'),
            ]),
        ]]);

        $response->assertJsonPath('results.0.status', 'rejected')
            ->assertJsonPath('results.0.errors.id.0', 'api.sync.not_owner');
    }

    public function test_an_instance_for_another_projects_form_is_rejected(): void
    {
        $this->actingAsRecorder();

        $otherForm = InterviewForm::factory()->create();

        $response = $this->postJson($this->syncUrl(), ['instances' => [
            [
                'id' => (string) Str::uuid(),
                'interview_form_id' => $otherForm->id,
                'answers' => [],
            ],
        ]]);

        $response->assertJsonPath('results.0.status', 'rejected')
            ->assertJsonPath('results.0.errors.interview_form_id.0', 'api.sync.form_not_in_project');
    }

    public function test_idempotency_key_dedupes_the_whole_batch(): void
    {
        $this->actingAsRecorder();

        $firstInstance = (string) Str::uuid();
        $this->postJson($this->syncUrl(), ['instances' => [
            $this->instancePayload($firstInstance, [$this->answerPayload((string) Str::uuid(), 'guaba')]),
        ]], ['Idempotency-Key' => 'batch-123'])->assertOk();

        // A different batch with the same key returns the cached result and does
        // not process the new instance.
        $secondInstance = (string) Str::uuid();
        $response = $this->postJson($this->syncUrl(), ['instances' => [
            $this->instancePayload($secondInstance, [$this->answerPayload((string) Str::uuid(), 'other')]),
        ]], ['Idempotency-Key' => 'batch-123']);

        $response->assertOk()->assertJsonPath('results.0.id', $firstInstance);
        $this->assertNull(InterviewInstance::find($secondInstance));
    }

    public function test_timestamps_round_trip_under_a_non_utc_app_timezone(): void
    {
        // The instant a device sends must survive storage even when the app
        // timezone isn't UTC, or last-writer-wins compares shifted times.
        $original = date_default_timezone_get();
        config(['app.timezone' => 'America/Guatemala']); // UTC-6
        date_default_timezone_set('America/Guatemala');

        try {
            $this->actingAsRecorder();
            $instanceId = (string) Str::uuid();
            $clientId = (string) Str::uuid();

            $this->postJson($this->syncUrl(), ['instances' => [
                $this->instancePayload($instanceId, [
                    $this->answerPayload($clientId, 'first', '2026-07-12T15:00:00Z'),
                ], ['captured_at' => '2026-07-12T15:00:00Z']),
            ]])->assertOk();

            // captured_at is the same instant that was sent, not shifted by the offset.
            $instance = InterviewInstance::find($instanceId);
            $this->assertTrue(
                $instance->captured_at->equalTo(Carbon::parse('2026-07-12T15:00:00Z')),
                'captured_at instant must be preserved'
            );

            // A later UTC instant still wins even though its wall-clock is earlier.
            $this->postJson($this->syncUrl(), ['instances' => [
                $this->instancePayload($instanceId, [
                    $this->answerPayload($clientId, 'later', '2026-07-12T18:00:00Z'),
                ]),
            ]])->assertJsonPath('results.0.status', 'updated');

            $this->assertSame('later', InstanceAnswer::where('client_id', $clientId)->first()->answer);
        } finally {
            date_default_timezone_set($original);
        }
    }

    public function test_member_without_record_data_is_forbidden(): void
    {
        $user = User::factory()->create();
        $this->giveAccess($user, $this->project, 'record_data', false);
        Sanctum::actingAs($user, ['capture']);

        $this->postJson($this->syncUrl(), ['instances' => [
            $this->instancePayload((string) Str::uuid(), []),
        ]])->assertStatus(403);
    }

    public function test_requires_authentication(): void
    {
        $this->postJson($this->syncUrl(), ['instances' => []])
            ->assertStatus(401);
    }
}
