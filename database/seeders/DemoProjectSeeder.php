<?php

namespace Database\Seeders;

use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;
use App\Models\InterviewForm;
use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\InterviewSection;
use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * A self-contained demonstration study, used for the public-site screenshots
 * and for demos.
 *
 * Every informant, answer and use-report here is invented. The taxa are real
 * botanical names — a plant is not personal data, and fictional binomials
 * would make the taxonomic features look wrong — but nothing in this seeder
 * describes a real person or a real interview.
 *
 * Run it explicitly; it is deliberately not part of DatabaseSeeder:
 *
 *   php artisan db:seed --class=DemoProjectSeeder
 */
class DemoProjectSeeder extends Seeder
{
    private const PROJECT_NAME = 'Plantas útiles de la cordillera (estudio demostrativo)';

    private const DEMO_EMAIL = 'demo@padiush.test';

    /**
     * A fixture credential for a fixture account, so the screenshot capture
     * script can sign in the way a person would. Harmless because this seeder
     * refuses to run in production and the account exists nowhere else.
     */
    private const DEMO_PASSWORD = 'demo-screenshots';

    /** Fixed so the figures, and therefore the screenshots, are reproducible. */
    private const SEED = 20260806;

    private const CATEGORIES = [
        'Medicinal',
        'Alimenticio',
        'Construcción',
        'Combustible',
        'Ritual',
        'Artesanal',
    ];

    private const COMMUNITIES = [
        'San Antonio',
        'El Zapote',
        'Las Flores',
        'Concepción',
    ];

    private const PARTS = [
        'Hoja',
        'Corteza',
        'Fruto',
        'Raíz',
        'Flor',
        'Tallo',
        'Semilla',
    ];

    private const PREPARATIONS = [
        'Infusión',
        'Cocimiento',
        'Emplasto',
        'Consumo directo',
        'Macerado',
    ];

    /**
     * Each entry is [family, genus, epithet, authority, local name, citation
     * weight, categories => affinity]. The weights are tuned so a handful of
     * species are near-universally cited and the tail is sparse, which is what
     * a real citation-frequency distribution looks like.
     */
    private const SPECIES = [
        ['Myrtaceae', 'Psidium', 'guajava', 'L.', 'guayaba', 0.85, ['Alimenticio' => 0.9, 'Medicinal' => 0.7]],
        ['Asteraceae', 'Matricaria', 'chamomilla', 'L.', 'manzanilla', 0.80, ['Medicinal' => 0.95]],
        ['Rutaceae', 'Citrus', 'aurantiifolia', '(Christm.) Swingle', 'limón', 0.80, ['Medicinal' => 0.8, 'Alimenticio' => 0.85]],
        ['Zingiberaceae', 'Zingiber', 'officinale', 'Roscoe', 'jengibre', 0.60, ['Medicinal' => 0.9, 'Alimenticio' => 0.4]],
        ['Lauraceae', 'Persea', 'americana', 'Mill.', 'aguacate', 0.60, ['Alimenticio' => 0.9, 'Medicinal' => 0.3]],
        ['Moringaceae', 'Moringa', 'oleifera', 'Lam.', 'moringa', 0.55, ['Medicinal' => 0.7, 'Alimenticio' => 0.7]],
        ['Annonaceae', 'Annona', 'muricata', 'L.', 'guanábana', 0.50, ['Alimenticio' => 0.8, 'Medicinal' => 0.5]],
        ['Urticaceae', 'Cecropia', 'obtusifolia', 'Bertol.', 'guarumo', 0.45, ['Medicinal' => 0.8, 'Combustible' => 0.3]],
        ['Burseraceae', 'Bursera', 'simaruba', '(L.) Sarg.', 'jiote', 0.40, ['Medicinal' => 0.7, 'Construcción' => 0.4, 'Ritual' => 0.2]],
        ['Lamiaceae', 'Ocimum', 'basilicum', 'L.', 'albahaca', 0.40, ['Medicinal' => 0.6, 'Alimenticio' => 0.5, 'Ritual' => 0.3]],
        ['Meliaceae', 'Cedrela', 'odorata', 'L.', 'cedro', 0.35, ['Construcción' => 0.9, 'Artesanal' => 0.5]],
        ['Asteraceae', 'Tagetes', 'erecta', 'L.', 'flor de muerto', 0.35, ['Ritual' => 0.8, 'Medicinal' => 0.3]],
        ['Bignoniaceae', 'Crescentia', 'alata', 'Kunth', 'morro', 0.30, ['Artesanal' => 0.8, 'Alimenticio' => 0.2]],
        ['Acanthaceae', 'Justicia', 'carthaginensis', 'Jacq.', 'chichipince', 0.25, ['Medicinal' => 0.7]],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoProjectSeeder refuses to run in production.');

            return;
        }

