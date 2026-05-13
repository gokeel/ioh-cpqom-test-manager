<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductTestRun;
use App\Models\ProductTestSuite;
use App\Models\RuntimeState;
use App\Models\SalesforceUser;
use App\Models\TestModule;
use App\Models\TestParameter;
use App\Services\CpqApiTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        $moduleIds = $productTestSuite->modules->pluck('id');

        // Latest run per module for the current user — groupBy then first() so the DESC order is respected
        $latestRuns = ProductTestRun::where('product_test_suite_id', $productTestSuite->id)
            ->whereIn('test_module_id', $moduleIds)
            ->where('user_id', auth()->id())
            ->orderByDesc('started_at')
            ->get()
            ->groupBy('test_module_id')
            ->map(fn($runs) => $runs->first());

        $salesforceUrl = rtrim(env('SALESFORCE_URL', ''), '/');

        $totalModules  = $productTestSuite->modules->count();
        $passedCount   = $latestRuns->where('validation_status', 'passed')->count();
        $notPassedCount = $latestRuns->where('validation_status', 'not_passed')->count();
        $errorCount    = $latestRuns->whereIn('status', ['error', 'aborted'])->where('validation_status', null)->count();
        $runningCount  = $latestRuns->where('status', 'running')->count();
        $notRunCount   = $totalModules - $latestRuns->count();

        return view('product-test-suites.show', compact(
            'productTestSuite', 'latestRuns', 'salesforceUrl',
            'totalModules', 'passedCount', 'notPassedCount', 'errorCount', 'runningCount', 'notRunCount'
        ));
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
        $testModule->load('spec');

        if (! $testModule->spec) {
            return response()->json(['error' => 'No spec assigned to this module.'], 422);
        }

        // 1. Write productCode into tc_quote TestParameter
        $param = TestParameter::firstOrNew([
            'module_id'    => $testModule->id,
            'test_case_id' => 'tc_quote',
        ]);
        $param->parameters = array_merge($param->parameters ?? [], [
            'productCode' => $productTestSuite->product->product_code,
        ]);
        $param->save();

        // 2. Create a run record
        $run = ProductTestRun::create([
            'product_test_suite_id' => $productTestSuite->id,
            'test_module_id'        => $testModule->id,
            'user_id'               => auth()->id(),
            'status'                => 'running',
            'started_at'            => Carbon::now(),
        ]);

        // 3. Call automation runner — runner responds immediately with { status: "running" },
        //    spec executes async and writes back to product_test_runs directly via DB.
        $runnerUrl = rtrim(env('AUTOMATION_RUNNER_URL', 'http://localhost:3333'), '/') . '/run';

        try {
            $payload = [
                'run_id'  => $run->id,
                'modules' => [$testModule->spec->runner_key],
            ];
            Log::info('ProductTestSuite runModule → runner', ['url' => $runnerUrl, 'payload' => $payload]);

            $response = Http::timeout(60)->post($runnerUrl, $payload);

            $body = $response->json() ?? $response->body();

            if ($response->successful()) {
                // Runner accepted — Playwright spec is running async, it will update the DB on finish
                $run->update([
                    'runner_response' => is_array($body) ? $body : ['raw' => $body],
                ]);

                return response()->json([
                    'run_id'    => $run->id,
                    'status'    => 'running',
                    'runner_id' => is_array($body) ? ($body['runId'] ?? null) : null,
                ]);
            }

            $log = 'HTTP ' . $response->status() . ': ' . (is_string($body) ? $body : json_encode($body));
            $run->update([
                'status'          => 'error',
                'runner_response' => is_array($body) ? $body : ['raw' => $body],
                'log'             => $log,
                'finished_at'     => Carbon::now(),
            ]);

            return response()->json([
                'run_id' => $run->id,
                'status' => 'error',
                'error'  => $log,
            ], $response->status());

        } catch (\Exception $e) {
            $run->update([
                'status'      => 'error',
                'log'         => $e->getMessage(),
                'finished_at' => Carbon::now(),
            ]);

            return response()->json([
                'run_id' => $run->id,
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 502);
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
