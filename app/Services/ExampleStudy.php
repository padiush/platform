<?php

namespace App\Services;

use App\Models\CatalogSpecies;
use App\Models\CollectingPermit;
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
use Illuminate\Support\Facades\DB;

/**
 * A complete, invented ethnobotanical study: a form, two dozen interviews
 * with their answers, a species catalog, field records in every state and a
 * collecting permit. It is the demonstration study behind the public-site
 * screenshots (DemoProjectSeeder) and the example project any user can open
 * to see the platform with something in it.
 *
 * Every informant, answer and use-report here is invented. The taxa are real
 * botanical names — a plant is not personal data, and fictional binomials
 * would make the taxonomic features look wrong — but nothing here describes a
 * real person or a real interview.
 *
 * Its words come from lang/{locale}/example_study.php, so a copy is built in
 * its owner's language. Only the words change: every copy draws the same
 * figures from the same seed.
 */
class ExampleStudy
{
    /** Fixed so the figures, and therefore the screenshots, are reproducible. */
    private const SEED = 20260806;

    /** The options, by key, in the order the seed draws them. */
    private const CATEGORIES = ['medicinal', 'food', 'construction', 'fuel', 'ritual', 'craft'];

    /** Proper nouns: the same in every language. */
    private const COMMUNITIES = [
        'San Antonio',
        'El Zapote',
        'Las Flores',
        'Concepción',
    ];

    private const PARTS = ['leaf', 'bark', 'fruit', 'root', 'flower', 'stem', 'seed'];

    private const PREPARATIONS = ['infusion', 'decoction', 'poultice', 'raw', 'maceration'];

    /**
     * Each entry is [family, genus, epithet, authority, citation weight,
     * category => affinity]. The local names are in the language files, by
     * scientific name. The weights are tuned so a handful of
     * species are near-universally cited and the tail is sparse, which is what
     * a real citation-frequency distribution looks like.
     */
    private const SPECIES = [
        ['Myrtaceae', 'Psidium', 'guajava', 'L.', 0.85, ['food' => 0.9, 'medicinal' => 0.7]],
        ['Asteraceae', 'Matricaria', 'chamomilla', 'L.', 0.80, ['medicinal' => 0.95]],
        ['Rutaceae', 'Citrus', 'aurantiifolia', '(Christm.) Swingle', 0.80, ['medicinal' => 0.8, 'food' => 0.85]],
        ['Zingiberaceae', 'Zingiber', 'officinale', 'Roscoe', 0.60, ['medicinal' => 0.9, 'food' => 0.4]],
        ['Lauraceae', 'Persea', 'americana', 'Mill.', 0.60, ['food' => 0.9, 'medicinal' => 0.3]],
        ['Moringaceae', 'Moringa', 'oleifera', 'Lam.', 0.55, ['medicinal' => 0.7, 'food' => 0.7]],
        ['Annonaceae', 'Annona', 'muricata', 'L.', 0.50, ['food' => 0.8, 'medicinal' => 0.5]],
        ['Urticaceae', 'Cecropia', 'obtusifolia', 'Bertol.', 0.45, ['medicinal' => 0.8, 'fuel' => 0.3]],
        ['Burseraceae', 'Bursera', 'simaruba', '(L.) Sarg.', 0.40, ['medicinal' => 0.7, 'construction' => 0.4, 'ritual' => 0.2]],
        ['Lamiaceae', 'Ocimum', 'basilicum', 'L.', 0.40, ['medicinal' => 0.6, 'food' => 0.5, 'ritual' => 0.3]],
        ['Meliaceae', 'Cedrela', 'odorata', 'L.', 0.35, ['construction' => 0.9, 'craft' => 0.5]],
        ['Asteraceae', 'Tagetes', 'erecta', 'L.', 0.35, ['ritual' => 0.8, 'medicinal' => 0.3]],
        ['Bignoniaceae', 'Crescentia', 'alata', 'Kunth', 0.30, ['craft' => 0.8, 'food' => 0.2]],
        ['Acanthaceae', 'Justicia', 'carthaginensis', 'Jacq.', 0.25, ['medicinal' => 0.7]],
    ];

    /** The language this copy is being built in. */
    private string $locale = 'es';

