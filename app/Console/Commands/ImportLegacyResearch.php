<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LegacyResearchImporter;
use App\Services\LegacyResearchWorkbook;
use Illuminate\Console\Command;
use Throwable;

class ImportLegacyResearch extends Command
{
    protected $signature = 'padiush:import-legacy-research
        {file : Absolute path to data_ingest.xlsx}
        {--owner= : Email address of the project owner}
        {--project-name=Investigación etnobotánica histórica : Spanish project name}
        {--execute : Persist the import; without this flag the command is a dry run}
        {--report= : Optional absolute path for the JSON reconciliation report}';

    protected $description = 'Validate and import the historical Spanish ethnobotanical research workbook';

    public function handle(LegacyResearchWorkbook $workbook, LegacyResearchImporter $importer): int
    {
        $path = (string) $this->argument('file');
        $ownerEmail = trim((string) $this->option('owner'));
        $projectName = trim((string) $this->option('project-name'));

        if ($ownerEmail === '') {
            $this->error('The --owner option is required.');

            return self::FAILURE;
        }

        if ($projectName === '') {
            $this->error('The --project-name option cannot be empty.');

            return self::FAILURE;
        }

        $owner = User::where('email', $ownerEmail)->first();

        if (! $owner) {
            $this->error("No user was found for {$ownerEmail}.");

            return self::FAILURE;
        }

        try {
            $dataset = $workbook->read($path);
            $workbook->assertExpected($dataset);

            $this->displaySummary($dataset['summary']);

            $report = [
                'mode' => $this->option('execute') ? 'execute' : 'dry-run',
                'source' => $dataset['source'],
                'project_name' => $projectName,
                'owner' => $owner->email,
                'source_summary' => $dataset['summary'],
            ];

            if (! $this->option('execute')) {
                $this->info('Dry run passed. No database changes were made.');
                $this->writeReport($report);

                return self::SUCCESS;
            }

            $result = $importer->import($dataset, $owner, $projectName);
            $report['result'] = $result;

            $this->newLine();
            $this->info("Imported project #{$result['project_id']} and form #{$result['form_id']}.");
            $this->table(
                ['Database check', 'Value'],
                collect($result['verification'])->map(fn ($value, $key) => [$key, is_bool($value) ? ($value ? 'true' : 'false') : $value])->values()->all()
            );
            $this->writeReport($report);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function displaySummary(array $summary): void
    {
        $this->table(['Source check', 'Value'], [
            ['Interviews', $summary['interviews']],
            ['Legacy reports', $summary['reports']],
            ['Species-use rows', $summary['records']],
            ['Catalog taxa', $summary['taxa']],
            ['Multi-species reports', $summary['multi_species_reports']],
            ['Analytical combinations', $summary['analytical_combinations']],
        ]);

        $this->table(
            ['Categoría', 'Filas válidas'],
            collect($summary['categories'])->map(fn ($count, $category) => [$category, $count])->values()->all()
        );
    }

    private function writeReport(array $report): void
    {
        $path = trim((string) $this->option('report'));

        if ($path === '') {
            return;
        }

        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new \RuntimeException("Unable to create report directory: {$directory}");
        }

        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($path, $json."\n", LOCK_EX) === false) {
            throw new \RuntimeException("Unable to write reconciliation report: {$path}");
        }

        $this->info("Reconciliation report written to {$path}.");
    }
}