        mt_srand(self::SEED);

        DB::transaction(function () {
            $this->purgeExisting();

            $user = $this->demoUser();
            $project = $this->project($user);
            $species = $this->catalog($project);
            [$form, $items, $sections] = $this->form($project);

            $this->interviews($form, $sections, $items, $species, $user);
        });

        $this->command?->info('Demo project seeded: '.self::PROJECT_NAME);
    }

    /** Re-running should replace the demo, never accumulate copies of it. */
    private function purgeExisting(): void
    {
        Project::where('name', self::PROJECT_NAME)->get()->each->delete();
    }

    private function demoUser(): User
    {
        // updateOrCreate, not firstOrCreate: re-running the seeder should leave
        // the fixture in a known state rather than keeping whatever password an
        // earlier run happened to set.
        return User::updateOrCreate(
            ['email' => self::DEMO_EMAIL],
            [
                'name' => 'Equipo Padiush',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
            ]
        );
    }

    private function project(User $user): Project
    {
        $project = new Project([
            'name' => self::PROJECT_NAME,
            'author' => 'Equipo Padiush',
            'institution' => 'Proyecto demostrativo',
            'author_email' => self::DEMO_EMAIL,
            'country' => 'El Salvador',
            'finished' => false,
            'published' => false,
            'shared' => false,
        ]);
        $project->user_id = $user->id;
        $project->save();

        ProjectAccess::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->value('id'),
        ]);

        return $project;
    }

    /** @return array<string, CatalogSpecies> keyed by local name */
    private function catalog(Project $project): array
    {
        $catalog = [];

        foreach (self::SPECIES as [$family, $genus, $epithet, $authority, $local]) {
            $catalog[$local] = CatalogSpecies::create([
                'project_id' => $project->id,
                'family' => $family,
                'genus' => $genus,
                'name' => $epithet,
                'authority' => $authority,
            ]);
        }

        return $catalog;
    }

    /** @return array{0: InterviewForm, 1: array<string, InterviewItem>, 2: array<string, InterviewSection>} */
    private function form(Project $project): array
    {
        $form = InterviewForm::create([
            'project_id' => $project->id,
            'name' => 'Entrevista etnobotánica (demostración)',
            'description' => 'Instrumento de ejemplo: datos del informante y usos reportados por especie.',
            'is_active' => true,
        ]);

        $informant = InterviewSection::create([
            'interview_form_id' => $form->id,
            'name' => 'Datos del informante',
            'description' => 'Información general de la persona entrevistada.',
            'order' => 1,
            'repeatable' => false,
        ]);

        $uses = InterviewSection::create([
            'interview_form_id' => $form->id,
            'name' => 'Usos reportados',
            'description' => 'Un conjunto por cada planta mencionada.',
            'order' => 2,
            'repeatable' => true,
        ]);

        $items = [
            'edad' => InterviewItem::create([
                'interview_section_id' => $informant->id,
                'label' => 'Edad', 'name' => 'edad', 'type' => 'number',
                'required' => true, 'order' => 1, 'min' => 18, 'max' => 99, 'step' => 1,
            ]),
            'comunidad' => InterviewItem::create([
                'interview_section_id' => $informant->id,
                'label' => 'Comunidad', 'name' => 'comunidad', 'type' => 'select',
                'required' => true, 'order' => 2, 'options' => self::COMMUNITIES,
            ]),
            'residencia' => InterviewItem::create([
                'interview_section_id' => $informant->id,
                'label' => 'Años de residencia', 'name' => 'residencia', 'type' => 'number',
                'required' => false, 'order' => 3, 'min' => 0, 'max' => 99, 'step' => 1,
            ]),
            'planta' => InterviewItem::create([
                'interview_section_id' => $uses->id,
                'label' => 'Nombre local de la planta', 'name' => 'planta', 'type' => 'text',
                'required' => true, 'order' => 1, 'link_to_species' => true,
            ]),
            'categoria' => InterviewItem::create([
                'interview_section_id' => $uses->id,
                'label' => 'Categoría de uso', 'name' => 'categoria', 'type' => 'select',
                'required' => true, 'order' => 2, 'options' => self::CATEGORIES,
                'is_use_category' => true,
            ]),
            'parte' => InterviewItem::create([
                'interview_section_id' => $uses->id,
                'label' => 'Parte utilizada', 'name' => 'parte', 'type' => 'select',
                'required' => false, 'order' => 3, 'options' => self::PARTS,
            ]),
            'preparacion' => InterviewItem::create([
                'interview_section_id' => $uses->id,
                'label' => 'Preparación', 'name' => 'preparacion', 'type' => 'select',
                'required' => false, 'order' => 4, 'options' => self::PREPARATIONS,
            ]),
        ];

        return [$form, $items, ['informante' => $informant, 'usos' => $uses]];
    }

    /**
     * @param  array<string, InterviewItem>  $items
     * @param  array<string, InterviewSection>  $sections
     * @param  array<string, CatalogSpecies>  $species
     */
    private function interviews(
        InterviewForm $form,
        array $sections,
        array $items,
        array $species,
        User $user
    ): void {
        for ($informant = 0; $informant < 24; $informant++) {
            $instance = InterviewInstance::create([
                'interview_form_id' => $form->id,
                'user_id' => $user->id,
                'captured_at' => now()->subDays(90 - $informant * 3),
                // Loosely around the Salvadoran highlands; jittered per record.
                'location_lat' => 13.85 + mt_rand(-400, 400) / 10000,
                'location_lng' => -89.15 + mt_rand(-400, 400) / 10000,
                'location_accuracy_m' => mt_rand(4, 18),
                'location_captured_at' => now()->subDays(90 - $informant * 3),
            ]);

            $this->answer($instance, $sections['informante'], $items['edad'], null, (string) mt_rand(24, 78));
            $this->answer($instance, $sections['informante'], $items['comunidad'], null, self::COMMUNITIES[mt_rand(0, 3)]);
            $this->answer($instance, $sections['informante'], $items['residencia'], null, (string) mt_rand(3, 60));

            $set = 0;

            foreach (self::SPECIES as [, , , , $local, $weight, $affinities]) {
                if (mt_rand(1, 100) > $weight * 100) {
                    continue;
                }

                foreach ($affinities as $category => $affinity) {
                    if (mt_rand(1, 100) > $affinity * 100) {
                        continue;
                    }

                    $this->answer(
                        $instance,
                        $sections['usos'],
                        $items['planta'],
                        $set,
                        $local,
                        $species[$local]->id
                    );
                    $this->answer($instance, $sections['usos'], $items['categoria'], $set, $category);
                    $this->answer($instance, $sections['usos'], $items['parte'], $set, self::PARTS[mt_rand(0, count(self::PARTS) - 1)]);
                    $this->answer($instance, $sections['usos'], $items['preparacion'], $set, self::PREPARATIONS[mt_rand(0, count(self::PREPARATIONS) - 1)]);

                    $set++;
                }
            }
        }
    }

    private function answer(
        InterviewInstance $instance,
        InterviewSection $section,
        InterviewItem $item,
        ?int $set,
        string $value,
        ?int $speciesId = null
    ): void {
        InstanceAnswer::create([
            'interview_instance_id' => $instance->id,
            'interview_section_id' => $section->id,
            'interview_item_id' => $item->id,
            'repeatable_index' => $set,
            'answer' => $value,
            'catalog_species_id' => $speciesId,
        ]);
    }
}
