<?php

namespace Tests\Feature;

use App\Models\FieldRecord;
use App\Models\InstanceAnswer;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\Project;
use App\Models\User;
use App\Services\ExampleStudy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** The example project: a private copy of the invented demo study. */
class ExampleProjectTest extends TestCase
{
    use RefreshDatabase;

    /** @return Collection<int, string> every answer, in the order given */
    private function answers(Project $project)
    {
        return InstanceAnswer::whereIn(
            'interview_instance_id',
            InterviewInstance::whereIn('interview_form_id', $project->interviewForms()->pluck('id'))->pluck('id')
        )->orderBy('id')->get()->pluck('answer');
    }

    private function example(User $user): ?Project
    {
        return Project::where('user_id', $user->id)->where('is_example', true)->first();
    }

    public function test_a_user_can_open_an_example_project_they_administer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.example.store'));

        $project = $this->example($user);
        $this->assertNotNull($project);
        $response->assertRedirect(route('projects.overview', ['project' => $project->id]));
        $this->assertTrue($user->fresh()->hasCapabilityOnProject($project, 'manage_project'));
        $this->assertSame($project->id, $user->fresh()->last_project_id);
    }

    /** Everything a section shows, so no page of the tour is empty. */
    public function test_the_example_holds_a_whole_study(): void
    {
        $user = User::factory()->create();

        $project = app(ExampleStudy::class)->build($user);

        $this->assertSame(1, $project->interviewForms()->count());
        $this->assertSame(24, InterviewInstance::whereIn('interview_form_id', $project->interviewForms()->pluck('id'))->count());
        $this->assertSame(14, $project->catalogSpecies()->count());
        $this->assertSame(7, FieldRecord::where('project_id', $project->id)->count());
        $this->assertSame(1, $project->collectingPermits()->count());
        $this->assertSame($user->name, $project->author);
    }

    /** The same seed every time: every copy shows the same figures. */
    public function test_every_copy_is_the_same_study(): void
    {
        $answers = function (Project $project) {
            return InstanceAnswer::whereIn(
                'interview_instance_id',
                InterviewInstance::whereIn('interview_form_id', $project->interviewForms()->pluck('id'))->pluck('id')
            )->count();
        };

        $first = app(ExampleStudy::class)->build(User::factory()->create());
        $second = app(ExampleStudy::class)->build(User::factory()->create());

        $this->assertSame($answers($first), $answers($second));
        $this->assertGreaterThan(24, $answers($first));
    }

    /** Built in the language its owner is using, not only titled in it. */
    public function test_the_example_is_built_in_its_owners_language(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)->post(route('projects.example.store'));

        $project = $this->example($user);
        $form = $project->interviewForms()->first();
        $category = InterviewItem::where('is_use_category', true)
            ->whereIn('interview_section_id', $form->sections()->pluck('id'))
            ->first();

        $this->assertSame('Example: useful plants of the highlands', $project->name);
        $this->assertSame('Ethnobotanical interview (demonstration)', $form->name);
        $this->assertSame(['Medicinal', 'Food', 'Construction', 'Fuel', 'Ritual', 'Handicraft'], $category->options);
        $this->assertContains('Food', $this->answers($project)->all());
        $this->assertNotContains('Alimenticio', $this->answers($project)->all());
        $this->assertSame('Highland coffee farm, El Rosario canton', FieldRecord::where('project_id', $project->id)->orderBy('id')->value('locality'));
    }

    /** Only the words change: every language tells the same study. */
    public function test_every_language_tells_the_same_study(): void
    {
        $study = app(ExampleStudy::class);
        $built = [];
        foreach (['es', 'en', 'pt'] as $locale) {
            $built[$locale] = $this->answers($study->build(User::factory()->create(), $locale));
        }

        foreach (['es', 'en', 'pt'] as $locale) {
            $this->assertSame($built['es']->count(), $built[$locale]->count());

            // Each answer is the same answer, said in another language.
            $words = trans('example_study', [], $locale);
            $spanish = trans('example_study', [], 'es');
            $toSpanish = [];
            foreach (['categories', 'parts', 'preparations', 'species'] as $group) {
                foreach ($words[$group] as $key => $word) {
                    $toSpanish[$word] = $spanish[$group][$key];
                }
            }
            $this->assertSame(
                $built['es']->all(),
                $built[$locale]->map(fn (string $answer) => $toSpanish[$answer] ?? $answer)->all(),
            );
        }
    }

    public function test_its_words_exist_in_every_language(): void
    {
        $keys = function (array $words, string $prefix = '') use (&$keys): array {
            $flat = [];
            foreach ($words as $key => $value) {
                $flat = is_array($value)
                    ? [...$flat, ...$keys($value, "{$prefix}{$key}.")]
                    : [...$flat, "{$prefix}{$key}"];
            }

            return $flat;
        };

        $spanish = $keys(trans('example_study', [], 'es'));
        $this->assertCount(14, trans('example_study.species', [], 'es'));

        foreach (['en', 'pt'] as $locale) {
            $this->assertSame($spanish, $keys(trans('example_study', [], $locale)), $locale);
        }
    }

    public function test_asking_again_opens_the_same_example(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('projects.example.store'));
        $this->actingAs($user)->post(route('projects.example.store'));

        $this->assertSame(1, Project::where('user_id', $user->id)->where('is_example', true)->count());
    }

    public function test_each_user_gets_their_own(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice)->post(route('projects.example.store'));
        $this->actingAs($bob)->post(route('projects.example.store'));

        $this->assertNotEquals($this->example($alice)->id, $this->example($bob)->id);
        $this->assertFalse($bob->fresh()->hasAccessToProject($this->example($alice)));
    }

    public function test_removing_it_takes_only_the_example(): void
    {
        $user = User::factory()->create();
        $real = Project::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->post(route('projects.example.store'));
        $exampleId = $this->example($user)->id;

        $this->actingAs($user)
            ->delete(route('projects.example.destroy'))
            ->assertRedirect(route('projects.index'));

        $this->assertNull($this->example($user));
        $this->assertNotNull($real->fresh());
        // Nothing of it is left behind, the catalog included.
        $this->assertDatabaseMissing('catalog_species', ['project_id' => $exampleId]);
        $this->assertDatabaseMissing('field_records', ['project_id' => $exampleId]);
        $this->assertDatabaseMissing('interview_forms', ['project_id' => $exampleId]);
        $this->assertSame(0, InterviewItem::count());
    }

    /** Copies of invented data are not work. */
    public function test_the_system_count_leaves_examples_out(): void
    {
        $admin = User::factory()->create(['system_admin' => true]);
        Project::factory()->create();
        $this->actingAs(User::factory()->create())->post(route('projects.example.store'));

        $this->actingAs($admin)->get(route('system.index'))
            ->assertInertia(fn (Assert $page) => $page->where('counts.projects', 1));
    }

    public function test_the_sidebar_marks_an_example(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('projects.example.store'));

        $this->actingAs($user)->get(route('projects.overview', $this->example($user)->id))
            ->assertInertia(fn (Assert $page) => $page
                ->where('projectNav.active.is_example', true)
                ->where('projectNav.projects.0.is_example', true));
    }

    public function test_guests_cannot_make_one(): void
    {
        $this->post(route('projects.example.store'))->assertRedirect(route('login'));
        $this->delete(route('projects.example.destroy'))->assertRedirect(route('login'));
    }
}
