<?php

namespace Tests\Feature\Api;

use App\Models\DeviceDiagnostic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    private User $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recorder = User::factory()->create();
        Sanctum::actingAs($this->recorder, ['capture']);
    }

    private function event(array $overrides = []): array
    {
        return array_merge([
            'client_id' => (string) Str::uuid(),
            'code' => DeviceDiagnostic::CODE_PLAINTEXT_CAPTURE_RETAINED,
            'occurred_at' => now()->subHour()->toIso8601String(),
            'app_version' => '1.0.0',
            'platform' => 'android',
            'os_version' => '34',
        ], $overrides);
    }

    public function test_it_stores_a_batch_and_reports_what_it_accepted(): void
    {
        $first = $this->event();
        $second = $this->event(['code' => DeviceDiagnostic::CODE_CAPTURE_CACHE_SWEEP_FAILED]);

        $response = $this->postJson('/api/v1/diagnostics', ['events' => [$first, $second]]);

        $response->assertStatus(202)
            ->assertJson(['accepted' => [$first['client_id'], $second['client_id']]]);

        $this->assertDatabaseCount('device_diagnostics', 2);
        $this->assertDatabaseHas('device_diagnostics', [
            'client_id' => $first['client_id'],
            'code' => DeviceDiagnostic::CODE_PLAINTEXT_CAPTURE_RETAINED,
            'user_id' => $this->recorder->id,
            'platform' => 'android',
        ]);
    }

    public function test_replaying_a_batch_does_not_duplicate_it(): void
    {
        // A device that never saw the response retries the same events.
        $event = $this->event();

        $this->postJson('/api/v1/diagnostics', ['events' => [$event]])->assertStatus(202);
        $this->postJson('/api/v1/diagnostics', ['events' => [$event]])
            ->assertStatus(202)
            ->assertJson(['accepted' => [$event['client_id']]]);

        $this->assertDatabaseCount('device_diagnostics', 1);
    }

    public function test_it_rejects_a_code_it_does_not_know(): void
    {
        // The closed enum is what keeps free text off this channel; if an
        // unknown code were stored, the guarantee would be gone.
        $response = $this->postJson('/api/v1/diagnostics', [
            'events' => [$this->event(['code' => 'user_said_something_interesting'])],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'api.validation_failed')
            ->assertJsonValidationErrors('events.0.code');

        $this->assertDatabaseCount('device_diagnostics', 0);
    }

    public function test_it_rejects_an_oversized_batch(): void
    {
        $events = [];
        for ($i = 0; $i < 101; $i++) {
            $events[] = $this->event();
        }

        $this->postJson('/api/v1/diagnostics', ['events' => $events])
            ->assertStatus(422)
            ->assertJsonValidationErrors('events');
    }

    public function test_it_rejects_an_empty_batch(): void
    {
        $this->postJson('/api/v1/diagnostics', ['events' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors('events');
    }

    public function test_it_logs_destructive_events_once(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $message, array $context) => $context['code'] === DeviceDiagnostic::CODE_STORE_RESET_UNRECOVERABLE
                && $context['user_id'] === $this->recorder->id);

        $event = $this->event(['code' => DeviceDiagnostic::CODE_STORE_RESET_UNRECOVERABLE]);

        // Sent twice; the retry must not raise the alarm a second time.
        $this->postJson('/api/v1/diagnostics', ['events' => [$event]])->assertStatus(202);
        $this->postJson('/api/v1/diagnostics', ['events' => [$event]])->assertStatus(202);
    }

    public function test_a_swept_cache_is_not_treated_as_destructive(): void
    {
        Log::shouldReceive('warning')->never();

        $this->postJson('/api/v1/diagnostics', [
            'events' => [$this->event(['code' => DeviceDiagnostic::CODE_CAPTURE_CACHE_SWEEP_FAILED])],
        ])->assertStatus(202);
    }

    public function test_it_requires_authentication(): void
    {
        // Undo the acting-as from setUp.
        app('auth')->forgetGuards();

        $this->postJson('/api/v1/diagnostics', ['events' => [$this->event()]], [
            'Authorization' => 'Bearer not-a-real-token',
        ])->assertStatus(401);
    }

    public function test_it_requires_the_capture_ability(): void
    {
        // A token minted without the capture ability must not reach this.
        Sanctum::actingAs(User::factory()->create(), []);

        $this->postJson('/api/v1/diagnostics', ['events' => [$this->event()]])
            ->assertStatus(403);
    }
}