    /**
     * Build the study as a project the user administers, in one transaction,
     * in the given language; the name and institution default to that
     * language's. The same seed always gives the same figures, so every copy
     * matches the screenshots and the indices a user sees can be compared
     * with anyone else's.
     */
    public function build(
        User $owner,
        string $locale = 'es',
        bool $example = true,
        ?string $name = null,
        ?string $institution = null,
    ): Project {
        $this->locale = $locale;
        $name ??= $this->words('name');
        $institution ??= $this->words('institution');

        mt_srand(self::SEED);

        try {
            return DB::transaction(function () use ($owner, $name, $institution, $example) {
                $project = $this->project($owner, $name, $institution, $example);
                $species = $this->catalog($project);
                [$form, $items, $sections] = $this->form($project);

                $this->interviews($form, $sections, $items, $species, $owner);
                $this->fieldRecords($project, $species);

                return $project;
            });
        } finally {
            // Nothing else in the request should draw from a fixed sequence.
            mt_srand();
        }
    }

    private function project(User $user, string $name, string $institution, bool $example): Project
    {
        $project = new Project([
            'name' => $name,
            'author' => $user->name,
            'institution' => $institution,
            'author_email' => $user->email,
            'country' => 'El Salvador',
            'accession_prefix' => 'DEMO',
            'finished' => false,
            'published' => false,
            'shared' => false,
        ]);
        $project->user_id = $user->id;
        $project->is_example = $example;
        $project->save();

        ProjectAccess::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_capability_id' => ProjectCapability::where('manage_project', true)->value('id'),
        ]);

        return $project;
    }

    /**
     * A few physical collections, in the three states a researcher actually
     * has: identified and deposited, examined without a name being reached,
     * and freshly collected with nothing said about it yet.
     *
     * Without these the demo shows a catalog of taxa and no evidence that
     * anything was ever picked, which is the half of the work that happens
     * first.
     */
    private function fieldRecords(Project $project, array $species): void
    {
        $collector = 'A. Domínguez';

        // Wild material is collected under a national authorisation. Invented,
        // like everything else here.
        $permit = new CollectingPermit([
            'authority' => 'MARN',
            'reference' => 'DEMO-RES-042-2026',
            'issued_on' => '2026-01-15',
            'expires_on' => '2027-01-14',
            'notes' => $this->words('permit_notes'),
        ]);
        $permit->project_id = $project->id;
        $permit->save();

        // Identified, deposited, voucher issued by the project itself — the
        // community-herbarium case.
        foreach (array_slice(array_keys($species), 0, 3) as $index => $scientific) {
            $fieldRecord = new FieldRecord([
                'collection_number' => (string) (101 + $index),
                'collector' => $collector,
                'collected_on' => '2026-03-'.str_pad((string) (10 + $index), 2, '0', STR_PAD_LEFT),
                'locality' => $this->words('localities.coffee'),
                'repository' => $this->words('repository'),
                'accession_number' => 'DEMO-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'collecting_permit_id' => $permit->id,
            ]);
            $fieldRecord->project_id = $project->id;
            $fieldRecord->save();

            $determination = new Determination([
                'catalog_species_id' => $species[$scientific]->id,
                'determiner' => 'M. Alvarenga',
                'determined_on' => '2026-04-02',
                'is_current' => true,
            ]);
            $determination->field_record_id = $fieldRecord->id;
            $determination->save();
        }

        // Examined and not nameable: indet., which is a determination.
        $indet = new FieldRecord([
            'collection_number' => '104',
            'collector' => $collector,
            'collected_on' => '2026-03-14',
            'locality' => $this->words('localities.stream'),
        ]);
        $indet->project_id = $project->id;
        $indet->save();

        $unnamed = new Determination([
            'catalog_species_id' => null,
            'determiner' => 'M. Alvarenga',
            'determined_on' => '2026-04-02',
            'is_current' => true,
        ]);
        $unnamed->field_record_id = $indet->id;
        $unnamed->save();

        // A guided walk with a key informant: named on the spot, nothing
        // taken. No permit covers it and none is needed, because nothing was
        // collected — the record is the photograph and what was said.
        $observed = new FieldRecord([
            'basis_of_record' => FieldRecord::BASIS_OBSERVATION,
            'collector' => $collector,
            'collected_on' => '2026-03-18',
            'locality' => $this->words('localities.trail'),
            'vernacular_name' => $this->words('observation.name'),
            'notes' => $this->words('observation.notes'),
        ]);
        $observed->project_id = $project->id;
        $observed->save();

        // Collected, nobody has looked at it yet. Different from indet.
        foreach (['105', '106'] as $offset => $number) {
            $pending = new FieldRecord([
                'collection_number' => $number,
                'collector' => $collector,
                'collected_on' => '2026-03-1'.(5 + $offset),
                'locality' => $this->words('localities.garden'),
                // Cultivated in a household garden: outside the permit regime,
                // which is an answer rather than a blank.
                'permit_exemption' => 'cultivated',
            ]);
            $pending->project_id = $project->id;
            $pending->save();
        }
    }

    /** @return array<string, CatalogSpecies> keyed by scientific name */
    private function catalog(Project $project): array
    {
        $catalog = [];

        foreach (self::SPECIES as [$family, $genus, $epithet, $authority]) {
            $catalog["{$genus} {$epithet}"] = CatalogSpecies::create([
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
            'name' => $this->words('form.name'),
            'description' => $this->words('form.description'),
            'is_active' => true,
        ]);

        $informant = InterviewSection::create([
            'interview_form_id' => $form->id,
            'name' => $this->words('sections.informant.name'),
            'description' => $this->words('sections.informant.description'),
            'order' => 1,
            'repeatable' => false,
        ]);

        $uses = InterviewSection::create([
            'interview_form_id' => $form->id,
            'name' => $this->words('sections.uses.name'),
            'description' => $this->words('sections.uses.description'),
            'order' => 2,
            'repeatable' => true,
        ]);

        $items = [
            'edad' => InterviewItem::create([
                'interview_section_id' => $informant->id,
                ...$this->question('age'), 'type' => 'number',
                'required' => true, 'order' => 1, 'min' => 18, 'max' => 99, 'step' => 1,
            ]),
            'comunidad' => InterviewItem::create([
                'interview_section_id' => $informant->id,
                ...$this->question('community'), 'type' => 'select',
                'required' => true, 'order' => 2, 'options' => self::COMMUNITIES,
            ]),
            'residencia' => InterviewItem::create([
                'interview_section_id' => $informant->id,
                ...$this->question('residence'), 'type' => 'number',
                'required' => false, 'order' => 3, 'min' => 0, 'max' => 99, 'step' => 1,
            ]),
            'planta' => InterviewItem::create([
                'interview_section_id' => $uses->id,
                ...$this->question('plant'), 'type' => 'text',
                'required' => true, 'order' => 1, 'link_to_species' => true,
            ]),
            'categoria' => InterviewItem::create([
                'interview_section_id' => $uses->id,
                ...$this->question('category'), 'type' => 'select',
                'required' => true, 'order' => 2, 'options' => $this->options('categories', self::CATEGORIES),
                'is_use_category' => true,
            ]),
            'parte' => InterviewItem::create([
                'interview_section_id' => $uses->id,
                ...$this->question('part'), 'type' => 'select',
                'required' => false, 'order' => 3, 'options' => $this->options('parts', self::PARTS),
            ]),
            'preparacion' => InterviewItem::create([
                'interview_section_id' => $uses->id,
                ...$this->question('preparation'), 'type' => 'select',
                'required' => false, 'order' => 4, 'options' => $this->options('preparations', self::PREPARATIONS),
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

            foreach (self::SPECIES as [, $genus, $epithet, , $weight, $affinities]) {
                $scientific = "{$genus} {$epithet}";

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
                        $this->words("species.{$scientific}"),
                        $species[$scientific]->id
                    );
                    $this->answer($instance, $sections['usos'], $items['categoria'], $set, $this->words("categories.{$category}"));
                    $this->answer($instance, $sections['usos'], $items['parte'], $set, $this->words('parts.'.self::PARTS[mt_rand(0, count(self::PARTS) - 1)]));
                    $this->answer($instance, $sections['usos'], $items['preparacion'], $set, $this->words('preparations.'.self::PREPARATIONS[mt_rand(0, count(self::PREPARATIONS) - 1)]));

                    $set++;
                }
            }
        }
    }

    /** One of the study's words, in the language of this copy. */
    private function words(string $key): string
    {
        return trans("example_study.{$key}", [], $this->locale);
    }

    /** @return array{label: string, name: string} */
    private function question(string $key): array
    {
        return [
            'label' => $this->words("items.{$key}.label"),
            'name' => $this->words("items.{$key}.name"),
        ];
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    private function options(string $group, array $keys): array
    {
        return array_map(fn (string $key) => $this->words("{$group}.{$key}"), $keys);
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
