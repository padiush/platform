<?php

namespace Tests\Feature;

use App\Exceptions\Handler;
use App\Exceptions\WfoUnavailableException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class WfoQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_query_wfo()
    {
        Http::fake();

        $response = $this->post(route('wfo.query'), [
            'genus' => 'Quercus',
            'name' => 'robur',
        ]);

        $response->assertRedirect(route('login'));
        Http::assertNothingSent();
    }

    public function test_genus_and_name_are_required()
    {
        Http::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('wfo.query'), [
            'authority' => 'L.',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['genus', 'name']);
        Http::assertNothingSent();
    }

    public function test_it_resolves_a_name_and_returns_the_structured_result()
    {
        Http::fake([
            'list.worldfloraonline.org/*' => Http::response([
                'data' => [
                    'taxonNameMatch' => ['match' => null, 'error' => false],
                    'taxonNameSuggestion' => [[
                        'id' => 'wfo-0000354479',
                        'stableUri' => 'https://list.worldfloraonline.org/wfo-0000354479',
                        'fullNameStringPlain' => 'Justicia carthaginensis Jacq.',
                        'fullNameStringHtml' => '<i>Justicia carthaginensis</i> Jacq.',
                        'currentPreferredUsage' => ['hasName' => [
                            'id' => 'wfo-0000354479',
                            'stableUri' => 'https://list.worldfloraonline.org/wfo-0000354479',
                            'fullNameStringPlain' => 'Justicia carthaginensis Jacq.',
                            'fullNameStringHtml' => '<i>Justicia carthaginensis</i> Jacq.',
                        ]],
                    ]],
                ],
            ]),
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('wfo.query'), [
            'genus' => 'Justicia',
            'name' => 'carthagenensis',
            'authority' => 'Jacq.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('recorded', 'Justicia carthagenensis Jacq.');
        $response->assertJsonPath('match', null);
        $response->assertJsonPath('candidates.0.full_name_plain', 'Justicia carthaginensis Jacq.');
        $response->assertJsonPath('candidates.0.is_accepted', true);
        $response->assertJsonPath('candidates.0.is_spelling_variant', true);

        // The full authored name is what disambiguates homonyms.
        Http::assertSent(fn ($request) => $request['variables']['match'] === 'Justicia carthagenensis Jacq.');
    }

    public function test_it_returns_502_when_wfo_is_unreachable()
    {
        Http::fake([
            'list.worldfloraonline.org/*' => Http::response('', 500),
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('wfo.query'), [
            'genus' => 'Quercus',
            'name' => 'robur',
        ]);

        $response->assertStatus(502);
        $response->assertJsonPath('error', 'wfo_unreachable');
    }

    public function test_a_repeated_lookup_does_not_ask_wfo_twice()
    {
        Http::fake([
            'list.worldfloraonline.org/*' => Http::response([
                'data' => [
                    'taxonNameMatch' => ['match' => null, 'error' => false],
                    'taxonNameSuggestion' => [],
                ],
            ]),
        ]);
        $user = User::factory()->create();

        foreach (range(1, 3) as $ignored) {
            $this->actingAs($user)->postJson(route('wfo.query'), [
                'genus' => 'Quercus',
                'name' => 'robur',
                'authority' => 'L.',
            ])->assertOk();
        }

        // Opening the same species page again is the common case, and it must
        // not cost an outbound request each time.
        Http::assertSentCount(1);
    }

    public function test_once_wfo_is_known_to_be_down_the_request_fails_without_waiting()
    {
        config(['padiush.wfo.breaker_threshold' => 1, 'padiush.wfo.breaker_cooldown' => 60]);
        Http::fake([
            'list.worldfloraonline.org/*' => Http::response('', 500),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('wfo.query'), [
            'genus' => 'Quercus',
            'name' => 'robur',
        ])->assertStatus(502);

        // A second species, so nothing answers from the first one's cache.
        $response = $this->actingAs($user)->postJson(route('wfo.query'), [
            'genus' => 'Pinus',
            'name' => 'sylvestris',
        ]);

        $response->assertStatus(502);
        $response->assertJsonPath('error', 'wfo_unreachable');
        // The point of the breaker: the second caller pays nothing to be told
        // what the first one found out.
        Http::assertSentCount(1);
    }

    public function test_the_breaker_refusing_a_call_is_not_reported_again()
    {
        $handler = app(Handler::class);

        // The request that actually failed is worth a log entry; every request
        // arriving during the cooldown would otherwise bury it.
        $this->assertFalse($handler->shouldReport(new WfoUnavailableException));
        $this->assertTrue($handler->shouldReport(new RuntimeException('WFO request failed')));
    }
}
