<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SalesforceUser;
use App\Services\SalesforceService;

class CpqSimulatorController extends Controller
{
    /**
     * Display the CPQ Simulator page.
     */
    public function index()
    {
        $sfUsers = \App\Models\SalesforceUser::orderBy('label')->get();
        return view('cpq-simulator.simulator', compact('sfUsers'));
    }

    /**
     * Proxies a request to Salesforce to bypass CORS limitations in the browser.
     */
    public function proxy(Request $request, \App\Services\SalesforceService $salesforceService)
    {
        $request->validate([
            'endpoint' => 'required|string',
            'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE',
            'persona_id' => 'nullable|exists:salesforce_users,id',
            'payload' => 'nullable|array',
        ]);

        $endpoint = '/' . ltrim($request->input('endpoint'), '/');
        $method = strtoupper($request->input('method'));
        $payload = $request->input('payload', []);
        
        $sfUser = null;
        if ($request->input('persona_id')) {
            $sfUser = \App\Models\SalesforceUser::find($request->input('persona_id'));
            $token = $salesforceService->getAccessTokenForUser($sfUser);
        } else {
            $token = $salesforceService->getAccessToken();
        }

        $baseUrl = rtrim(env('SALESFORCE_URL', 'https://test.salesforce.com'), '/');
        $url = $baseUrl . $endpoint;

        $client = Http::withToken($token)->acceptJson()->asJson()->timeout(60);
        
        $makeRequest = function($client, $method, $url, $payload) {
             if ($method === 'GET') return $client->get($url, $payload);
             if ($method === 'POST') return $client->post($url, $payload);
             if ($method === 'PATCH') return $client->patch($url, $payload);
             if ($method === 'PUT') return $client->put($url, $payload);
             if ($method === 'DELETE') return $client->delete($url, $payload);
        };

        try {
            Log::info('[CPQ Proxy] Request', ['method' => $method, 'url' => $url]);

            $response = $makeRequest($client, $method, $url, $payload);

            Log::info('[CPQ Proxy] Response', ['status' => $response->status(), 'url' => $url]);

            if ($response->status() === 401 && $sfUser) {
                Log::warning('[CPQ Proxy] 401 — refreshing token and retrying', ['url' => $url]);
                $token  = $salesforceService->refreshUserToken($sfUser);
                $client = Http::withToken($token)->acceptJson()->asJson()->timeout(60);
                $response = $makeRequest($client, $method, $url, $payload);
                Log::info('[CPQ Proxy] Retry response', ['status' => $response->status(), 'url' => $url]);
            }

            return response()->json([
                'status'  => $response->status(),
                'headers' => $response->headers(),
                'data'    => $response->json(),
                'body'    => $response->body()
            ], $response->status() >= 500 ? 500 : 200);

        } catch (\Exception $e) {
            Log::error('[CPQ Proxy] Exception', ['url' => $url, 'error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rootProducts(Request $request, SalesforceService $salesforceService)
    {
        $request->validate([
            'cart_id'        => 'nullable|string',
            'price_list_id'  => 'required|string',
            'opportunity_id' => 'nullable|string',
            'currency'       => 'nullable|string|max:10',
            'record_type_id' => 'nullable|string',
            'persona_id'     => 'nullable|exists:salesforce_users,id',
            'force_refresh'  => 'nullable|boolean',
        ]);

        $cartId        = $request->input('cart_id');
        $priceListId   = $request->input('price_list_id');
        $personaId     = $request->input('persona_id');
        $opportunityId = $request->input('opportunity_id');
        $cacheKey      = "cpq_root_products_{$priceListId}_{$cartId}";

        if ($request->boolean('force_refresh')) {
            Log::info('[CPQ RootProducts] Cache busted', ['cache_key' => $cacheKey]);
            Cache::forget($cacheKey);
        }

        $products = Cache::remember($cacheKey, 86400, function () use (
            $cartId, $priceListId, $personaId, $opportunityId, $salesforceService
        ) {
            $sfUser = null;
            if ($personaId) {
                $sfUser = SalesforceUser::find($personaId);
                $token  = $salesforceService->getAccessTokenForUser($sfUser);
            } else {
                $token = $salesforceService->getAccessToken();
            }

            $baseUrl = rtrim(env('SALESFORCE_URL', ''), '/');

            // No cart supplied — look up the most recent quote on the opportunity
            if (!$cartId) {
                if (!$opportunityId) {
                    Log::warning('[CPQ RootProducts] No cart_id and no opportunity_id — cannot resolve cart');
                    return ['records' => []];
                }

                $soql    = urlencode("SELECT Id FROM Quote WHERE OpportunityId = '{$opportunityId}' ORDER BY CreatedDate DESC LIMIT 1");
                $soqlRes = Http::withToken($token)->acceptJson()->timeout(30)
                    ->get("{$baseUrl}/services/data/v60.0/query?q={$soql}");

                if ($soqlRes->successful() && !empty($soqlRes->json()['records'])) {
                    $cartId = $soqlRes->json()['records'][0]['Id'];
                    Log::info('[CPQ RootProducts] Resolved cart_id from opportunity SOQL', [
                        'opportunity_id' => $opportunityId,
                        'cart_id'        => $cartId,
                    ]);
                } else {
                    Log::info('[CPQ RootProducts] No existing quote for opportunity', ['opportunity_id' => $opportunityId]);
                    return ['records' => []];
                }
            }

            Log::info('[CPQ RootProducts] Cache miss — fetching from Salesforce', [
                'price_list_id' => $priceListId,
                'cart_id'       => $cartId,
            ]);

            $endpoint = "/services/apexrest/vlocity_cmt/v2/cpq/carts/{$cartId}/products"
                      . "?hierarchy=0&pagesize=500&includeAttachment=false&includeAttributes=true"
                      . "&priceListId={$priceListId}";

            Log::info('[CPQ RootProducts] GET products', ['url' => $baseUrl . $endpoint]);

            $response = Http::withToken($token)->acceptJson()->asJson()->timeout(60)->get($baseUrl . $endpoint);

            Log::info('[CPQ RootProducts] Products response', ['status' => $response->status()]);
            Log::debug($response->json()['records']);

            if ($response->status() === 401 && $sfUser) {
                Log::warning('[CPQ RootProducts] 401 — refreshing token and retrying');
                $token    = $salesforceService->refreshUserToken($sfUser);
                $response = Http::withToken($token)->acceptJson()->asJson()->timeout(60)->get($baseUrl . $endpoint);
                Log::info('[CPQ RootProducts] Retry response', ['status' => $response->status()]);
            }

            $count = count($response->json()['records'] ?? []);
            Log::info('[CPQ RootProducts] Caching result', ['product_count' => $count, 'price_list_id' => $priceListId]);

            return $response->json();
        });

        if ($products !== null) {
            Log::info('[CPQ RootProducts] Returning products', [
                'cache_key'     => $cacheKey,
                'product_count' => count($products['records'] ?? []),
            ]);
        }

        return response()->json($products);
    }
}
