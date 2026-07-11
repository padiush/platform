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

        $response = $this->post(route('wfo.query'), ['input' => 'Quercus']);

        $response->assertRedirect(route('login'));
        Http::assertNothingSent();
    }

    public function test_input_is_required()
    {
        Http::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('wfo.query'), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['input']);
        Http::assertNothingSent();
    }

    public function test_query_is_forwarded_and_response_returned()
    {
        Http::fake([
            'list.worldfloraonline.org/*' => Http::response([
                'data' => ['taxonNameSuggestion' => [['id' => 'wfo-0000615907']]],
            ]),
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('wfo.query'), [
            'input' => 'Quercus robur',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.taxonNameSuggestion.0.id', 'wfo-0000615907');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'list.worldfloraonline.org')
                && $request['variables']['terms'] === 'Quercus robur';
        });
    }
}
