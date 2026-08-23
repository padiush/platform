<?php

namespace App\Services;

use App\Models\FieldRecord;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Issues accession numbers on a project's behalf.
 *
 * An institutional herbarium has a curator and a registry to hand these out. A
 * community herbarium has neither, which is why fieldRecords deposited with a
 * community end up unvouchered — so the project becomes its own issuing
 * authority. See docs/decisions/0008-specimens-and-determinations.md.
 *
 * The format is a configurable prefix and a zero-padded sequence, per project:
 * `MML-0001`, or `0001` when no prefix is set. Nothing forces a fieldRecord to use
 * it — `accession_number` is a plain string, so a study that already has its own
 * numbering can enter those instead and the sequence simply steps around them.
 */
class AccessionNumbers
{
    /** How many taken numbers to step over before giving up. */
    private const MAX_ATTEMPTS = 100;

    /**
     * Take the next free number for this project and advance the sequence.
     *
     * The read and the increment are one locked transaction, so two researchers
     * registering a fieldRecord at the same moment cannot be handed the same
     * number. (`lockForUpdate` is a no-op on SQLite, which the test suite uses —
     * harmless there, since the tests are single-threaded.)
     */
    public function mint(Project $project): string
    {
        return DB::transaction(function () use ($project) {
            $locked = Project::whereKey($project->getKey())->lockForUpdate()->firstOrFail();

            $next = (int) $locked->next_accession_number;

            for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
                $candidate = $this->format($locked->accession_prefix, $next + $attempt);

                if (! $this->isTaken($locked, $candidate)) {
                    $locked->forceFill([
                        'next_accession_number' => $next + $attempt + 1,
                    ])->save();

                    // Keep the caller's instance honest about the sequence it
                    // just moved, rather than leaving it holding a stale count.
                    $project->setAttribute('next_accession_number', $next + $attempt + 1);

                    return $candidate;
                }
            }

            throw new RuntimeException(
                "Could not find a free accession number for project {$locked->getKey()} after "
                .self::MAX_ATTEMPTS.' attempts; the sequence may have been overtaken by numbers entered by hand.'
            );
        });
    }

    /** What `mint()` would return next, without consuming it. */
    public function peek(Project $project): string
    {
        return $this->format($project->accession_prefix, (int) $project->next_accession_number);
    }

    /**
     * Prefix and sequence, joined by a hyphen when there is a prefix. Padded to
     * four digits so numbers sort as text — which is how they appear in a table,
     * on a label, and in an export.
     */
    public function format(?string $prefix, int $number): string
    {
        $padded = str_pad((string) $number, 4, '0', STR_PAD_LEFT);

        $prefix = trim((string) $prefix);

        return $prefix === '' ? $padded : "{$prefix}-{$padded}";
    }

    private function isTaken(Project $project, string $accessionNumber): bool
    {
        return FieldRecord::query()
            ->where('project_id', $project->getKey())
            ->where('accession_number', $accessionNumber)
            ->exists();
    }
}
