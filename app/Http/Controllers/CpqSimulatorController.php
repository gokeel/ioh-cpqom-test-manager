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
            'opportunity_id' => 'nullable|required_without:cart_id|string',
            'currency'       => 'nullable|string|max:10',
            'record_type_id' => 'nullable|string',
            'persona_id'     => 'nullable|exists:salesforce_users,id',
            'force_refresh'  => 'nullable|boolean',
        ]);

        $cartId        = $request->input('cart_id');
        $priceListId   = $request->input('price_list_id');
        $personaId     = $request->input('persona_id');
        $opportunityId = $request->input('opportunity_id');
        $currency      = $request->input('currency', 'IDR');
        $recordTypeId  = $request->input('record_type_id');
        $cacheKey      = "cpq_root_products_{$priceListId}";

        if ($request->boolean('force_refresh')) {
            Log::info('[CPQ RootProducts] Cache busted', ['cache_key' => $cacheKey]);
            Cache::forget($cacheKey);
        }

        $products = Cache::remember($cacheKey, 86400, function () use (
            $cartId, $priceListId, $personaId, $opportunityId, $currency, $recordTypeId, $salesforceService
        ) {
            Log::info('[CPQ RootProducts] Cache miss — fetching from Salesforce', [
                'price_list_id'  => $priceListId,
                'opportunity_id' => $opportunityId,
                'cart_id'        => $cartId ?? '(none — will create temp)',
            ]);

            $sfUser = null;
            if ($personaId) {
                $sfUser = SalesforceUser::find($personaId);
                $token  = $salesforceService->getAccessTokenForUser($sfUser);
            } else {
                $token = $salesforceService->getAccessToken();
            }

            $baseUrl = rtrim(env('SALESFORCE_URL', ''), '/');

            // No existing cart — create a temporary one to browse the catalogue
            if (!$cartId) {
                $inputFields = [
                    ['OpportunityId'               => $opportunityId],
                    ['Name'                        => 'Temp_Browse_' . time()],
                    ['vlocity_cmt__PriceListId__c' => $priceListId],
                    ['CurrencyIsoCode'             => $currency],
                ];
                if ($recordTypeId) {
                    $inputFields[] = ['RecordTypeId' => $recordTypeId];
                }

                $createUrl = $baseUrl . '/services/apexrest/vlocity_cmt/v2/carts';
                Log::info('[CPQ RootProducts] POST createCart', ['url' => $createUrl, 'opportunity_id' => $opportunityId]);

                $createRes = Http::withToken($token)->acceptJson()->asJson()->timeout(60)
                    ->post($createUrl, [
                        'methodName'  => 'createCart',
                        'objectType'  => 'Quote',
                        'subaction'   => 'createQuote',
                        'fields'      => 'Id,Name',
                        'filters'     => 'Account.vlocity_cmt__Status__c:Inactive_Active_Pending',
                        'inputFields' => $inputFields,
                    ]);

                Log::info('[CPQ RootProducts] createCart response', ['status' => $createRes->status()]);

                if (!$createRes->successful()) {
                    Log::error('[CPQ RootProducts] createCart failed', ['body' => $createRes->body()]);
                    return null;
                }

                $cartData = $createRes->json();
                $cartId   = $cartData['cartId']
                    ?? ($cartData['records'][0]['Id'] ?? null)
                    ?? $cartData['Id']
                    ?? null;

                if (!$cartId) {
                    Log::error('[CPQ RootProducts] Could not extract cartId', ['response' => $cartData]);
                    return null;
                }

                Log::info('[CPQ RootProducts] Temp cart created', ['cart_id' => $cartId]);
            }

            $endpoint = "/services/apexrest/vlocity_cmt/v2/cpq/carts/{$cartId}/products"
                      . "?hierarchy=0&pagesize=200&includeAttachment=false&includeAttributes=true"
                      . "&priceListId={$priceListId}";

            Log::info('[CPQ RootProducts] GET products', ['url' => $baseUrl . $endpoint]);

            $response = Http::withToken($token)->acceptJson()->asJson()->timeout(60)->get($baseUrl . $endpoint);

            Log::info('[CPQ RootProducts] Products response', ['status' => $response->status()]);

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
