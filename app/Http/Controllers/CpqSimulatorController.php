<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
            $response = $makeRequest($client, $method, $url, $payload);
            
            if ($response->status() === 401 && $sfUser) {
                // Refresh and retry
                $token = $salesforceService->refreshUserToken($sfUser);
                $client = Http::withToken($token)->acceptJson()->asJson()->timeout(60);
                $response = $makeRequest($client, $method, $url, $payload);
            }

            return response()->json([
                'status' => $response->status(),
                'headers' => $response->headers(),
                'data' => $response->json(),
                'body' => $response->body()
            ], $response->status() >= 500 ? 500 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rootProducts(Request $request, SalesforceService $salesforceService)
    {
        $request->validate([
            'cart_id'       => 'required|string',
            'price_list_id' => 'required|string',
            'persona_id'    => 'nullable|exists:salesforce_users,id',
        ]);

        $cartId      = $request->input('cart_id');
        $priceListId = $request->input('price_list_id');
        $personaId   = $request->input('persona_id');
        $cacheKey    = "cpq_root_products_{$cartId}_{$priceListId}";

        $products = Cache::remember($cacheKey, 86400, function () use (
            $cartId, $priceListId, $personaId, $salesforceService
        ) {
            $sfUser = null;
            if ($personaId) {
                $sfUser = SalesforceUser::find($personaId);
                $token  = $salesforceService->getAccessTokenForUser($sfUser);
            } else {
                $token = $salesforceService->getAccessToken();
            }

            $baseUrl  = rtrim(env('SALESFORCE_URL', ''), '/');
            $endpoint = "/services/apexrest/vlocity_cmt/v2/cpq/carts/{$cartId}/products"
                      . "?hierarchy=0&pagesize=200&includeAttachment=false&includeAttributes=true"
                      . "&priceListId={$priceListId}";

            $response = Http::withToken($token)->acceptJson()->asJson()->timeout(60)->get($baseUrl . $endpoint);

            if ($response->status() === 401 && $sfUser) {
                $token    = $salesforceService->refreshUserToken($sfUser);
                $response = Http::withToken($token)->acceptJson()->asJson()->timeout(60)->get($baseUrl . $endpoint);
            }

            return $response->json();
        });

        return response()->json($products);
    }
}
