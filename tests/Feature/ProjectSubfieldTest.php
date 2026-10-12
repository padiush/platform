<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\ExampleStudy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Every project names its branch of ethnobiology, and until a second one is
 * built, that is ethnobotany. See docs/decisions/0006-multi-subfield-architecture.md.
 */
class ProjectSubfieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_default_subfield_is_a_known_one(): void
    {
        $this->assertContains(Project::SUBFIELD_ETHNOBOTANY, Project::SUBFIELDS);
    }

    public function test_a_project_not_yet_saved_reports_its_subfield(): void
    {
        $this->assertSame(Project::SUBFIELD_ETHNOBOTANY, (new Project)->subfield);
    }

    public function test_a_row_written_without_one_is_ethnobotany(): void
    {
        // What the migration gives every project that existed before it.
        $id = DB::table('projects')->insertGetId([
            'user_id' => User::factory()->create()->id,
            'name' => 'Estudio anterior',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(Project::SUBFIELD_ETHNOBOTANY, Project::find($id)->subfield);
    }

    public function test_a_project_created_on_the_web_is_ethnobotany(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('projects.create'), [
            'name' => 'Plantas medicinales de Morazán',
        ])->assertRedirect(route('projects.index'));

        $this->assertSame(
            Project::SUBFIELD_ETHNOBOTANY,
            Project::where('user_id', $user->id)->sole()->subfield
        );
    }

    public function test_the_request_cannot_choose_the_subfield(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('projects.create'), [
            'name' => 'Aves de Jocoaitique',
            'subfield' => 'ethnoornithology',
        ]);

        $this->assertSame(
            Project::SUBFIELD_ETHNOBOTANY,
            Project::where('user_id', $user->id)->sole()->subfield
        );
    }

    public function test_the_subfield_is_not_mass_assignable(): void
    {
        $project = new Project(['name' => 'Un estudio', 'subfield' => 'ethnoornithology']);

        $this->assertSame(Project::SUBFIELD_ETHNOBOTANY, $project->subfield);
    }

    public function test_the_example_study_is_ethnobotany(): void
    {
        $project = app(ExampleStudy::class)->build(User::factory()->create());

        $this->assertSame(Project::SUBFIELD_ETHNOBOTANY, $project->fresh()->subfield);
    }
}
