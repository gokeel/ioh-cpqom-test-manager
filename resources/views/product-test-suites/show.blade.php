<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-brand-dark leading-tight">
                {{ $productTestSuite->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('product-test-suites.edit', $productTestSuite) }}"
                    class="text-xs px-3 py-1.5 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition">
                    Edit
                </a>
                <a href="{{ route('product-test-suites.index') }}"
                    class="text-xs px-3 py-1.5 border border-gray-200 text-gray-400 rounded-lg hover:bg-gray-50 transition">
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen"
        x-data="{
            suiteRunning: false,

            statusColor(status) {
                return {
                    passed:  'bg-green-100 text-green-700',
                    failed:  'bg-red-100 text-red-700',
                    skipped: 'bg-yellow-100 text-yellow-700',
                    error:   'bg-red-100 text-red-700',
                }[status] ?? 'bg-gray-100 text-gray-600';
            },

            async runSuite() {
                this.suiteRunning = true;
                // Dispatch run event to all module rows in sequence
                const rows = document.querySelectorAll('[data-module-row]');
                for (const row of rows) {
                    await row.__x.$data.runModule();
                }
                this.suiteRunning = false;
            }
        }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Suite header card --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs py-0.5 px-2 rounded-full bg-brand-teal/10 text-brand-teal font-semibold">
                                {{ $productTestSuite->product->product_line }}
                            </span>
                            <span class="text-xs font-mono px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full">
                                {{ $productTestSuite->product->product_code }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 font-medium">{{ $productTestSuite->product->product_offer }}</p>
                        @if($productTestSuite->description)
                            <p class="text-xs text-gray-400">{{ $productTestSuite->description }}</p>
                        @endif
                    </div>

                    <button @click="runSuite()" :disabled="suiteRunning"
                        class="px-5 py-2 bg-brand-teal text-white rounded-lg text-sm font-semibold hover:opacity-90 disabled:opacity-60 transition flex items-center gap-2">
                        <span x-show="!suiteRunning">Run Suite</span>
                        <span x-show="suiteRunning" class="flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Running…
                        </span>
                    </button>
                </div>
            </div>

            {{-- Module sequence table --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-brand-dark">Module Sequence</h3>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left w-10">#</th>
                            <th class="px-5 py-3 text-left">Module</th>
                            <th class="px-5 py-3 text-left">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    @forelse($productTestSuite->modules as $module)
                    <tbody
                        data-module-row
                        x-data="{
                            status: 'idle',
                            result: null,
                            open: false,
                            async runModule() {
                                this.status = 'running';
                                this.result = null;
                                this.open = false;
                                try {
                                    const res = await fetch('{{ route('product-test-suites.run-module', [$productTestSuite, $module]) }}', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                            'Accept': 'application/json',
                                        }
                                    });
                                    this.result = await res.json();
                                    this.status = this.result.status;
                                    this.open = this.status !== 'passed';
                                } catch(e) {
                                    this.status = 'error';
                                    this.result = { status: 'error', error: e.message, steps: [], assertions: [] };
                                    this.open = true;
                                }
                            }
                        }"
                        class="divide-y divide-gray-50 border-t border-gray-100">

                        {{-- Main row --}}
                        <tr :class="status === 'running' ? 'bg-brand-teal/5' : ''">
                            <td class="px-5 py-3 text-xs text-gray-400 font-mono tabular-nums">{{ $module->pivot->sequence_order }}</td>
                            <td class="px-5 py-3 font-semibold text-brand-dark text-sm">{{ $module->display_name }}</td>
                            <td class="px-5 py-3">
                                {{-- idle --}}
                                <span x-show="status === 'idle'" class="text-xs text-gray-300 font-medium">—</span>
                                {{-- running --}}
                                <span x-show="status === 'running'" class="inline-flex items-center gap-1 text-xs text-brand-teal font-medium">
                                    <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                    Running
                                </span>
                                {{-- result badge --}}
                                <template x-if="status !== 'idle' && status !== 'running'">
                                    <button @click="open = !open"
                                        class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-semibold capitalize cursor-pointer"
                                        :class="$root.statusColor(status)">
                                        <span x-text="status"></span>
                                        <svg :class="open ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                </template>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <button @click="runModule()" :disabled="status === 'running' || suiteRunning"
                                        class="text-xs px-2.5 py-1 bg-brand-teal text-white rounded-lg font-semibold hover:opacity-90 disabled:opacity-40 transition">
                                        Run
                                    </button>
                                    <a href="{{ route('test-suite.show', $module) }}"
                                        class="text-xs text-brand-teal hover:underline font-medium">Open</a>
                                </div>
                            </td>
                        </tr>

                        {{-- Result detail row --}}
                        <tr x-show="open && result !== null" x-transition>
                            <td colspan="4" class="px-5 py-4 bg-gray-50">
                                <template x-if="result && result.error">
                                    <p class="text-xs text-red-600 font-mono bg-red-50 rounded p-2 mb-3" x-text="result.error"></p>
                                </template>

                                <template x-if="result && result.steps && result.steps.length">
                                    <div class="mb-3">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Steps</p>
                                        <div class="space-y-1">
                                            <template x-for="(step, si) in result.steps" :key="si">
                                                <div class="flex items-start gap-2 text-xs">
                                                    <span :class="step.status === 'pass' || step.status === 'ok' ? 'text-green-500' : 'text-red-500'" class="font-bold mt-0.5">
                                                        <span x-show="step.status === 'pass' || step.status === 'ok'">✓</span>
                                                        <span x-show="step.status !== 'pass' && step.status !== 'ok'">✗</span>
                                                    </span>
                                                    <div>
                                                        <span class="font-medium text-gray-700" x-text="step.label"></span>
                                                        <span x-show="step.detail" class="text-gray-400 ml-1" x-text="step.detail"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="result && result.assertions && result.assertions.length">
                                    <div>
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Assertions</p>
                                        <table class="min-w-full text-xs divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden">
                                            <thead class="bg-white text-gray-500 uppercase tracking-wide">
                                                <tr>
                                                    <th class="px-3 py-2 text-left">Check</th>
                                                    <th class="px-3 py-2 text-left">Expected</th>
                                                    <th class="px-3 py-2 text-left">Actual</th>
                                                    <th class="px-3 py-2 text-center">Pass</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50 bg-white">
                                                <template x-for="(a, ai) in result.assertions" :key="ai">
                                                    <tr>
                                                        <td class="px-3 py-2 font-medium text-gray-700" x-text="a.label"></td>
                                                        <td class="px-3 py-2 font-mono text-gray-500" x-text="a.expected"></td>
                                                        <td class="px-3 py-2 font-mono text-gray-700" x-text="a.actual"></td>
                                                        <td class="px-3 py-2 text-center">
                                                            <span :class="a.pass ? 'text-green-500' : 'text-red-500'" class="font-bold" x-text="a.pass ? '✓' : '✗'"></span>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>
                            </td>
                        </tr>

                    </tbody>
                    @empty
                    <tbody>
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-gray-400 text-sm">
                                No modules in this suite.
                                <a href="{{ route('product-test-suites.edit', $productTestSuite) }}" class="text-brand-teal hover:underline ml-1">Add modules</a>
                            </td>
                        </tr>
                    </tbody>
                    @endforelse
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
