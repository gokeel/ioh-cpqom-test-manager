<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-brand-dark leading-tight flex items-center gap-2">
            <a href="{{ route('test-suite.index') }}" class="text-gray-400 hover:text-brand-dark transition-colors">Test Suite</a>
            <span class="text-gray-300">/</span>
            <a href="{{ route('test-suite.show', $testModule) }}" class="text-gray-400 hover:text-brand-dark transition-colors">{{ $testModule->display_name }}</a>
            <span class="text-gray-300">/</span>
            API Test
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen" x-data="apiTest()" x-cloak>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Page title -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-xs font-mono text-gray-400 bg-gray-100 px-2 py-0.5 rounded">{{ $testModule->module_key }}</span>
                        <h3 class="text-xl font-bold text-brand-dark mt-1">{{ $testModule->display_name }} — Quote API Test</h3>
                        <p class="text-sm text-gray-500 mt-1">Creates a real quote in Salesforce, randomly adds products, optionally randomises attributes and overrides pricing, then asserts on the results.</p>
                    </div>
                    <a href="{{ route('test-suite.show', $testModule) }}"
                       class="text-xs text-gray-400 hover:text-brand-teal transition-colors whitespace-nowrap">← Back to suite</a>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">

                <!-- ── Left: Configuration ────────────────────────────── -->
                <div class="space-y-4">

                    <!-- Persona -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4">
                        <h4 class="text-sm font-bold text-brand-dark mb-3">Salesforce Persona</h4>
                        <select x-model="selectedPersonaId"
                                class="block w-full border-gray-300 focus:border-brand-teal focus:ring-brand-teal rounded-md shadow-sm text-sm">
                            <option value="">System Default</option>
                            @foreach($sfUsers as $sfu)
                                <option value="{{ $sfu->id }}" data-username="{{ $sfu->username }}">
                                    {{ $sfu->label }} ({{ $sfu->username }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Opportunity -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                        <div class="px-5 py-4 flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-brand-dark">Opportunity</h4>
                            <button @click="fetchOpportunities()" :disabled="isLoading"
                                    class="text-xs px-3 py-1.5 bg-brand-teal text-white rounded-lg font-semibold hover:opacity-90 transition disabled:opacity-50">
                                Load Opportunities
                            </button>
                        </div>

                        <template x-if="selectedOpportunityId">
                            <div class="mb-3 text-xs text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                                ✓ Selected: <span class="font-mono font-bold" x-text="selectedOpportunityId"></span>
                            </div>
                        </template>

                        <template x-if="opportunities.length > 0">
                            <div>
                                <div class="overflow-x-auto border border-gray-100 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-100 text-xs">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">Name</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">Stage</th>
                                                <th class="px-3 py-2 text-right font-medium text-gray-600">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="opp in paginatedOpportunities()" :key="opp.Id">
                                                <tr :class="selectedOpportunityId === opp.Id ? 'bg-brand-teal bg-opacity-10' : 'hover:bg-gray-50'">
                                                    <td class="px-3 py-2 font-medium text-gray-900" x-text="opp.Name"></td>
                                                    <td class="px-3 py-2 text-gray-500" x-text="opp.StageName"></td>
                                                    <td class="px-3 py-2 text-right">
                                                        <button @click="selectedOpportunityId = opp.Id"
                                                                class="px-2 py-0.5 bg-brand-teal text-white rounded text-xs">
                                                            <span x-text="selectedOpportunityId === opp.Id ? '✓' : 'Select'"></span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Pagination -->
                                <div class="flex items-center justify-between mt-2 px-1">
                                    <span class="text-xs text-gray-400"
                                        x-text="`${Math.min((oppPage-1)*oppPageSize+1, opportunities.length)}–${Math.min(oppPage*oppPageSize, opportunities.length)} of ${opportunities.length}`">
                                    </span>
                                    <div class="flex gap-1.5">
                                        <button @click="oppPage--" :disabled="oppPage <= 1"
                                                class="px-2 py-0.5 text-xs rounded border border-gray-300 disabled:opacity-40 hover:bg-gray-100">← Prev</button>
                                        <button @click="oppPage++" :disabled="oppPage >= Math.ceil(opportunities.length / oppPageSize)"
                                                class="px-2 py-0.5 text-xs rounded border border-gray-300 disabled:opacity-40 hover:bg-gray-100">Next →</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Quote Config -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <h4 class="text-sm font-bold text-brand-dark">Quote Configuration</h4>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div class="px-5 py-4">
                                <x-input-label value="Quote Name" />
                                <x-text-input type="text" x-model="quoteName" class="mt-2 block w-full text-sm" />
                            </div>
                            <div class="px-5 py-4">
                                <x-input-label value="Price List" />
                                <div class="mt-2 relative">
                                    <select x-model="priceListId"
                                            :disabled="priceLists.length === 0"
                                            class="block w-full border-gray-300 focus:border-brand-teal focus:ring-brand-teal rounded-md shadow-sm text-sm disabled:opacity-50">
                                        <option value="" x-text="priceLists.length === 0 ? 'Loading…' : '— Select Price List —'"></option>
                                        <template x-for="pl in priceLists" :key="pl.Id">
                                            <option :value="pl.Id" x-text="pl.Name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0;" class="divide-x divide-gray-100">
                                <div class="px-5 py-4">
                                    <x-input-label value="Currency" />
                                    <x-text-input type="text" x-model="currency" class="mt-2 block w-full text-sm" />
                                </div>
                                <div class="px-5 py-4">
                                    <x-input-label value="Record Type ID" />
                                    <div class="mt-2 relative">
                                        <input type="text" :value="recordTypeId" readonly
                                               class="block w-full border-gray-200 bg-gray-50 rounded-md shadow-sm text-sm font-mono text-xs text-gray-600 px-3 py-2 cursor-default" />
                                        <span x-show="!recordTypeId"
                                              class="absolute inset-y-0 left-3 flex items-center text-xs text-gray-400 pointer-events-none">
                                            Loading…
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Test Options -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <h4 class="text-sm font-bold text-brand-dark">Test Options</h4>
                        </div>
                        <div class="divide-y divide-gray-100">

                            {{-- Quantity --}}
                            <div class="px-5 py-4">
                                <x-input-label value="Quantity per Product" />
                                <x-text-input type="number" x-model.number="productQuantity" min="1" max="100" class="mt-2 block w-32 text-sm" />
                            </div>

                            {{-- Product Selection --}}
                            <div class="px-5 py-4">
                                <x-input-label value="Product Selection" />
                                <div class="flex gap-6 mt-2 mb-4">
                                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="radio" x-model="selectionMode" value="random"
                                               class="border-gray-300 text-brand-teal focus:ring-brand-teal" />
                                        Random
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="radio" x-model="selectionMode" value="manual"
                                               class="border-gray-300 text-brand-teal focus:ring-brand-teal" />
                                        Select Specific Products
                                    </label>
                                </div>

                                {{-- Random mode --}}
                                <div x-show="selectionMode === 'random'">
                                    <x-input-label value="Number of Random Products (1–20)" />
                                    <x-text-input type="number" x-model.number="productCount" min="1" max="20" class="mt-2 block w-32 text-sm" />
                                </div>

                                {{-- Manual mode --}}
                                <div x-show="selectionMode === 'manual'">
                                    <div class="flex items-center gap-2 mb-3">
                                        <button @click="fetchAvailableProducts(availableProducts.length > 0)"
                                                :disabled="isLoading || !priceListId || !selectedOpportunityId"
                                                :title="!priceListId || !selectedOpportunityId ? 'Select a Price List and Opportunity first' : (availableProducts.length > 0 ? 'Force reload from Salesforce (clears cache)' : '')"
                                                class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg border border-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <span x-text="availableProducts.length > 0 ? '↺ Reload' : 'Load Products'"></span>
                                        </button>
                                        <span class="text-xs text-gray-400" x-show="availableProducts.length > 0"
                                              x-text="`${availableProducts.length} available`"></span>
                                        <span class="ml-auto text-xs font-semibold text-brand-teal" x-show="selectedProducts.length > 0"
                                              x-text="`${selectedProducts.length} selected`"></span>
                                    </div>

                                    <template x-if="availableProducts.length > 0">
                                        <div>
                                            <input type="text" x-model="productSearch"
                                                   placeholder="Type to search products…"
                                                   class="block w-full border-gray-300 focus:border-brand-teal focus:ring-brand-teal rounded-md shadow-sm text-sm mb-2 px-3 py-2" />
                                            <div class="border border-gray-200 rounded-lg overflow-y-auto" style="max-height: 15rem;">
                                                <template x-for="prod in filteredProducts()" :key="prod.Id">
                                                    <label class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0"
                                                           :class="isProductSelected(prod.Id) ? 'bg-teal-50' : ''">
                                                        <input type="checkbox"
                                                               :checked="isProductSelected(prod.Id)"
                                                               @change="toggleProduct(prod)"
                                                               class="rounded border-gray-300 text-brand-teal focus:ring-brand-teal shrink-0" />
                                                        <span class="text-sm text-gray-700" x-text="prod.Name"></span>
                                                    </label>
                                                </template>
                                                <template x-if="filteredProducts().length === 0">
                                                    <div class="px-3 py-6 text-xs text-gray-400 text-center">No products match your search.</div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="availableProducts.length === 0">
                                        <p class="text-xs text-gray-400">Click "Load Products" to browse and select.</p>
                                    </template>
                                </div>
                            </div>

                            {{-- Randomize attributes --}}
                            <div class="px-5 py-4">
                                <label class="flex items-center gap-3 cursor-pointer text-sm text-gray-700">
                                    <input type="checkbox" x-model="randomizeAttributes"
                                           class="rounded border-gray-300 text-brand-teal focus:ring-brand-teal" />
                                    Randomize attributes on bundle child items
                                </label>
                            </div>

                            {{-- Override pricing --}}
                            <div class="px-5 py-4">
                                <label class="flex items-center gap-3 cursor-pointer text-sm text-gray-700">
                                    <input type="checkbox" x-model="overridePricing"
                                           class="rounded border-gray-300 text-brand-teal focus:ring-brand-teal" />
                                    Override OTC / RC pricing
                                </label>
                                <template x-if="overridePricing">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;" class="mt-4">
                                        <div>
                                            <x-input-label value="OTC Override" />
                                            <div class="flex gap-1.5 mt-2">
                                                <x-text-input type="number" x-model.number="otcOverride" min="0" step="1000000"
                                                              class="flex-1 text-sm" placeholder="e.g. 25000000" />
                                                <button @click="fakeOtc()" title="15M – 50M in 1M steps"
                                                        class="px-2.5 py-2 bg-gray-100 hover:bg-brand-teal hover:text-white text-gray-600 rounded-lg border border-gray-200 transition text-base leading-none">
                                                    🎲
                                                </button>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-400">15M – 50M · 1M steps</p>
                                        </div>
                                        <div>
                                            <x-input-label value="RC Override" />
                                            <div class="flex gap-1.5 mt-2">
                                                <x-text-input type="number" x-model.number="rcOverride" min="0" step="500000"
                                                              class="flex-1 text-sm" placeholder="e.g. 7500000" />
                                                <button @click="fakeRc()" title="5M – 10M in 500K steps"
                                                        class="px-2.5 py-2 bg-gray-100 hover:bg-brand-teal hover:text-white text-gray-600 rounded-lg border border-gray-200 transition text-base leading-none">
                                                    🎲
                                                </button>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-400">5M – 10M · 500K steps</p>
                                        </div>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </div>

                    <!-- Run Button -->
                    <button @click="runTest()"
                            :disabled="running || !selectedOpportunityId || !priceListId || (selectionMode === 'manual' && selectedProducts.length === 0)"
                            class="w-full py-3 bg-brand-teal text-white font-bold rounded-xl shadow hover:opacity-90 transition disabled:opacity-40 flex items-center justify-center gap-2">
                        <svg x-show="!running" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6.3 2.84A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.27l9.34-5.89a1.5 1.5 0 000-2.54L6.3 2.84z"/>
                        </svg>
                        <svg x-show="running" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        <span x-text="running ? 'Running test…' : 'Run API Test'"></span>
                    </button>

                </div>

                <!-- ── Right: Results ──────────────────────────────────── -->
                <div class="space-y-4">

                    <!-- Placeholder when no result yet -->
                    <template x-if="!result && !running">
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 flex flex-col items-center justify-center text-center text-gray-400">
                            <svg class="w-12 h-12 mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-sm">Configure and run the test to see results here.</p>
                        </div>
                    </template>

                    <template x-if="running && !result">
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 flex flex-col items-center justify-center text-center text-gray-400">
                            <svg class="w-10 h-10 mb-3 animate-spin text-brand-teal" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            <p class="text-sm">Executing CPQ API flow…</p>
                        </div>
                    </template>

                    <template x-if="result">
                        <!-- Overall status banner -->
                        <div :class="result.error ? 'bg-red-50 border-red-300 text-red-800' : (result.success ? 'bg-green-50 border-green-300 text-green-800' : 'bg-yellow-50 border-yellow-300 text-yellow-800')"
                             class="rounded-2xl border p-4 flex items-center gap-3">
                            <span class="text-2xl" x-text="result.error ? '✗' : (result.success ? '✓' : '⚠')"></span>
                            <div>
                                <div class="font-bold text-sm" x-text="result.error ? 'Test Failed — Error' : (result.success ? 'All Assertions Passed' : 'Some Assertions Failed')"></div>
                                <template x-if="result.cartId">
                                    <div class="text-xs mt-0.5">
                                        Cart ID: <span class="font-mono" x-text="result.cartId"></span>
                                        <span x-show="result.quoteTotal" class="ml-3">
                                            Quote Total: <strong x-text="result.quoteTotal?.toLocaleString()"></strong>
                                        </span>
                                    </div>
                                </template>
                                <template x-if="result.error">
                                    <div class="text-xs mt-0.5 font-mono" x-text="result.error"></div>
                                </template>
                            </div>
                        </div>

                        <!-- Assertions -->
                        <template x-if="result.assertions && result.assertions.length > 0">
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                <div class="px-5 py-3 border-b border-gray-100 font-bold text-sm text-brand-dark">Assertions</div>
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                                        <tr>
                                            <th class="px-5 py-2 text-left">Check</th>
                                            <th class="px-5 py-2 text-center">Expected</th>
                                            <th class="px-5 py-2 text-center">Actual</th>
                                            <th class="px-5 py-2 text-center">Result</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="a in result.assertions" :key="a.label">
                                            <tr>
                                                <td class="px-5 py-3 font-medium text-gray-800" x-text="a.label"></td>
                                                <td class="px-5 py-3 text-center font-mono text-xs text-gray-600" x-text="a.expected"></td>
                                                <td class="px-5 py-3 text-center font-mono text-xs text-gray-600" x-text="a.actual"></td>
                                                <td class="px-5 py-3 text-center">
                                                    <span :class="a.pass ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                                          class="inline-block px-2 py-0.5 rounded text-xs font-bold"
                                                          x-text="a.pass ? 'PASS' : 'FAIL'">
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <!-- Products in cart -->
                        <template x-if="result.products && result.products.length > 0">
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                <div class="px-5 py-3 border-b border-gray-100 font-bold text-sm text-brand-dark">
                                    Products in Cart (<span x-text="result.products.length"></span>)
                                </div>
                                <div class="divide-y divide-gray-100">
                                    <template x-for="prod in result.products" :key="prod.id">
                                        <div class="px-5 py-3">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-900" x-text="prod.name"></span>
                                                <span class="text-xs font-mono text-gray-400" x-text="prod.id"></span>
                                            </div>
                                            <template x-if="prod.children && prod.children.length > 0">
                                                <div class="mt-1 ml-4 space-y-0.5">
                                                    <template x-for="child in prod.children" :key="child.id">
                                                        <div class="text-xs text-gray-500">
                                                            └ <span x-text="child.name"></span>
                                                            <span class="font-mono text-gray-400 ml-1" x-text="child.id"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Execution Steps Log -->
                        <template x-if="result.steps && result.steps.length > 0">
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                <div class="px-5 py-3 border-b border-gray-100 font-bold text-sm text-brand-dark">Execution Log</div>
                                <div class="divide-y divide-gray-50">
                                    <template x-for="(step, i) in result.steps" :key="i">
                                        <div class="px-5 py-2 flex items-start gap-3 text-xs">
                                            <span :class="{
                                                'text-green-600': step.status === 'ok',
                                                'text-red-600': step.status === 'error',
                                                'text-gray-400': step.status === 'skip'
                                            }" class="mt-0.5 font-bold shrink-0"
                                            x-text="step.status === 'ok' ? '✓' : (step.status === 'error' ? '✗' : '–')">
                                            </span>
                                            <div>
                                                <span class="font-medium text-gray-700" x-text="step.label"></span>
                                                <template x-if="step.detail">
                                                    <span class="ml-2 text-gray-400 font-mono" x-text="step.detail"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Raw JSON toggle -->
                        <div x-data="{ showRaw: false }" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <button @click="showRaw = !showRaw"
                                    class="w-full px-5 py-3 text-left text-xs text-gray-400 hover:text-brand-teal transition flex items-center justify-between">
                                <span>Raw JSON response</span>
                                <span x-text="showRaw ? '▲' : '▼'"></span>
                            </button>
                            <div x-show="showRaw" class="px-5 pb-4">
                                <pre class="text-xs font-mono bg-gray-50 rounded-lg p-4 overflow-x-auto text-gray-700"
                                     x-text="JSON.stringify(result, null, 2)"></pre>
                            </div>
                        </div>

                    </template>

                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('apiTest', () => ({
                selectedPersonaId: '',
                selectedOpportunityId: '',
                opportunities: [],
                oppPage: 1,
                oppPageSize: 10,
                priceLists: [],
                isLoading: false,

                quoteName: 'API Test — {{ $testModule->display_name }}',
                priceListId: '',
                currency: 'IDR',
                recordTypeId: '',

                selectionMode: 'random',
                productCount: 3,
                productQuantity: 1,
                availableProducts: [],
                productSearch: '',
                selectedProducts: [],

                randomizeAttributes: true,
                overridePricing: false,
                otcOverride: null,
                rcOverride: null,

                running: false,
                result: null,

                async init() {
                    await Promise.all([
                        this.fetchPriceLists(),
                        this.fetchRecordTypeId(),
                    ]);
                },

                paginatedOpportunities() {
                    const start = (this.oppPage - 1) * this.oppPageSize;
                    return this.opportunities.slice(start, start + this.oppPageSize);
                },

                async fetchOpportunities() {
                    this.oppPage = 1;
                    this.isLoading = true;
                    try {
                        let query = `SELECT Id, Name, StageName, Owner.Name FROM Opportunity WHERE (StageName = 'Scoping' OR StageName = 'Quoting') ORDER BY CreatedDate DESC LIMIT 100`;
                        const res = await axios.post('/cpq-simulator/proxy', {
                            method: 'GET',
                            endpoint: `/services/data/v66.0/query?q=${encodeURIComponent(query)}`,
                            persona_id: this.selectedPersonaId || null,
                            payload: null,
                        }, { timeout: 30000 });
                        if (res.data?.data?.records) {
                            this.opportunities = res.data.data.records;
                        } else {
                            alert('Failed to load opportunities.');
                        }
                    } catch (e) {
                        alert(`Error: ${e.message}`);
                    } finally {
                        this.isLoading = false;
                    }
                },

                async fetchRecordTypeId() {
                    try {
                        const q = `SELECT Id FROM RecordType WHERE DeveloperName = 'WorkingCart' AND SobjectType = 'Quote' LIMIT 1`;
                        const res = await axios.post('/cpq-simulator/proxy', {
                            method: 'GET',
                            endpoint: `/services/data/v66.0/query?q=${encodeURIComponent(q)}`,
                            persona_id: null,
                            payload: null,
                        }, { timeout: 15000 });
                        const id = res.data?.data?.records?.[0]?.Id;
                        if (id) this.recordTypeId = id;
                    } catch (e) {
                        console.error('Failed to load Record Type ID:', e);
                    }
                },

                filteredProducts() {
                    if (!this.productSearch) return this.availableProducts;
                    const q = this.productSearch.toLowerCase();
                    return this.availableProducts.filter(p => p.Name.toLowerCase().includes(q));
                },

                isProductSelected(id) {
                    return this.selectedProducts.some(p => p.id === id);
                },

                toggleProduct(product) {
                    const idx = this.selectedProducts.findIndex(p => p.id === product.Id);
                    if (idx >= 0) this.selectedProducts.splice(idx, 1);
                    else this.selectedProducts.push({ id: product.Id, name: product.Name });
                },

                async fetchAvailableProducts(forceRefresh = false) {
                    if (!this.priceListId || !this.selectedOpportunityId) {
                        alert('Select a Price List and an Opportunity before loading products.');
                        return;
                    }
                    this.isLoading = true;
                    try {
                        const res = await axios.get('{{ route('cpq-simulator.root-products') }}', {
                            params: {
                                price_list_id:  this.priceListId,
                                opportunity_id: this.selectedOpportunityId,
                                currency:       this.currency,
                                record_type_id: this.recordTypeId || null,
                                persona_id:     this.selectedPersonaId || null,
                                force_refresh:  forceRefresh ? 1 : 0,
                            },
                            timeout: 60000,
                        });
                        const records = res.data?.records ?? [];
                        this.availableProducts = records.map(p => ({
                            Id:   p.Id?.value ?? p.Id,
                            Name: p.Product2?.Name ?? p.Name ?? '',
                        }));
                        this.selectedProducts = [];
                    } catch (e) {
                        console.error('Failed to load products:', e);
                        alert(`Failed to load products: ${e.response?.data?.message ?? e.message}`);
                    } finally {
                        this.isLoading = false;
                    }
                },

                fakeOtc() {
                    // 15M to 50M in 1M increments (36 possible values)
                    this.otcOverride = (Math.floor(Math.random() * 36) + 15) * 1_000_000;
                },

                fakeRc() {
                    // 5M to 10M in 500K increments (11 possible values)
                    this.rcOverride = (Math.floor(Math.random() * 11) + 10) * 500_000;
                },

                async fetchPriceLists() {
                    if (this.priceLists.length > 0) return;
                    this.isLoading = true;
                    try {
                        const q = `SELECT Id, Name FROM vlocity_cmt__PriceList__c WHERE vlocity_cmt__IsActive__c = true LIMIT 50`;
                        const res = await axios.post('/cpq-simulator/proxy', {
                            method: 'GET',
                            endpoint: `/services/data/v66.0/query?q=${encodeURIComponent(q)}`,
                            persona_id: this.selectedPersonaId || null,
                            payload: null,
                        }, { timeout: 30000 });
                        if (res.data?.data?.records) {
                            this.priceLists = res.data.data.records;
                            const preferred = this.priceLists.find(p => p.Name.toLowerCase().includes('b2b pricelist'));
                            this.priceListId = preferred ? preferred.Id : (this.priceLists[0]?.Id ?? '');
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isLoading = false;
                    }
                },

                async runTest() {
                    this.running = true;
                    this.result = null;
                    try {
                        const payload = {
                            opportunity_id:       this.selectedOpportunityId,
                            quote_name:           this.quoteName,
                            price_list_id:        this.priceListId,
                            currency:             this.currency,
                            record_type_id:       this.recordTypeId,
                            product_quantity:     this.productQuantity,
                            selection_mode:       this.selectionMode,
                            product_count:        this.selectionMode === 'random' ? this.productCount : null,
                            selected_products:    this.selectionMode === 'manual' ? this.selectedProducts : null,
                            randomize_attributes: this.randomizeAttributes ? 1 : 0,
                            override_pricing:     this.overridePricing ? 1 : 0,
                            otc_override:         this.overridePricing ? this.otcOverride : null,
                            rc_override:          this.overridePricing ? this.rcOverride  : null,
                            persona_id:           this.selectedPersonaId || null,
                        };

                        const res = await axios.post('{{ route('test-suite.api-test.run', $testModule) }}', payload, {
                            timeout: 300000,
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                        });
                        this.result = res.data;
                    } catch (e) {
                        this.result = { error: e.response?.data?.message ?? e.message, success: false, steps: [], assertions: [] };
                    } finally {
                        this.running = false;
                    }
                },
            }));
        });
    </script>

</x-app-layout>
