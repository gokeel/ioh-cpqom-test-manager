<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductTestSuite;
use App\Models\RuntimeState;
use App\Models\SalesforceUser;
use App\Models\TestModule;
use App\Models\TestParameter;
use App\Services\CpqApiTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProductTestSuiteController extends Controller
{
    public function index()
    {
        $suites = ProductTestSuite::with('product')
            ->withCount('modules')
            ->join('products', 'products.id', '=', 'product_test_suites.product_id')
            ->orderBy('products.product_offer')
            ->select('product_test_suites.*')
            ->get();

        $productLines = $suites->pluck('product.product_line')->unique()->sort()->values();

        return view('product-test-suites.index', compact('suites', 'productLines'));
    }

    public function create()
    {
        $productLines = Product::distinct()->orderBy('product_line')->pluck('product_line');
        $modules      = TestModule::whereNotNull('spec_id')->orderBy('display_name')->get();

        return view('product-test-suites.create', compact('productLines', 'modules'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'product_id'     => 'required|exists:products,id',
            'module_ids'     => 'nullable|array',
            'module_ids.*'   => 'exists:test_modules,id',
            'sequence_order' => 'nullable|array',
            'sequence_order.*' => 'integer|min:0',
        ]);

        $suite = ProductTestSuite::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'product_id'  => $data['product_id'],
        ]);

        $this->syncModules($suite, $data['module_ids'] ?? [], $data['sequence_order'] ?? []);

        return redirect()->route('product-test-suites.show', $suite)
            ->with('success', 'Product test suite created.');
    }

    public function show(ProductTestSuite $productTestSuite)
    {
        $productTestSuite->load(['product', 'modules']);

        // Annotate each module with whether it has a tc_quote parameter
        $moduleIds = $productTestSuite->modules->pluck('id');
        $tcQuoteExists = TestParameter::whereIn('module_id', $moduleIds)
            ->where('test_case_id', 'tc_quote')
            ->pluck('module_id')
            ->flip();

        return view('product-test-suites.show', compact('productTestSuite', 'tcQuoteExists'));
    }

    public function edit(ProductTestSuite $productTestSuite)
    {
        $productTestSuite->load(['product', 'modules']);
        $productLines = Product::distinct()->orderBy('product_line')->pluck('product_line');
        $modules      = TestModule::whereNotNull('spec_id')->orderBy('display_name')->get();

        return view('product-test-suites.edit', compact('productTestSuite', 'productLines', 'modules'));
    }

    public function update(Request $request, ProductTestSuite $productTestSuite)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'product_id'       => 'required|exists:products,id',
            'module_ids'       => 'nullable|array',
            'module_ids.*'     => 'exists:test_modules,id',
            'sequence_order'   => 'nullable|array',
            'sequence_order.*' => 'integer|min:0',
        ]);

        $productTestSuite->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'product_id'  => $data['product_id'],
        ]);

        $this->syncModules($productTestSuite, $data['module_ids'] ?? [], $data['sequence_order'] ?? []);

        return redirect()->route('product-test-suites.show', $productTestSuite)
            ->with('success', 'Product test suite updated.');
    }

    public function destroy(ProductTestSuite $productTestSuite)
    {
        $productTestSuite->delete();

        return redirect()->route('product-test-suites.index')
            ->with('success', 'Product test suite deleted.');
    }

    public function run(ProductTestSuite $productTestSuite, CpqApiTestService $service)
    {
        $productTestSuite->load(['product', 'modules']);
        $productCode = $productTestSuite->product->product_code;

        // Build a runtime state lookup for fallback values (e.g. opportunityId → opportunity_id)
        $runtimeMap = RuntimeState::all()->pluck('state_value', 'state_key');

        $results = [];

        foreach ($productTestSuite->modules as $module) {
            // Step 1: upsert the tc_quote TestParameter with the productCode
            $param = TestParameter::firstOrNew([
                'module_id'    => $module->id,
                'test_case_id' => 'tc_quote',
            ]);
            $existing   = $param->parameters ?? [];
            $param->parameters = array_merge($existing, ['productCode' => $productCode]);
            $param->save();

            // Step 2: build CpqApiTestService config from the saved parameters
            $p = $param->parameters;

            // Resolve opportunity_id: prefer explicit parameter, fall back to RuntimeState
            $opportunityId = $p['opportunity_id']
                ?? $p['opportunityId']
                ?? $runtimeMap->get('opportunityId')
                ?? null;

            if (! $opportunityId) {
                $results[] = [
                    'module'  => $module->display_name,
                    'status'  => 'skipped',
                    'reason'  => 'opportunity_id not set in tc_quote parameters or RuntimeState',
                    'steps'   => [],
                    'assertions' => [],
                ];
                continue;
            }

            $config = [
                'opportunity_id'       => $opportunityId,
                'quote_name'           => $p['quote_name'] ?? ($productTestSuite->name . ' - ' . $module->display_name),
                'price_list_id'        => $p['price_list_id'] ?? $runtimeMap->get('priceListId') ?? '',
                'currency'             => $p['currency'] ?? $runtimeMap->get('currency') ?? 'IDR',
                'record_type_id'       => $p['record_type_id'] ?? $runtimeMap->get('recordTypeId') ?? '',
                'product_count'        => (int) ($p['product_count'] ?? 1),
                'selection_mode'       => $p['selection_mode'] ?? 'random',
                'randomize_attributes' => (bool) ($p['randomize_attributes'] ?? false),
                'override_pricing'     => (bool) ($p['override_pricing'] ?? false),
                'otc_override'         => $p['otc_override'] ?? null,
                'rc_override'          => $p['rc_override'] ?? null,
            ];

            $sfUser = isset($p['persona_id'])
                ? SalesforceUser::find($p['persona_id'])
                : null;

            try {
                $result = $service->runQuoteTest($config, $sfUser);
                $results[] = [
                    'module'     => $module->display_name,
                    'status'     => $result['success'] ? 'passed' : 'failed',
                    'steps'      => $result['steps'] ?? [],
                    'assertions' => $result['assertions'] ?? [],
                    'error'      => $result['error'] ?? null,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'module'     => $module->display_name,
                    'status'     => 'error',
                    'error'      => $e->getMessage(),
                    'steps'      => [],
                    'assertions' => [],
                ];
            }
        }

        return response()->json(['results' => $results]);
    }

    public function runModule(ProductTestSuite $productTestSuite, TestModule $testModule)
    {
        $productTestSuite->load('product');

        // 1. Write productCode into tc_quote TestParameter
        $param = TestParameter::firstOrNew([
            'module_id'    => $testModule->id,
            'test_case_id' => 'tc_quote',
        ]);
        $param->parameters = array_merge($param->parameters ?? [], [
            'productCode' => $productTestSuite->product->product_code,
        ]);
        $param->save();

        // 2. Run via automation runner (same as TestSuiteController@runSpec)
        $testModule->load('spec');

        if (! $testModule->spec) {
            return response()->json(['error' => 'No spec assigned to this module.'], 422);
        }

        $runnerUrl = rtrim(env('AUTOMATION_RUNNER_URL', 'http://localhost:3333'), '/') . '/run';

        try {
            $response = Http::timeout(60)->post($runnerUrl, [
                'modules' => [$testModule->spec->runner_key],
            ]);

            return response()->json([
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ], $response->successful() ? 200 : $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    public function products(Request $request)
    {
        $products = Product::where('product_line', $request->input('product_line'))
            ->orderBy('product_offer')
            ->get(['id', 'product_offer', 'product_code']);

        return response()->json($products);
    }

    private function syncModules(ProductTestSuite $suite, array $moduleIds, array $sequenceOrder): void
    {
        $pivot = [];
        foreach ($moduleIds as $index => $moduleId) {
            $pivot[$moduleId] = ['sequence_order' => $sequenceOrder[$moduleId] ?? $index];
        }
        $suite->modules()->sync($pivot);
    }
}
