<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WfoController extends Controller
{
    public function query(Request $request)
    {
        $terms = $request->input('input');

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

        $response = Http::withHeaders([
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
