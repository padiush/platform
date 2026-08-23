<?php

namespace Tests\Feature\Api;

use App\Models\CollectingPermit;
use App\Models\FieldRecord;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * POST /api/v1/projects/{project}/records:sync — the device authors the
 * recorded stage of a field record and nothing beyond it
 * (docs/decisions/0011-companion-field-records.md).
 */
class FieldRecordSyncTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private User $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->recorder = User::factory()->create();
        $this->giveAccess($this->recorder, $this->project, 'record_data');
    }

    private function actingAsRecorder(): void
    {
        Sanctum::actingAs($this->recorder, ['capture']);
    }

    private function syncUrl(?Project $project = null): string
    {
        return '/api/v1/projects/'.($project ?? $this->project)->id.'/records:sync';
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function recordPayload(string $clientId, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $clientId,
            'basis_of_record' => FieldRecord::BASIS_OBSERVATION,
            'vernacular_name' => 'guaba',
            'collection_number' => 'RA-014',
            'collector' => 'R. Arévalo',
            'collected_on' => '2026-08-20',
            'locality' => 'cafetal above the school',
            'location' => ['lat' => 13.7, 'lng' => -89.2],
            'notes' => 'flowering',
        ], $overrides);
    }

    public function test_it_creates_a_record_and_encrypts_the_vernacular_name(): void
    {
        $this->actingAsRecorder();

        $clientId = (string) Str::uuid();

        $response = $this->postJson($this->syncUrl(), [
            'records' => [$this->recordPayload($clientId)],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'created');
        $response->assertJsonPath('results.0.client_id', $clientId);

        $record = FieldRecord::where('client_id', $clientId)->firstOrFail();
        $this->assertSame($this->project->id, $record->project_id);
        $this->assertSame(FieldRecord::BASIS_OBSERVATION, $record->basis_of_record);
        $this->assertSame('guaba', $record->vernacular_name);
        $this->assertSame('RA-014', $record->collection_number);
        $this->assertEqualsWithDelta(13.7, $record->location_lat, 0.0001);

        // The server id comes back, because the device has no other way to
        // learn it — unlike an interview, whose id it minted itself.
        $response->assertJsonPath('results.0.id', $record->id);

        // Encrypted at rest, as an interview answer is.
        $raw = DB::table('field_records')->where('client_id', $clientId)->value('vernacular_name');
        $this->assertNotSame('guaba', $raw);
    }

    public function test_resyncing_the_same_batch_is_a_no_op(): void
    {
        $this->actingAsRecorder();

        $clientId = (string) Str::uuid();

        // One payload, posted twice — the pattern that keeps this test off the
        // clock. See the note in InstanceSyncTest.
        $batch = ['records' => [$this->recordPayload($clientId)]];

        $this->postJson($this->syncUrl(), $batch)->assertJsonPath('results.0.status', 'created');
        $this->postJson($this->syncUrl(), $batch)->assertJsonPath('results.0.status', 'unchanged');

        $this->assertSame(1, FieldRecord::where('client_id', $clientId)->count());
    }

    public function test_a_newer_edit_wins(): void
    {
        $this->actingAsRecorder();

        $clientId = (string) Str::uuid();

        $this->postJson($this->syncUrl(), ['records' => [
            $this->recordPayload($clientId, [
                'collection_number' => 'RA-014',
                'edited_at' => now()->subMinutes(30)->toIso8601String(),
            ]),
        ]])->assertOk();

        $this->postJson($this->syncUrl(), ['records' => [
            $this->recordPayload($clientId, [
                'collection_number' => 'RA-015',
                'edited_at' => now()->subMinutes(1)->toIso8601String(),
            ]),
        ]])->assertJsonPath('results.0.status', 'updated');

        $this->assertSame('RA-015', FieldRecord::where('client_id', $clientId)->first()->collection_number);
    }

    public function test_an_older_edit_is_ignored(): void
    {
        $this->actingAsRecorder();

        $clientId = (string) Str::uuid();

        $this->postJson($this->syncUrl(), ['records' => [
            $this->recordPayload($clientId, [
                'collection_number' => 'RA-014',
                'edited_at' => now()->subMinutes(1)->toIso8601String(),
            ]),
        ]])->assertOk();

        $this->postJson($this->syncUrl(), ['records' => [
            $this->recordPayload($clientId, [
                'collection_number' => 'RA-999',
                'edited_at' => now()->subMinutes(30)->toIso8601String(),
            ]),
        ]])->assertJsonPath('results.0.status', 'unchanged');

        $this->assertSame('RA-014', FieldRecord::where('client_id', $clientId)->first()->collection_number);
    }

    public function test_a_future_edit_time_is_clamped_so_a_fast_clock_cannot_win_forever(): void
    {
        $this->actingAsRecorder();

        $clientId = (string) Str::uuid();

        $this->postJson($this->syncUrl(), ['records' => [
            $this->recordPayload($clientId, [
                'edited_at' => now()->addYears(5)->toIso8601String(),
            ]),
        ]])->assertOk();

        $stored = FieldRecord::where('client_id', $clientId)->first();
        $this->assertTrue(
            $stored->edited_at->lessThanOrEqualTo(now()->addMinute()),
            'a device five years fast would otherwise never be corrected'
        );
    }

    public function test_a_permit_from_another_project_is_refused(): void
    {
        $this->actingAsRecorder();

        $foreign = CollectingPermit::factory()->create();

        $response = $this->postJson($this->syncUrl(), ['records' => [
            $this->recordPayload((string) Str::uuid(), [
                'collecting_permit_id' => $foreign->id,
            ]),
        ]]);

        $response->assertJsonPath('results.0.status', 'rejected');
        $response->assertJsonPath('results.0.errors.collecting_permit_id.0', 'api.sync.permit_not_in_project');
        $this->assertSame(0, FieldRecord::count());
    }

    public function test_a_permit_and_an_exemption_together_are_refused(): void
    {
        $this->actingAsRecorder();

        $permit = CollectingPermit::factory()->create(['project_id' => $this->project->id]);

        $this->postJson($this->syncUrl(), ['records' => [
            $this->recordPayload((string) Str::uuid(), [
                'collecting_permit_id' => $permit->id,
                'permit_exemption' => 'cultivated',
            ]),
        ]])->assertStatus(422);

        $this->assertSame(0, FieldRecord::count());
    }

    /**
     * The pairing has no meaning (0009), so a device that switches from one to
     * the other must not leave a record carrying both.
     */
    public function test_switching_to_an_exemption_clears_the_permit(): void
    {
        $this->actingAsRecorder();

        $clientId = (string) Str::uuid();
        $permit = CollectingPermit::factory()->create(['project_id' => $this->project->id]);

        $this->postJson($this->syncUrl(), ['records' => [
            $this->recordPayload($clientId, [
                'collecting_permit_id' => $permit->id,
                'edited_at' => now()->subMinutes(10)->toIso8601String(),
            ]),
        ]])->assertOk();

        $this->postJson($this->syncUrl(), ['records' => [
            $this->recordPayload($clientId, [
                'permit_exemption' => 'cultivated',
                'edited_at' => now()->toIso8601String(),
            ]),
        ]])->assertJsonPath('results.0.status', 'updated');

        $record = FieldRecord::where('client_id', $clientId)->first();
        $this->assertNull($record->collecting_permit_id);
        $this->assertSame('cultivated', $record->permit_exemption);
    }

    public function test_the_device_cannot_deposit_or_identify(): void
    {
        $this->actingAsRecorder();

        foreach ([
            ['accession_number' => 'MML-0001'],
            ['repository' => 'Herbario comunitario'],
            ['determination' => ['catalog_species_id' => 1]],
        ] as $webOnly) {
            $this->postJson($this->syncUrl(), ['records' => [
                $this->recordPayload((string) Str::uuid(), $webOnly),
            ]])->assertStatus(422);
        }

        $this->assertSame(0, FieldRecord::count());
    }

    public function test_a_record_can_come_out_of_an_interview_answer(): void
    {
        $this->actingAsRecorder();

        $answerClientId = (string) Str::uuid();
        $answer = $this->answerInThisProject($answerClientId);

        $clientId = (string) Str::uuid();

        $this->postJson($this->syncUrl(), ['records' => [
            $this->recordPayload($clientId, ['answer_client_id' => $answerClientId]),
        ]])->assertJsonPath('results.0.status', 'created');

        $this->assertSame(
            $answer->id,
            FieldRecord::where('client_id', $clientId)->first()->instance_answer_id
        );
    }

    /**
     * Refused rather than accepted without the link: the interview simply has
     * not been pushed yet, and letting the record through would lose the
     * connection permanently.
     */
    public function test_a_record_naming_an_unknown_answer_is_refused(): void
    {
        $this->actingAsRecorder();

        $response = $this->postJson($this->syncUrl(), ['records' => [
            $this->recordPayload((string) Str::uuid(), [
                'answer_client_id' => (string) Str::uuid(),
            ]),
        ]]);

        $response->assertJsonPath('results.0.status', 'rejected');
        $response->assertJsonPath('results.0.errors.answer_client_id.0', 'api.sync.answer_not_found');
        $this->assertSame(0, FieldRecord::count());
    }

    public function test_an_answer_from_another_project_cannot_be_linked(): void
    {
        $this->actingAsRecorder();

        $answerClientId = (string) Str::uuid();
        $this->answerInProject(Project::factory()->create(), $answerClientId);

        $response = $this->postJson($this->syncUrl(), ['records' => [
            $this->recordPayload((string) Str::uuid(), ['answer_client_id' => $answerClientId]),
        ]]);

        $response->assertJsonPath('results.0.status', 'rejected');
        $response->assertJsonPath('results.0.errors.answer_client_id.0', 'api.sync.answer_not_found');
    }

    public function test_a_client_id_already_used_in_another_project_is_refused(): void
    {
        $other = Project::factory()->create();
        $this->giveAccess($this->recorder, $other, 'record_data');
        $this->actingAsRecorder();

        $clientId = (string) Str::uuid();

        $this->postJson($this->syncUrl($other), [
            'records' => [$this->recordPayload($clientId)],
        ])->assertJsonPath('results.0.status', 'created');

        $response = $this->postJson($this->syncUrl(), [
            'records' => [$this->recordPayload($clientId)],
        ]);

        $response->assertJsonPath('results.0.status', 'rejected');
        $response->assertJsonPath('results.0.errors.client_id.0', 'api.sync.client_id_conflict');
        $this->assertSame(1, FieldRecord::count());
    }

    public function test_the_idempotency_key_dedupes_the_whole_batch(): void
    {
        $this->actingAsRecorder();

        $batch = ['records' => [$this->recordPayload((string) Str::uuid())]];

        $this->postJson($this->syncUrl(), $batch, ['Idempotency-Key' => 'abc'])
            ->assertJsonPath('results.0.status', 'created');

        // The replay returns the first answer verbatim rather than re-applying.
        $this->postJson($this->syncUrl(), $batch, ['Idempotency-Key' => 'abc'])
            ->assertJsonPath('results.0.status', 'created');

        $this->assertSame(1, FieldRecord::count());
    }

    public function test_a_member_without_record_data_is_forbidden(): void
    {
        $outsider = $this->userWithCapability($this->project, 'record_data', false);
        Sanctum::actingAs($outsider, ['capture']);

        $this->postJson($this->syncUrl(), [
            'records' => [$this->recordPayload((string) Str::uuid())],
        ])->assertStatus(403);
    }

    public function test_it_requires_authentication(): void
    {
        $this->postJson($this->syncUrl(), [
            'records' => [$this->recordPayload((string) Str::uuid())],
        ])->assertStatus(401);
    }

    private function answerInThisProject(string $clientId): InstanceAnswer
    {
        return $this->answerInProject($this->project, $clientId);
    }

    private function answerInProject(Project $project, string $clientId): InstanceAnswer
    {
        $form = InterviewForm::factory()->create(['project_id' => $project->id]);
        $section = InterviewSection::factory()->create(['interview_form_id' => $form->id]);
        $item = InterviewItem::factory()->create(['interview_section_id' => $section->id]);
        $instance = InterviewInstance::factory()->create([
            'interview_form_id' => $form->id,
            'user_id' => $this->recorder->id,
        ]);

        return InstanceAnswer::factory()->create([
            'client_id' => $clientId,
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $section->id,
            'interview_item_id' => $item->id,
        ]);
    }
}
