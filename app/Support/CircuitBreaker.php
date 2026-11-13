<?php

namespace App\Support;

use Illuminate\Contracts\Cache\Repository;

/**
 * Stops an application from queueing up behind a dependency that is already
 * known to be down.
 *
 * An outbound call that fails by timing out is the expensive kind: the caller
 * learns nothing for the whole timeout, and in a pool of a few PHP-FPM workers
 * a handful of concurrent requests is enough to starve everything else on the
 * site. The breaker turns the second such failure into an immediate one: it
 * records failures, opens once they reach a threshold, and stays open for a
 * cooldown during which callers are expected to give up without making the
 * call at all.
 *
 * State lives in the cache rather than in the process, because the workers that
 * need to agree about it are separate processes. With the file store a
 * read-modify-write of the counter is not atomic, so simultaneous failures can
 * be counted as one; the consequence is that the breaker occasionally opens a
 * failure or two later than the threshold says, which costs one more timeout
 * and is not worth a lock to avoid.
 *
 * The caller decides what an open breaker means — most usefully, serving
 * something cached rather than failing.
 */
class CircuitBreaker
{
    public function __construct(
        private readonly Repository $cache,
        private readonly string $name,
        private readonly int $threshold,
        private readonly int $cooldown,
    ) {}

    /** Whether the dependency should be left alone for now. */
    public function isOpen(): bool
    {
        if ($this->threshold < 1 || $this->cooldown < 1) {
            return false;
        }

        return (bool) $this->cache->get($this->key('open'), false);
    }

    /**
     * Records that the dependency answered. The run of failures is over, so the
     * count is cleared and a half-open breaker closes immediately: one good
     * answer is enough evidence, and holding the breaker open past it would
     * punish a service that has already recovered.
     */
    public function recordSuccess(): void
    {
        $this->cache->forget($this->key('failures'));
        $this->cache->forget($this->key('open'));
    }

    /**
     * Records that the dependency did not answer, opening the breaker once the
     * failures reach the threshold.
     *
     * The counter expires after the same cooldown, so failures have to be both
     * consecutive and close together to trip it. An occasional blip hours apart
     * never accumulates into an outage of our own making.
     */
    public function recordFailure(): void
    {
        if ($this->threshold < 1 || $this->cooldown < 1) {
            return;
        }

        $failures = ((int) $this->cache->get($this->key('failures'), 0)) + 1;

        $this->cache->put($this->key('failures'), $failures, $this->cooldown);

        if ($failures >= $this->threshold) {
            $this->cache->put($this->key('open'), true, $this->cooldown);
        }
    }

    private function key(string $suffix): string
    {
        return "breaker:{$this->name}:{$suffix}";
    }
}
