<?php

namespace Tests\Feature;

use App\Models\CatalogSpecies;
use App\Models\Determination;
use App\Models\FieldRecord;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithProjects;
use Tests\TestCase;

/**
 * A field record made from an interview answer, seen from both sides on the
 * web: the record says which interview and question it came out of, and the
 * interview lists the records made from each answer.
 * See docs/decisions/0011-companion-field-records.md.
 */
class FieldRecordAnswerLinkTest extends TestCase
{
    use InteractsWithProjects, RefreshDatabase;

    private Project $project;

    private InterviewInstance $instance;

    private InstanceAnswer $answer;

    private FieldRecord $fromAnswer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $form = InterviewForm::factory()->create(['project_id' => $this->project->id]);
        $section = InterviewSection::factory()->create([
            'interview_form_id' => $form->id,
            'repeatable' => true,
        ]);
        $item = InterviewItem::factory()->create([
            'interview_section_id' => $section->id,
            'label' => 'Nombre local de la planta',
            'type' => 'text',
            'link_to_species' => true,
        ]);
        $this->instance = InterviewInstance::factory()->create([
            'interview_form_id' => $form->id,
        ]);
        $this->answer = InstanceAnswer::factory()->create([
            'interview_instance_id' => $this->instance->id,
            'interview_section_id' => $section->id,
            'interview_item_id' => $item->id,
            'repeatable_index' => 2,
            'answer' => 'Ruda',
        ]);

        $this->fromAnswer = FieldRecord::factory()->create([
            'project_id' => $this->project->id,
            'collection_number' => 'RA-031',
            'vernacular_name' => 'Ruda',
            'instance_answer_id' => $this->answer->id,
        ]);
        FieldRecord::factory()->create([
            'project_id' => $this->project->id,
            'collection_number' => 'RA-032',
        ]);
    }

    /** Records and interviews both: the technician role. */
    private function recorder(): User
    {
        return $this->userWithCapability($this->project, 'manage_data', false);
    }

    /** Reads the catalog, records nothing: the consultation role. */
    private function catalogReader(): User
    {
        return $this->userWithCapability($this->project, 'record_data', false);
    }

    /** Records interviews without reading the catalog — not a seeded role. */
    private function recorderWithoutCatalog(): User
    {
        $capability = ProjectCapability::factory()->create([
            'record_data' => true,
            'view_catalog' => false,
        ]);
        $user = User::factory()->create();
        ProjectAccess::factory()->create([
            'user_id' => $user->id,
            'project_id' => $this->project->id,
            'project_capability_id' => $capability->id,
        ]);

        return $user;
    }

    private function recordsPage(User $user)
    {
        return $this->actingAs($user)->get(
            route('catalogs.fieldRecords.index', ['project' => $this->project->id])
        );
    }

    private function recordFromAnswer(Assert $page): array
    {
        $rows = collect($page->toArray()['props']['fieldRecords']);

        return $rows->firstWhere('id', $this->fromAnswer->id);
    }

    // ------------------------------------------------------- the record ---

    public function test_a_record_says_which_interview_and_question_it_came_out_of()
    {
        $this->recordsPage($this->recorder())
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $page->component('Catalog/FieldRecords')->where('canOpenInterviews', true);

                $this->assertSame([
                    'instance_id' => $this->instance->id,
                    'question' => 'Nombre local de la planta',
                ], $this->recordFromAnswer($page)['interview']);

                $other = collect($page->toArray()['props']['fieldRecords'])
                    ->firstWhere('collection_number', 'RA-032');
                $this->assertNull($other['interview']);
            });
    }

    /**
     * Someone who reads the catalog but cannot open interviews sees where the
     * record came from, and is not offered a way into the interview.
     */
    public function test_a_catalog_reader_sees_the_origin_but_cannot_follow_it()
    {
        $this->recordsPage($this->catalogReader())
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $page->where('canOpenInterviews', false);

                $this->assertSame(
                    'Nombre local de la planta',
                    $this->recordFromAnswer($page)['interview']['question']
                );
            });
    }

    /** The answer is an informant's response; the record carries no copy of it. */
    public function test_the_answer_itself_is_not_sent_with_the_record()
    {
        $this->recordsPage($this->catalogReader())
            ->assertInertia(function (Assert $page) {
                $this->assertSame(
                    ['instance_id', 'question'],
                    array_keys($this->recordFromAnswer($page)['interview'])
                );
            });
    }

    /** Deleting the answer releases the record rather than deleting it. */
    public function test_a_record_whose_answer_is_gone_stands_on_its_own()
    {
        $this->answer->delete();

        $this->recordsPage($this->recorder())
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $this->assertNull($this->recordFromAnswer($page)['interview']);
            });
    }

    public function test_the_species_page_shows_the_origin_too()
    {
        $species = CatalogSpecies::factory()->create(['project_id' => $this->project->id]);
        Determination::factory()->create([
            'field_record_id' => $this->fromAnswer->id,
            'catalog_species_id' => $species->id,
        ]);

        $this->actingAs($this->recorder())
            ->get(route('catalogs.species.show', [
                'project' => $this->project,
                'species' => $species,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/SpeciesShow')
                ->where('canOpenInterviews', true)
                ->where('fieldRecords.0.interview.question', 'Nombre local de la planta')
            );
    }

    // ---------------------------------------------------- the interview ---

    public function test_an_interview_lists_the_records_made_from_each_answer()
    {
        $this->actingAs($this->recorder())
            ->get(route('interviews.show', ['instance' => $this->instance]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Interviews/Instance')
                ->where('canViewCatalog', true)
                ->has('answers', 1)
                ->where('answers.0.field_records', [[
                    'id' => $this->fromAnswer->id,
                    'accession_number' => null,
                    'collection_number' => 'RA-031',
                    'was_collected' => true,
                ]])
            );
    }

    /** The records are listed either way; only reading the catalog opens them. */
    public function test_a_recorder_who_cannot_read_the_catalog_sees_the_records_unlinked()
    {
        $this->actingAs($this->recorderWithoutCatalog())
            ->get(route('interviews.show', ['instance' => $this->instance]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewCatalog', false)
                ->has('answers.0.field_records', 1)
            );
    }
}
