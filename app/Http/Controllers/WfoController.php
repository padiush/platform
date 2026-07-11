<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WfoController extends Controller
{
    public function query(Request $request)
    {
        $validated = $request->validate([
            'input' => ['required', 'string', 'max:255'],
        ]);

        $terms = $validated['input'];

        $query = <<<'GRAPHQL'
    query NameSearch($terms: String!) {
        taxonNameSuggestion(termsString: $terms, limit: 100) {
            id
            stableUri
            fullNameStringPlain
            fullNameStringHtml
            currentPreferredUsage {
                hasName {
                    id
                    stableUri
                    fullNameStringHtml
                }
            }
        }
    }
GRAPHQL;

        // WFO's server omits its intermediate certificate; see the bundle's header.
        $response = Http::timeout(15)
            ->withOptions(['verify' => base_path('resources/certs/wfo-ca-chain.pem')])
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://list.worldfloraonline.org/gql.php', [
                'query' => $query,
                'variables' => [
                    'terms' => $terms,
                ],
            ]);

        return response()->json($response->json());
    }
}
