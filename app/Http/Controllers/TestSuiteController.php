<?php

namespace App\Http\Controllers;

use App\Models\TestModule;
use App\Models\TestParameter;
use App\Models\TestSpec;
use App\Models\RuntimeState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TestSuiteController extends Controller
{
    // ── Modules index ──────────────────────────────────────────────────────

    public function index()
    {
        $modules      = TestModule::withCount(['testParameters' => fn($q) => $q->where('user_id', auth()->id())])->orderBy('display_name')->get();
        $categories   = $modules->pluck('category')->filter()->unique()->sort()->values();
        $runtimeState = RuntimeState::where('user_id', auth()->id())->orderBy('state_key')->get();
        return view('test-suite.index', compact('modules', 'categories', 'runtimeState'));
    }

    // ── Module detail (test cases) ─────────────────────────────────────────

    public function show(TestModule $testModule)
    {
        $testModule->load([
            'testParameters' => fn($q) => $q->where('user_id', auth()->id()),
            'spec',
        ]);
        $specs = TestSpec::orderBy('display_name')->get();
        return view('test-suite.show', compact('testModule', 'specs'));
    }

    // ── Increment counter ──────────────────────────────────────────────────

    public function incrementCounter(TestModule $testModule)
    {
        $testModule->incrementCounter();
        return back()->with('success', "Counter for '{$testModule->display_name}' incremented to {$testModule->counter}.");
    }

    // ── Reset counter ──────────────────────────────────────────────────────

    public function resetCounter(TestModule $testModule)
    {
        $testModule->update(['counter' => 0]);
        return back()->with('success', "Counter for '{$testModule->display_name}' reset to 0.");
    }

    // ── Update test parameters (JSONB) ─────────────────────────────────────

    public function updateParameter(Request $request, TestParameter $testParameter)
    {
        abort_if($testParameter->user_id !== auth()->id(), 403);

        $request->validate([
            'parameters'   => 'nullable|array',
            'new_keys'     => 'nullable|array',
            'new_values'   => 'nullable|array',
            'notes'        => 'nullable|string|max:500',
        ]);

        // Existing parameters submitted as parameters[key] = value
        $parameters = $request->parameters ?? [];

        // Append newly added key-value rows
        foreach (($request->new_keys ?? []) as $i => $key) {
            $key = trim($key);
            if ($key !== '') {
                $parameters[$key] = $request->new_values[$i] ?? '';
            }
        }

        // Cast numeric strings back to int/float
        $parameters = array_map(function ($val) {
            if (is_string($val) && is_numeric(trim($val)) && trim($val) !== '') {
                return str_contains($val, '.') ? (float) $val : (int) $val;
            }
            return $val;
        }, $parameters);

        $testParameter->update([
            'parameters' => $parameters,
            'notes'      => $request->notes,
        ]);

        return back()->with('success', "Parameters for {$testParameter->test_case_id} updated.");
    }

    // ── Add a new test case ─────────────────────────────────────────────────

    public function storeParameter(Request $request, TestModule $testModule)
    {
        $request->validate([
            'test_case_id' => 'required|string|max:20',
            'parameters'   => 'required|string',
            'notes'        => 'nullable|string|max:500',
        ]);

        $decoded = json_decode($request->parameters, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['parameters' => 'Invalid JSON: ' . json_last_error_msg()]);
        }

        $testModule->testParameters()->updateOrCreate(
            ['test_case_id' => strtolower($request->test_case_id), 'user_id' => auth()->id()],
            ['parameters' => $decoded, 'notes' => $request->notes]
        );

        return back()->with('success', "Test case {$request->test_case_id} saved.");
    }

    // ── Delete a test case ──────────────────────────────────────────────────

    public function destroyParameter(TestParameter $testParameter)
    {
        abort_if($testParameter->user_id !== auth()->id(), 403);

        $id = $testParameter->test_case_id;
        $testParameter->delete();
        return back()->with('success', "Test case {$id} deleted.");
    }

    // ── Update runtime state ────────────────────────────────────────────────

    public function updateRuntimeState(Request $request, RuntimeState $runtimeState)
    {
        abort_if($runtimeState->user_id !== auth()->id(), 403);

        $request->validate([
            'state_value' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:500',
        ]);

        $runtimeState->update([
            'state_value'     => $request->state_value,
            'description'     => $request->description,
            'last_updated_at' => now(),
        ]);

        return back()->with('success', "State '{$runtimeState->state_key}' updated.");
    }

    // ── Add runtime state key ───────────────────────────────────────────────

    public function storeRuntimeState(Request $request)
    {
        $userId = auth()->id();

        $request->validate([
            'state_key'   => [
                'required', 'string', 'max:100',
                \Illuminate\Validation\Rule::unique('runtime_state')->where('user_id', $userId),
            ],
            'state_value' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:500',
        ]);

        RuntimeState::create([
            'user_id'         => $userId,
            'state_key'       => $request->state_key,
            'state_value'     => $request->state_value,
            'description'     => $request->description,
            'last_updated_at' => now(),
        ]);

        return back()->with('success', "State key '{$request->state_key}' created.");
    }

    // ── Assign spec to module ───────────────────────────────────────────────

    public function updateSpec(Request $request, TestModule $testModule)
    {
        $request->validate(['spec_id' => 'nullable|exists:test_specs,id']);
        $testModule->update(['spec_id' => $request->spec_id ?: null]);
        return back()->with('success', 'Spec updated.');
    }

    // ── Proxy run to automation runner ─────────────────────────────────────

    public function runSpec(TestModule $testModule)
    {
        $testModule->load('spec');

        if (!$testModule->spec) {
            return response()->json(['error' => 'No spec assigned to this module.'], 422);
        }

        $runnerUrl = rtrim(env('AUTOMATION_RUNNER_URL', 'http://localhost:3333'), '/') . '/run';

        try {
            $response = Http::timeout(60)->post($runnerUrl, [
                'modules' => [$testModule->spec->runner_key],
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ], $response->successful() ? 200 : $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    // ── Delete runtime state key ────────────────────────────────────────────

    public function destroyRuntimeState(RuntimeState $runtimeState)
    {
        abort_if($runtimeState->user_id !== auth()->id(), 403);

        $key = $runtimeState->state_key;
        $runtimeState->delete();
        return back()->with('success', "State '{$key}' deleted.");
    }
}
