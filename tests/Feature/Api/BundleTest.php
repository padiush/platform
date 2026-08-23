<?php

namespace Tests\Feature\Api;

use App\Models\CollectingPermit;
use App\Models\InterviewForm;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

class BundleTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewForm $activeForm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();

        $this->activeForm = InterviewForm::factory()->create([
            'project_id' => $this->project->id,
            'is_active' => true,
        ]);
        $section = InterviewSection::factory()->create([
            'interview_form_id' => $this->activeForm->id,
            'repeatable' => true,
            'order' => 1,
        ]);
        InterviewItem::factory()->create([
            'interview_section_id' => $section->id,
            'link_to_species' => true,
            'type' => 'text',
            'order' => 1,
        ]);

        // An inactive form must never appear in the bundle.
        InterviewForm::factory()->create([
            'project_id' => $this->project->id,
            'is_active' => false,
        ]);
    }

    private function actingAsRecorder(): User
    {
        $user = User::factory()->create();
        $this->giveAccess($user, $this->project, 'record_data');
        Sanctum::actingAs($user, ['capture']);

        return $user;
    }

    public function test_returns_active_forms_with_full_structure(): void
    {
        $this->actingAsRecorder();

        $response = $this->getJson("/api/v1/projects/{$this->project->id}/bundle");

        $response->assertOk()
            ->assertJsonStructure([
                'form_version_cursor',
                'server_time',
                'forms' => [[
                    'id', 'name', 'description', 'is_active', 'updated_at',
                    'sections' => [[
                        'id', 'name', 'order', 'repeatable',
                        'items' => [[
                            'id', 'label', 'name', 'type', 'required', 'options',
                            'link_to_species', 'is_use_category', 'min', 'max', 'step', 'order',
                        ]],
                    ]],
                ]],
            ])
            ->assertJsonCount(1, 'forms')
            ->assertJsonPath('forms.0.id', $this->activeForm->id)
            ->assertJsonPath('forms.0.sections.0.items.0.link_to_species', true);
    }

    public function test_since_in_the_future_returns_no_forms_but_keeps_the_cursor(): void
    {
        $this->actingAsRecorder();

        $since = now()->addDay()->toIso8601String();

        $response = $this->getJson(
            "/api/v1/projects/{$this->project->id}/bundle?since=".urlencode($since)
        );

        $response->assertOk()
            ->assertJsonCount(0, 'forms')
            ->assertJsonPath('form_version_cursor', fn ($cursor) => $cursor !== null);
    }

    public function test_since_in_the_past_returns_the_form(): void
    {
        $this->actingAsRecorder();

        $since = now()->subDay()->toIso8601String();

        $this->getJson("/api/v1/projects/{$this->project->id}/bundle?since=".urlencode($since))
            ->assertOk()
            ->assertJsonCount(1, 'forms');
    }

    /**
     * `forms` is a delta once `since` is given, and a delta cannot express a
     * removal: a deactivated form simply stops appearing, which a device cannot
     * tell apart from one that has not changed. Without the full active set, a
     * form retired on the web keeps accepting interviews on every device that
     * already cached it.
     */
    public function test_lists_every_active_form_id_alongside_the_delta(): void
    {
        $this->actingAsRecorder();

        $other = InterviewForm::factory()->create([
            'project_id' => $this->project->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/projects/{$this->project->id}/bundle");

        $response->assertOk()
            ->assertJsonCount(2, 'active_form_ids');
        $this->assertEqualsCanonicalizing(
            [$this->activeForm->id, $other->id],
            $response->json('active_form_ids')
        );
    }

    public function test_the_active_set_is_complete_even_when_the_delta_is_empty(): void
    {
        $this->actingAsRecorder();

        // Nothing changed since the cursor, so no form is sent…
        $response = $this->getJson(
            "/api/v1/projects/{$this->project->id}/bundle?since=".urlencode(now()->addDay()->toIso8601String())
        );

        $response->assertOk()->assertJsonCount(0, 'forms');
        // …but the device still learns which forms remain active.
        $this->assertSame([$this->activeForm->id], $response->json('active_form_ids'));
    }

    public function test_a_deactivated_form_leaves_the_active_set(): void
    {
        $this->actingAsRecorder();

        $this->activeForm->update(['is_active' => false]);

        $response = $this->getJson("/api/v1/projects/{$this->project->id}/bundle");

        $response->assertOk();
        $this->assertSame([], $response->json('active_form_ids'));
    }

    public function test_it_carries_the_projects_collecting_permits(): void
    {
        $this->actingAsRecorder();

        $permit = CollectingPermit::factory()->create([
            'project_id' => $this->project->id,
            'authority' => 'MARN',
            'reference' => 'RES-021-2026',
        ]);
        // Another study's permit must not travel with this bundle.
        CollectingPermit::factory()->create(['authority' => 'CONAP']);

        $response = $this->getJson("/api/v1/projects/{$this->project->id}/bundle");

        $response->assertOk()
            ->assertJsonCount(1, 'collecting_permits')
            ->assertJsonPath('collecting_permits.0.id', $permit->id)
            ->assertJsonPath('collecting_permits.0.authority', 'MARN')
            ->assertJsonPath('collecting_permits.0.reference', 'RES-021-2026')
            ->assertJsonPath('collecting_permits.0.issued_on', '2026-01-15');
    }

    /**
     * The permits are the full set every time. A delta cannot express a
     * removal, and a device holding a revoked permit would go on offering it —
     * the same trap `active_form_ids` exists to avoid.
     */
    public function test_permits_are_sent_in_full_even_when_the_form_delta_is_empty(): void
    {
        $this->actingAsRecorder();

        CollectingPermit::factory()->create(['project_id' => $this->project->id]);

        $response = $this->getJson(
            "/api/v1/projects/{$this->project->id}/bundle?since=".now()->addDay()->toIso8601String()
        );

        $response->assertOk()
            ->assertJsonCount(0, 'forms')
            ->assertJsonCount(1, 'collecting_permits');
    }

    public function test_invalid_since_is_rejected(): void
    {
        $this->actingAsRecorder();

        $this->getJson("/api/v1/projects/{$this->project->id}/bundle?since=not-a-date")
            ->assertStatus(422)
            ->assertJsonPath('message', 'api.validation_failed');
    }

    public function test_member_without_record_data_is_forbidden(): void
    {
        $user = User::factory()->create();
        $this->giveAccess($user, $this->project, 'record_data', false);
        Sanctum::actingAs($user, ['capture']);

        $this->getJson("/api/v1/projects/{$this->project->id}/bundle")
            ->assertStatus(403)
            ->assertJsonPath('message', 'api.forbidden');
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/v1/projects/{$this->project->id}/bundle")
            ->assertStatus(401);
    }
}
