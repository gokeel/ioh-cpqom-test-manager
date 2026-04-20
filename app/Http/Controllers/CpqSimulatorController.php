<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
}
