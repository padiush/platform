<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
}
