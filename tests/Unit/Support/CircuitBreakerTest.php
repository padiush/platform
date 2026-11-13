<?php

namespace Tests\Unit\Support;

use App\Support\CircuitBreaker;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Tests\TestCase;

class CircuitBreakerTest extends TestCase
{
    private Repository $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new Repository(new ArrayStore);
    }

    private function breaker(int $threshold = 2, int $cooldown = 60): CircuitBreaker
    {
        return new CircuitBreaker($this->cache, 'test', $threshold, $cooldown);
    }

    public function test_it_starts_closed()
    {
        $this->assertFalse($this->breaker()->isOpen());
    }

    public function test_it_stays_closed_below_the_threshold()
    {
        $breaker = $this->breaker(threshold: 2);

        $breaker->recordFailure();

        // One failure is a blip, and the dependency is still worth asking.
        $this->assertFalse($breaker->isOpen());
    }

    public function test_it_opens_once_the_failures_reach_the_threshold()
    {
        $breaker = $this->breaker(threshold: 2);

        $breaker->recordFailure();
        $breaker->recordFailure();

        $this->assertTrue($breaker->isOpen());
    }

    public function test_a_success_closes_an_open_breaker()
    {
        $breaker = $this->breaker(threshold: 2);
        $breaker->recordFailure();
        $breaker->recordFailure();
        $this->assertTrue($breaker->isOpen());

        $breaker->recordSuccess();

        $this->assertFalse($breaker->isOpen());
    }

    public function test_a_success_resets_the_failure_count()
    {
        $breaker = $this->breaker(threshold: 2);

        $breaker->recordFailure();
        $breaker->recordSuccess();
        // Were the count kept, this single later failure would reach the
        // threshold of two and open the breaker.
        $breaker->recordFailure();

        $this->assertFalse($breaker->isOpen());
    }

    public function test_it_closes_again_after_the_cooldown()
    {
        $breaker = $this->breaker(threshold: 1, cooldown: 60);
        $breaker->recordFailure();
        $this->assertTrue($breaker->isOpen());

        $this->travel(61)->seconds();

        $this->assertFalse($breaker->isOpen());
    }

    public function test_failures_further_apart_than_the_cooldown_do_not_accumulate()
    {
        $breaker = $this->breaker(threshold: 2, cooldown: 60);

        $breaker->recordFailure();
        $this->travel(61)->seconds();
        $breaker->recordFailure();

        // Two failures, but not consecutive within one window: an occasional
        // blip must never add up to an outage of our own making.
        $this->assertFalse($breaker->isOpen());
    }

    public function test_separate_names_do_not_share_state()
    {
        $wfo = new CircuitBreaker($this->cache, 'wfo', 1, 60);
        $other = new CircuitBreaker($this->cache, 'other', 1, 60);

        $wfo->recordFailure();

        $this->assertTrue($wfo->isOpen());
        $this->assertFalse($other->isOpen());
    }

    public function test_a_threshold_below_one_disables_the_breaker()
    {
        $breaker = $this->breaker(threshold: 0);

        $breaker->recordFailure();
        $breaker->recordFailure();

        $this->assertFalse($breaker->isOpen());
    }
}
