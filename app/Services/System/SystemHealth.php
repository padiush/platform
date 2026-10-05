<?php

namespace App\Services\System;

use App\Models\SystemRun;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Carbon;

/**
 * Whether the upkeep the platform relies on is happening: the scheduler that
 * runs the daily jobs, uploads left open, the orphaned-media check, and
 * migrations not yet run. Each check is `ok`, `warn` or `unknown`, with the
 * figures the panel words.
 */
class SystemHealth
{
    /** The scheduler runs every minute; this long without it is a problem. */
    private const SCHEDULER_LATE_MINUTES = 10;

    /** The daily job aborts uploads open seven days; past eight, it is not running. */
    private const UPLOAD_STUCK_DAYS = 8;

    public function __construct(private StorageUsage $usage, private Migrator $migrator) {}

    public function checks(?array $inFlight = null): array
    {
        $inFlight ??= $this->usage->inFlight();

        return [
            'scheduler' => $this->scheduler(),
            'uploads' => $this->uploads($inFlight),
            'waiting' => ['status' => 'ok', 'count' => $inFlight['pending']],
            'orphans' => $this->orphans(),
        ];
    }

    public function pendingMigrations(): int
    {
        if (! $this->migrator->repositoryExists()) {
            return 0;
        }

        $files = $this->migrator->getMigrationFiles(
            array_merge([database_path('migrations')], $this->migrator->paths())
        );

        return count(array_diff(array_keys($files), $this->migrator->getRepository()->getRan()));
    }

    private function scheduler(): array
    {
        $run = SystemRun::find(SystemRun::SCHEDULER);

        if (! $run) {
            return ['status' => 'unknown', 'ran_at' => null];
        }

        return [
            'status' => $run->ran_at->gt(now()->subMinutes(self::SCHEDULER_LATE_MINUTES)) ? 'ok' : 'warn',
            'ran_at' => $run->ran_at->toIso8601String(),
        ];
    }

    private function uploads(array $inFlight): array
    {
        $oldest = $inFlight['oldest_open_upload'];
        $stuck = $oldest !== null && now()->subDays(self::UPLOAD_STUCK_DAYS)->gt($oldest);

        return [
            'status' => $stuck ? 'warn' : 'ok',
            'count' => $inFlight['open_uploads'],
            'oldest' => $oldest ? Carbon::parse($oldest)->toIso8601String() : null,
        ];
    }

    private function orphans(): array
    {
        $run = SystemRun::find(SystemRun::PRUNE_ORPHANS);

        if (! $run) {
            return ['status' => 'warn', 'ran_at' => null, 'found' => null, 'bytes' => null, 'deleted' => false];
        }

        $summary = $run->summary ?? [];
        $found = (int) ($summary['found'] ?? 0);
        $deleted = (bool) ($summary['deleted'] ?? false);

        return [
            'status' => $found === 0 || $deleted ? 'ok' : 'warn',
            'ran_at' => $run->ran_at->toIso8601String(),
            'found' => $found,
            'bytes' => (int) ($summary['bytes'] ?? 0),
            'deleted' => $deleted,
        ];
    }
}
