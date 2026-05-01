<?php

namespace App\Services;

use App\Models\SalesforceUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CpqApiTestService
{
    public function __construct(private SalesforceService $sf) {}

    /**
     * Run the full CPQ quote API test and return a structured result.
     *
     * @param array $config  Keys: opportunity_id, quote_name, price_list_id, currency,
     *                       record_type_id, product_count, randomize_attributes,
     *                       override_pricing, otc_override, rc_override
     */
    public function runQuoteTest(array $config, ?SalesforceUser $sfUser = null): array
    {
        $steps      = [];
        $assertions = [];

        set_time_limit(300); // 5 min — adding many products + attribute patches can take a while

        try {
            // ── Auth ─────────────────────────────────────────────────────
            $token   = $sfUser
                ? $this->sf->getAccessTokenForUser($sfUser)
                : $this->sf->getAccessToken();
            $baseUrl = rtrim(env('SALESFORCE_URL', ''), '/');

            $call = function (string $method, string $endpoint, array $payload = []) use (&$token, $baseUrl, $sfUser) {
                $url    = $baseUrl . '/' . ltrim($endpoint, '/');
                $client = Http::withToken($token)->acceptJson()->asJson()->timeout(60);

                $res = match (strtoupper($method)) {
                    'GET'   => $client->get($url, $payload),
                    'POST'  => $client->post($url, $payload),
                    'PATCH' => $client->patch($url, $payload),
                    default => throw new \InvalidArgumentException("Unsupported method: $method"),
                };

                // Refresh once on 401 for persona users
                if ($res->status() === 401 && $sfUser) {
                    $token  = $this->sf->refreshUserToken($sfUser);
                    $client = Http::withToken($token)->acceptJson()->asJson()->timeout(60);
                    $res    = match (strtoupper($method)) {
                        'GET'   => $client->get($url, $payload),
                        'POST'  => $client->post($url, $payload),
                        'PATCH' => $client->patch($url, $payload),
                    };
                }

                return $res;
            };

            // ── Step 1: Create Quote ──────────────────────────────────────
            $createRes = $call('POST', '/services/apexrest/vlocity_cmt/v2/carts', [
                'methodName'  => 'createCart',
                'objectType'  => 'Quote',
                'subaction'   => 'createQuote',
                'fields'      => 'Id,Name',
                'filters'     => 'Account.vlocity_cmt__Status__c:Inactive_Active_Pending',
                'inputFields' => [
                    ['OpportunityId'                => $config['opportunity_id']],
                    ['Name'                         => $config['quote_name']],
                    ['vlocity_cmt__PriceListId__c'  => $config['price_list_id']],
                    ['CurrencyIsoCode'              => $config['currency']],
                    ['RecordTypeId'                 => $config['record_type_id']],
                ],
            ]);

            if (!$createRes->successful()) {
                return $this->fail('Create Quote failed', $createRes->json() ?? $createRes->body(), $steps);
            }

            $cartData = $createRes->json();
            $cartId   = $cartData['cartId']
                ?? ($cartData['records'][0]['Id'] ?? null)
                ?? $cartData['Id']
                ?? null;

            if (!$cartId) {
                return $this->fail('Create Quote: could not extract cartId', $cartData, $steps);
            }
            $steps[] = ['label' => 'Create Quote', 'status' => 'ok', 'detail' => "cartId: {$cartId}"];

            // ── Step 2+3: Determine which products to add ────────────────
            $quantity      = max(1, (int) ($config['product_quantity'] ?? 1));
            $selectionMode = $config['selection_mode'] ?? 'random';

            if ($selectionMode === 'manual') {
                $manualProducts = $config['selected_products'] ?? [];
                $selected       = array_map(fn($p) => [
                    'Id'   => $p['id'] ?? $p['Id'],
                    'Name' => $p['name'] ?? $p['Name'] ?? '',
                ], $manualProducts);
                $expectedCount = count($selected);
                $steps[] = ['label' => 'Product Selection (Manual)', 'status' => 'ok',
                    'detail' => $expectedCount . ' product(s): ' . implode(', ', array_column($selected, 'Name'))];
            } else {
                $priceListId = $config['price_list_id'];
                $cacheKey    = "cpq_root_products_{$priceListId}";

                // Use the shared priceList-scoped cache; populate from this cart if it's a miss.
                $allProducts = Cache::remember($cacheKey, 86400, function () use ($call, $cartId, $priceListId) {
                    $res = $call('GET', "/services/apexrest/vlocity_cmt/v2/cpq/carts/{$cartId}/products"
                        . "?hierarchy=0&pagesize=200&includeAttachment=false&includeAttributes=true"
                        . "&priceListId={$priceListId}");
                    return array_map(fn($p) => [
                        'Id'   => is_array($p['Id'] ?? null) ? ($p['Id']['value'] ?? '') : ($p['Id'] ?? ''),
                        'Name' => $p['Product2']['Name'] ?? $p['Name'] ?? '',
                    ], $res->json()['records'] ?? []);
                });

                if (empty($allProducts)) {
                    return $this->fail('No root products found for this price list', null, $steps);
                }
                $steps[] = ['label' => 'Fetch Root Products', 'status' => 'ok', 'detail' => count($allProducts) . ' available'];

                $count    = min((int) $config['product_count'], count($allProducts));
                $keys     = (array) array_rand($allProducts, $count);
                $selected = array_map(fn($k) => $allProducts[$k], $keys);
                $expectedCount = $count;
                $steps[] = ['label' => 'Select Random Products', 'status' => 'ok',
                    'detail' => $count . ' product(s): ' . implode(', ', array_column($selected, 'Name'))];
            }

            // ── Step 4: Add each product to cart ─────────────────────────
            foreach ($selected as $prod) {
                $itemId  = is_array($prod['Id']) ? $prod['Id']['value'] : $prod['Id'];
                $addRes  = $call('POST', "/services/apexrest/vlocity_cmt/v2/cpq/carts/{$cartId}/items", [
                    'cartId'   => $cartId,
                    'price'    => true,
                    'validate' => true,
                    'items'    => [['itemId' => $itemId, 'quantity' => $quantity]],
                ]);

                $prodName = $prod['Product2']['Name'] ?? $prod['Name'] ?? $itemId;
                if (!$addRes->successful()) {
                    $steps[] = ['label' => "Add Product: {$prodName}", 'status' => 'error', 'detail' => $addRes->body()];
                } else {
                    $steps[] = ['label' => "Add Product: {$prodName} (qty: {$quantity})", 'status' => 'ok', 'detail' => ''];
                }
            }

            // ── Step 5: Load Cart Items (for attributes + pricing) ────────
            $itemsRes = $call('GET', "/services/apexrest/vlocity_cmt/v2/cpq/carts/{$cartId}/items"
                . '?includeAttachment=true&hierarchy=true');

            if (!$itemsRes->successful()) {
                return $this->fail('Load Cart Items failed', $itemsRes->json() ?? $itemsRes->body(), $steps);
            }

            $rootLineItems = $itemsRes->json()['records'] ?? [];
            $steps[] = ['label' => 'Load Cart Items', 'status' => 'ok', 'detail' => count($rootLineItems) . ' root item(s) in cart'];

            // Flatten all child items from bundles
            $childItems = [];
            foreach ($rootLineItems as $root) {
                foreach ($root['lineItems']['records'] ?? [] as $child) {
                    $child['_rootName'] = $root['Name'] ?? '';
                    $childItems[]       = $child;
                }
            }

            // ── Step 6a: Randomize Attributes (if enabled) ────────────────
            if (!empty($config['randomize_attributes']) && !empty($childItems)) {
                $attrPatched = 0;
                foreach ($childItems as $child) {
                    $childId = $this->extractId($child);
                    $attrs   = $this->extractRandomAttributes($child);
                    if (empty($attrs)) {
                        continue;
                    }

                    $patchRes = $call('PATCH', "/services/data/v66.0/sobjects/QuoteLineItem/{$childId}", [
                        'vlocity_cmt__AttributeSelectedValues__c' => json_encode($attrs),
                    ]);
                    $attrPatched++;
                    $steps[] = ['label' => "Randomize Attrs: {$childId}", 'status' => $patchRes->successful() ? 'ok' : 'error',
                        'detail' => json_encode($attrs)];
                }
                if ($attrPatched === 0) {
                    $steps[] = ['label' => 'Randomize Attributes', 'status' => 'skip', 'detail' => 'No eligible dropdown attributes found'];
                }
            }

            // ── Step 6b: Override Pricing (if enabled) ────────────────────
            if (!empty($config['override_pricing']) && !empty($childItems)) {
                $otc = isset($config['otc_override']) ? (float) $config['otc_override'] : null;
                $rc  = isset($config['rc_override'])  ? (float) $config['rc_override']  : null;

                if ($otc !== null || $rc !== null) {
                    foreach ($childItems as $child) {
                        $childId  = $this->extractId($child);
                        $patchPayload = [];
                        if ($otc !== null) $patchPayload['AdditionalOneTimeCharge__c']    = $otc;
                        if ($rc  !== null) $patchPayload['AdditionalRecurringCharge__c']  = $rc;

                        $patchRes = $call('PATCH', "/services/data/v66.0/sobjects/QuoteLineItem/{$childId}", $patchPayload);
                        $steps[]  = ['label' => "Override Pricing: {$childId}", 'status' => $patchRes->successful() ? 'ok' : 'error',
                            'detail' => json_encode($patchPayload)];
                    }
                }
            }

            // ── Step 7: Recalculate ───────────────────────────────────────
            $recalcRes = $call('GET', "/services/apexrest/vlocity_cmt/v2/cpq/carts/{$cartId}/price?price=true");
            $steps[] = ['label' => 'Recalculate Quote', 'status' => $recalcRes->successful() ? 'ok' : 'error', 'detail' => ''];

            // ── Step 8: Fetch Quote Total ─────────────────────────────────
            $quoteRes   = $call('GET', "/services/data/v66.0/sobjects/Quote/{$cartId}?fields=TotalPrice,GrandTotal,vlocity_cmt__QuoteTotal__c");
            $quoteData  = $quoteRes->json() ?? [];
            $quoteTotal = $quoteData['vlocity_cmt__QuoteTotal__c']
                ?? $quoteData['GrandTotal']
                ?? $quoteData['TotalPrice']
                ?? 0;

            $steps[] = ['label' => 'Fetch Quote Total', 'status' => $quoteRes->successful() ? 'ok' : 'error',
                'detail' => "Total: {$quoteTotal}"];

            // ── Assertions ────────────────────────────────────────────────
            $assertions[] = [
                'label'    => 'Products added to cart',
                'expected' => $expectedCount,
                'actual'   => count($rootLineItems),
                'pass'     => count($rootLineItems) === $expectedCount,
            ];
            $assertions[] = [
                'label'    => 'Quote Total is positive',
                'expected' => '> 0',
                'actual'   => $quoteTotal,
                'pass'     => (float) $quoteTotal > 0,
            ];

            $allPassed = collect($assertions)->every(fn($a) => $a['pass']);

            return [
                'success'    => $allPassed,
                'cartId'     => $cartId,
                'quoteTotal' => $quoteTotal,
                'products'   => array_map(fn($r) => [
                    'id'       => $this->extractId($r),
                    'name'     => $r['Name'] ?? 'unknown',
                    'children' => array_map(fn($c) => [
                        'id'   => $this->extractId($c),
                        'name' => $c['Name'] ?? 'unknown',
                    ], $r['lineItems']['records'] ?? []),
                ], $rootLineItems),
                'assertions' => $assertions,
                'steps'      => $steps,
                'error'      => null,
            ];

        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), null, $steps);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function fail(string $message, mixed $detail, array $steps): array
    {
        return [
            'success'    => false,
            'cartId'     => null,
            'quoteTotal' => null,
            'products'   => [],
            'assertions' => [],
            'steps'      => $steps,
            'error'      => $message,
            'detail'     => $detail,
        ];
    }

    private function extractId(array $item): string
    {
        if (isset($item['Id'])) {
            return is_array($item['Id']) ? ($item['Id']['value'] ?? '') : $item['Id'];
        }
        return $item['id'] ?? $item['itemId'] ?? '';
    }

    /**
     * Walk attributeCategories and pick a random valid value for each eligible dropdown.
     * Returns [ code => randomValue, ... ]
     */
    private function extractRandomAttributes(array $item): array
    {
        $attrs = [];
        foreach ($item['attributeCategories']['records'] ?? [] as $cat) {
            foreach ($cat['productAttributes']['records'] ?? [] as $attr) {
                if (!empty($attr['disabled']) || !empty($attr['hidden'])) {
                    continue;
                }
                $key    = $attr['code'] ?? $attr['label'] ?? null;
                $values = $attr['values'] ?? [];

                if ($attr['inputType'] === 'dropdown' && !empty($values) && $key) {
                    $pick       = $values[array_rand($values)];
                    $attrs[$key] = is_array($pick) ? ($pick['value'] ?? '') : $pick;
                }
            }
        }
        return $attrs;
    }
}
