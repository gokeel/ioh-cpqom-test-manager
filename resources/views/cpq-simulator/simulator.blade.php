<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-brand-dark leading-tight">
            {{ __('CPQ Simulator') }}
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8">

<div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-6" x-data="cpqSimulator()">
    <div class="border-b border-gray-200 pb-4 mb-6">
        <h3 class="text-2xl font-bold text-brand-dark">Salesforce Vlocity CPQ Simulator</h3>
        <p class="text-gray-600 mt-1">Simulate a sequential CPQ quote flow using the API proxy.</p>
    </div>

    <div class="mb-6">
        <x-input-label for="sf_persona" value="Select Salesforce Persona" />
        <select id="sf_persona" x-model="selectedPersonaId"
            class="mt-1 block w-md border-gray-300 focus:border-brand-teal focus:ring-brand-teal rounded-md shadow-sm">
            <option value="">System Default</option>
            @foreach($sfUsers as $sfu)
                <option value="{{ $sfu->id }}" data-username="{{ $sfu->username }}">{{ $sfu->label }} ({{ $sfu->username }})
                </option>
            @endforeach
        </select>
    </div>

    <!-- Opportunities + Quote Selection side by side -->
    <div class="mb-4" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">

        <!-- Step 0: Get Opportunities -->
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-bold text-lg">Initialization: Select Opportunity</h4>
                <button @click="fetchOpportunities()" :disabled="isLoading"
                    class="px-3 py-1 bg-brand-teal text-white rounded shadow text-sm disabled:opacity-50">
                    Load Opportunities
                </button>
            </div>

            <template x-if="opportunities.length > 0">
                <div class="mt-4">
                    <div class="overflow-x-auto bg-white border border-gray-200 rounded-lg shadow-sm">
                        <table class="min-w-full divide-y-2 divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 text-left">Opportunity Name</th>
                                    <th class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 text-left">Stage</th>
                                    <th class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 text-left">Owner</th>
                                    <th class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="opp in paginatedOpportunities()" :key="opp.Id">
                                    <tr class="transition-colors"
                                        :class="selectedOpportunityId === opp.Id ? 'bg-brand-teal bg-opacity-10' : 'hover:bg-gray-50'">
                                        <td class="px-4 py-3 font-medium text-gray-900 text-sm" x-text="opp.Name"></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-500 text-sm" x-text="opp.StageName"></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-500 text-sm" x-text="opp.Owner?.Name"></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <button @click="selectOpportunity(opp.Id)"
                                                class="px-3 py-1 bg-brand-teal text-white rounded shadow text-xs">
                                                <span x-text="selectedOpportunityId === opp.Id ? 'Selected' : 'Select'"></span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination controls -->
                    <div class="flex items-center justify-between mt-3 px-1">
                        <span class="text-xs text-gray-500"
                            x-text="`Showing ${Math.min((oppPage - 1) * oppPageSize + 1, opportunities.length)}–${Math.min(oppPage * oppPageSize, opportunities.length)} of ${opportunities.length}`">
                        </span>
                        <div class="flex items-center gap-2">
                            <button @click="oppPage--" :disabled="oppPage <= 1"
                                class="px-2 py-1 text-xs rounded border border-gray-300 disabled:opacity-40 hover:bg-gray-100">
                                ← Prev
                            </button>
                            <span class="text-xs text-gray-600"
                                x-text="`${oppPage} / ${Math.ceil(opportunities.length / oppPageSize)}`">
                            </span>
                            <button @click="oppPage++" :disabled="oppPage >= Math.ceil(opportunities.length / oppPageSize)"
                                class="px-2 py-1 text-xs rounded border border-gray-300 disabled:opacity-40 hover:bg-gray-100">
                                Next →
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Quote Selection/Creation Panel -->
        <div class="border border-gray-200 rounded-lg bg-white shadow-sm">
            <!-- Placeholder when no opportunity selected -->
            <template x-if="!selectedOpportunityId">
                <div class="flex items-center justify-center h-full py-16 text-sm text-gray-400">
                    Select an opportunity to view quotes.
                </div>
            </template>

            <template x-if="selectedOpportunityId">
                <div>
                    <!-- Panel Header -->
                    <div class="flex justify-between items-center px-5 py-4 border-b border-gray-200">
                        <div>
                            <h4 class="font-bold text-lg text-brand-dark">Quote Selection / Creation</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Select an existing quote or create a new one</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <template x-if="cartId">
                                <div
                                    class="flex items-center gap-2 text-xs bg-green-50 border border-green-200 text-green-700 rounded-md px-3 py-1.5">
                                    <span>✓ Active Cart:</span>
                                    <span x-text="cartId" class="font-mono font-bold"></span>
                                </div>
                            </template>
                            <button @click="showNewQuoteModal = true" :disabled="!selectedOpportunityId"
                                class="px-4 py-2 bg-brand-teal text-white rounded-lg shadow text-sm font-medium flex items-center gap-2 hover:opacity-90 transition disabled:opacity-50">
                                <span>＋</span> New Quote
                            </button>
                        </div>
                    </div>

                    <!-- Quotes Table -->
                    <div class="p-5">
                        <div x-show="fetchingQuotes" class="text-sm text-gray-500 animate-pulse py-4 text-center">Fetching quotes...</div>
                        <template x-if="!fetchingQuotes && quotes.length === 0">
                            <div class="text-sm text-gray-400 py-6 text-center">No quotes found for this opportunity.</div>
                        </template>
                        <template x-if="!fetchingQuotes && quotes.length > 0">
                            <div class="overflow-x-auto border border-gray-100 rounded-lg">
                                <table class="min-w-full divide-y-2 divide-gray-100 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 text-left">Quote Name</th>
                                            <th class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 text-left">ID</th>
                                            <th class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 text-left">Status</th>
                                            <th class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="q in quotes" :key="q.Id">
                                            <tr class="transition-colors"
                                                :class="cartId === q.Id ? 'bg-brand-teal bg-opacity-10' : 'hover:bg-gray-50'">
                                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900" x-text="q.Name"></td>
                                                <td class="whitespace-nowrap px-4 py-3 text-gray-500 font-mono text-xs" x-text="q.Id"></td>
                                                <td class="whitespace-nowrap px-4 py-3 text-gray-500" x-text="q.Status"></td>
                                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                                    <button @click="selectQuote(q)"
                                                        :class="cartId === q.Id ? 'bg-green-600' : 'bg-brand-teal'"
                                                        class="px-3 py-1 text-white rounded shadow text-xs transition">
                                                        <span x-text="cartId === q.Id ? '✓ Selected' : 'Use Quote'"></span>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

    </div>

    <!-- New Quote Modal -->
    <div x-show="showNewQuoteModal" x-cloak
        style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; display: flex; align-items: center; justify-content: center;">
        <!-- Backdrop -->
        <div @click="showNewQuoteModal = false"
            style="position: absolute; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(3px);"></div>
        <!-- Modal Box -->
        <div
            style="position: relative; background: white; border-radius: 12px; box-shadow: 0 25px 60px rgba(0,0,0,0.35); width: 90%; max-width: 520px; padding: 2rem; z-index: 10000; margin: auto;">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="font-bold text-xl text-brand-dark">Create New Quote</h3>
                    <p class="text-sm text-gray-500 mt-1">Fill in the details below to create a new CPQ quote</p>
                </div>
                <button @click="showNewQuoteModal = false"
                    class="text-gray-400 hover:text-gray-600 text-2xl leading-none transition">&times;</button>
            </div>

            <div class="grid grid-cols-1 gap-4 mb-6">
                <div>
                    <x-input-label value="Quote Name" />
                    <x-text-input type="text" x-model="quoteConfig.Name" class="mt-1 block w-full text-sm" />
                </div>
                <div>
                    <x-input-label value="Price List" />
                    <select x-model="quoteConfig.PriceListId"
                        class="mt-1 block w-full border-gray-300 focus:border-brand-teal focus:ring-brand-teal rounded-md shadow-sm text-sm">
                        <option value="">-- Select Price List --</option>
                        <template x-for="pl in priceLists" :key="pl.Id">
                            <option :value="pl.Id" x-text="pl.Name"></option>
                        </template>
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <x-input-label value="Currency" />
                        <x-text-input type="text" x-model="quoteConfig.CurrencyIsoCode"
                            class="mt-1 block w-full text-sm" />
                    </div>
                    <div>
                        <x-input-label value="Record Type ID" />
                        <x-text-input type="text" x-model="quoteConfig.RecordTypeId"
                            class="mt-1 block w-full text-sm" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button @click="showNewQuoteModal = false"
                    class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button @click="createQuote()" :disabled="isLoading"
                    class="px-5 py-2 bg-brand-teal text-white rounded-lg shadow text-sm font-medium hover:opacity-90 transition disabled:opacity-50 flex items-center gap-2">
                    <span x-show="isLoading" class="animate-spin">⟳</span>
                    <span>Create Quote</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Cart Line Items (shown after quote selected) -->
    <div class="mb-4 border border-gray-200 rounded-lg bg-white shadow-sm" x-show="cartId">
        <div class="flex justify-between items-center px-5 py-4 border-b border-gray-200">
            <div>
                <h4 class="font-bold text-lg text-brand-dark">Cart Line Items</h4>
                <p class="text-xs text-gray-500 mt-0.5">Existing products in this quote</p>
            </div>
            <div class="flex items-center gap-3">
                <template x-if="loadingCartItems">
                    <span class="text-xs text-gray-400 animate-pulse">Loading items...</span>
                </template>
                <button @click="showAddProductModal = true; getRootProducts()" :disabled="isLoading"
                    class="px-4 py-2 bg-brand-teal text-white rounded-lg shadow text-sm font-medium flex items-center gap-2 hover:opacity-90 transition disabled:opacity-50">
                    <span>＋</span> Add Product
                </button>
            </div>
        </div>
        <div class="p-5">
            <template x-if="!loadingCartItems && rootLineItems.length === 0">
                <div class="text-sm text-gray-400 py-6 text-center">No line items found. Add a product to get started.
                </div>
            </template>
            <template x-if="rootLineItems.length > 0">
                <div class="space-y-4">
                    <template x-for="item in rootLineItems" :key="item.Id">
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <!-- Root item header -->
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200 cursor-pointer"
                                @click="item._expanded = !item._expanded">
                                <div class="flex items-center gap-3">
                                    <span class="text-gray-400 text-xs" x-text="item._expanded ? '▼' : '▶'"></span>
                                    <span class="font-semibold text-gray-900 text-sm"
                                        x-text="item.Name || item.Product2?.Name || '-'"></span>
                                    <span class="text-xs text-gray-400 font-mono" x-text="item.Id"></span>
                                </div>
                                <span class="text-xs text-gray-500"
                                    x-text="'Qty: ' + (item.Quantity?.value || item.Quantity || 1)"></span>
                            </div>

                            <!-- Child items -->
                            <div x-show="item._expanded" class="divide-y divide-gray-100">
                                <template x-if="!item._children || item._children.length === 0">
                                    <div class="px-6 py-4 text-sm text-gray-400 italic">No child items.</div>
                                </template>
                                <template x-for="child in (item._children || [])" :key="child.Id">
                                    <div class="px-6 py-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <div>
                                                <span class="font-medium text-brand-teal text-sm"
                                                    x-text="child.Name || '-'"></span>
                                                <span class="ml-2 text-xs text-gray-400 font-mono"
                                                    x-text="child.Id"></span>
                                            </div>
                                            <button @click="getAttributes(child.Id)"
                                                class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs transition">
                                                Load Attributes
                                            </button>
                                        </div>

                                        <!-- Attributes grid -->
                                        <template
                                            x-if="childAttributes[child.Id] && Object.keys(childAttributes[child.Id]).filter(k => k !== '_saved').length > 0">
                                            <div class="mb-4">
                                                <div class="text-xs font-semibold text-gray-500 uppercase mb-2">
                                                    Attributes</div>
                                                <div
                                                    style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;">
                                                    <template x-for="(val, key) in childAttributes[child.Id]"
                                                        :key="key">
                                                        <template x-if="key !== '_saved'">
                                                            <div>
                                                                <div class="flex items-center gap-1">
                                                                    <x-input-label
                                                                        x-text="childAttrsMeta[child.Id]?.[key]?.label" />
                                                                    <template
                                                                        x-if="childAttrsMeta[child.Id]?.[key]?.required">
                                                                        <span
                                                                            class="text-red-500 text-xs font-bold">*</span>
                                                                    </template>
                                                                </div>
                                                                <!-- Dropdown for inputType === 'dropdown' -->
                                                                <template
                                                                    x-if="childAttrsMeta[child.Id]?.[key]?.inputType === 'dropdown'">
                                                                    <select x-model="childAttributes[child.Id][key]"
                                                                        :required="childAttrsMeta[child.Id] && childAttrsMeta[child.Id][key] && childAttrsMeta[child.Id][key].required"
                                                                        class="mt-1 block w-full border-gray-300 focus:border-brand-teal focus:ring-brand-teal rounded-md shadow-sm text-xs">
                                                                        <option value="">-- None --</option>
                                                                        <template
                                                                            x-for="opt in (childAttrsMeta[child.Id]?.[key]?.values || [])"
                                                                            :key="opt.value">
                                                                            <option :value="opt.value"
                                                                                :selected="String(childAttributes[child.Id][key]) === String(opt.value)"
                                                                                x-text="opt.value"></option>
                                                                        </template>
                                                                    </select>
                                                                </template>
                                                                <!-- Text input for all other types -->
                                                                <template
                                                                    x-if="childAttrsMeta[child.Id]?.[key]?.inputType !== 'dropdown'">
                                                                    <input type="text"
                                                                        x-model="childAttributes[child.Id][key]"
                                                                        :required="childAttrsMeta[child.Id] && childAttrsMeta[child.Id][key] && childAttrsMeta[child.Id][key].required"
                                                                        class="mt-1 block w-full text-xs border-gray-300 focus:border-brand-teal focus:ring-brand-teal rounded-md shadow-sm" />
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </template>
                                                </div>
                                                <div class="flex justify-end mt-2">
                                                    <button @click="updateAttributes(child.Id)" :disabled="isLoading"
                                                        class="px-3 py-1 bg-brand-teal text-white rounded text-xs flex gap-2 items-center">
                                                        <template x-if="childAttributes[child.Id]._saved">
                                                            <span class="text-green-300">✓</span>
                                                        </template>
                                                        Save Attributes
                                                    </button>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- OTC / RC pricing -->
                                        <div class="pt-3 border-t border-gray-100">
                                            <div class="text-xs font-semibold text-gray-500 uppercase mb-2">Pricing
                                                Override</div>
                                            <div
                                                style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem; align-items: end;">
                                                <div>
                                                    <x-input-label value="OTC (One-Time Charge)" />
                                                    <x-text-input type="number" x-model="childPricing[child.Id].otc"
                                                        class="mt-1 block w-full text-sm" />
                                                </div>
                                                <div>
                                                    <x-input-label value="RC (Recurring Charge)" />
                                                    <x-text-input type="number" x-model="childPricing[child.Id].rc"
                                                        class="mt-1 block w-full text-sm" />
                                                </div>
                                                <div>
                                                    <button @click="updatePricing(child.Id)" :disabled="isLoading"
                                                        class="px-3 py-2 bg-brand-teal text-white rounded text-xs flex gap-1 items-center whitespace-nowrap">
                                                        <template x-if="childPricing[child.Id].saved">
                                                            <span class="text-green-300">✓</span>
                                                        </template>
                                                        Update &amp; Recalculate
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div x-show="showAddProductModal" x-cloak
        style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; display: flex; align-items: center; justify-content: center;">
        <div @click="showAddProductModal = false"
            style="position: absolute; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(3px);"></div>
        <div
            style="position: relative; background: white; border-radius: 12px; box-shadow: 0 25px 60px rgba(0,0,0,0.35); width: 90%; max-width: 600px; padding: 2rem; z-index: 10000; margin: auto; max-height: 85vh; overflow-y: auto;">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="font-bold text-xl text-brand-dark">Add Product to Cart</h3>
                    <p class="text-sm text-gray-500 mt-1">Select a root product from the quote's pricelist</p>
                </div>
                <button @click="showAddProductModal = false"
                    class="text-gray-400 hover:text-gray-600 text-2xl leading-none transition">&times;</button>
            </div>

            <div class="mb-5">
                <div x-show="isLoading && rootProducts.length === 0"
                    class="text-sm text-gray-400 animate-pulse py-4 text-center">Loading products...</div>
                <template x-if="rootProducts.length > 0">
                    <div>
                        <x-input-label value="Select Product" />
                        <select x-model="selectedPricebookEntryId"
                            class="mt-1 block w-full border-gray-300 focus:border-brand-teal focus:ring-brand-teal rounded-md shadow-sm text-sm">
                            <option value="">-- Choose Product --</option>
                            <template x-for="prod in rootProducts" :key="prod.Id?.value || prod.Id">
                                <option :value="prod.Id?.value || prod.Id"
                                    x-text="prod.Product2?.Name || prod.Name || prod.Id"></option>
                            </template>
                        </select>
                    </div>
                </template>
            </div>

            <div class="flex justify-end gap-3">
                <button @click="showAddProductModal = false"
                    class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button @click="addItemToCart()" :disabled="isLoading || !selectedPricebookEntryId"
                    class="px-5 py-2 bg-brand-teal text-white rounded-lg shadow text-sm font-medium hover:opacity-90 transition disabled:opacity-50 flex items-center gap-2">
                    <span x-show="isLoading" class="animate-spin">⟳</span>
                    <span>Add to Cart</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('cpqSimulator', () => ({
            selectedPersonaId: '',
            isLoading: false,

            opportunities: [],
            oppPage: 1,
            oppPageSize: 15,
            selectedOpportunityId: '',

            quotes: [],
            fetchingQuotes: false,
            showNewQuoteModal: false,

            priceLists: [],
            fetchingPriceLists: false,

            quoteConfig: {
                Name: 'Test Automation Quote API 1',
                PriceListId: '',
                CurrencyIsoCode: 'IDR',
                RecordTypeId: '012MS000000GkkxYAC' // Enterprise Quote
            },
            cartId: null,
            cartPriceListId: null,  // pricelist from selected quote

            rootLineItems: [],      // top-level line items in cart
            loadingCartItems: false,
            showAddProductModal: false,

            rootProducts: [],
            selectedPricebookEntryId: '',
            itemAdded: false,

            childItems: [],
            childAttributes: {},
            childAttrsMeta: {},
            childPricing: {},

            paginatedOpportunities() {
                const start = (this.oppPage - 1) * this.oppPageSize;
                return this.opportunities.slice(start, start + this.oppPageSize);
            },

            async executeProxy(method, endpoint, payload = null) {
                this.isLoading = true;
                try {
                    const response = await axios.post('/cpq-simulator/proxy', {
                        method: method,
                        endpoint: endpoint,
                        persona_id: this.selectedPersonaId,
                        payload: payload
                    }, {
                        timeout: 60000
                    });
                    return {
                        success: response.data.status >= 200 && response.data.status < 300,
                        status: response.data.status,
                        data: response.data.data
                    };
                } catch (error) {
                    console.error("Proxy error:", error);
                    return {
                        success: false,
                        status: error.response?.status || 500,
                        data: error.response?.data || error.message
                    };
                } finally {
                    this.isLoading = false;
                }
            },

            async selectOpportunity(oppId) {
                this.selectedOpportunityId = oppId;
                this.cartId = null;
                this.cartPriceListId = null;
                this.quotes = [];
                this.rootLineItems = [];
                this.childItems = [];
                this.fetchingQuotes = true;

                const q = `SELECT Id, Name, Status, vlocity_cmt__PriceListId__c FROM Quote WHERE OpportunityId = '${oppId}' AND vlocity_cmt__ParentQuoteId__c = null ORDER BY CreatedDate DESC LIMIT 20`;
                const res = await this.executeProxy('GET', `/services/data/v66.0/query?q=${encodeURIComponent(q)}`);

                if (res.success && res.data.records) {
                    this.quotes = res.data.records;
                } else {
                    console.error("Failed to load quotes.");
                }
                this.fetchingQuotes = false;
                this.fetchPriceLists();
            },

            async selectQuote(q) {
                this.cartId = q.Id;
                this.cartPriceListId = q.vlocity_cmt__PriceListId__c || null;
                this.rootLineItems = [];
                this.childItems = [];
                this.itemAdded = false;
                this.selectedPricebookEntryId = '';
                this.rootProducts = [];
                await this.loadRootLineItems();
            },

            async loadRootLineItems() {
                this.loadingCartItems = true;
                const res = await this.executeProxy('GET', `/services/apexrest/vlocity_cmt/v2/cpq/carts/${this.cartId}/items?includeAttachment=true&hierarchy=true`);
                if (res.success && res.data.records) {
                    const getItemId = (obj) => {
                        if (!obj) return null;
                        if (typeof obj.Id === 'string' && obj.Id.length > 0) return obj.Id;
                        if (typeof obj.id === 'string' && obj.id.length > 0) return obj.id;
                        if (typeof obj.itemId === 'string' && obj.itemId.length > 0) return obj.itemId;
                        if (obj.Id && obj.Id.value) return obj.Id.value;
                        return `local_${Math.random().toString(36).substr(2, 9)}`;
                    };

                    // Build root items with embedded children
                    const newPricing = {};
                    const newAttrs = {};

                    const processChildren = (childRecords) => {
                        return childRecords.map(c => {
                            c.Id = getItemId(c);
                            newPricing[c.Id] = newPricing[c.Id] || { otc: c.vlocity_cmt__OneTimeCharge__c.value, rc: c.vlocity_cmt__RecurringCharge__c.value, saved: false };
                            newAttrs[c.Id] = newAttrs[c.Id] || { _saved: false };
                            return c;
                        });
                    };

                    this.rootLineItems = res.data.records.map(r => {
                        r.Id = getItemId(r);
                        r._expanded = false;
                        r._children = (r.lineItems?.records) ? processChildren(r.lineItems.records) : [];
                        return r;
                    });

                    // Also sync flat childItems list for compatibility
                    this.childItems = this.rootLineItems.flatMap(r => r._children || []);
                    this.childPricing = newPricing;
                    this.childAttributes = newAttrs;
                }
                this.loadingCartItems = false;
            },

            async fetchPriceLists() {
                if (this.priceLists.length > 0) return;
                this.fetchingPriceLists = true;
                const q = `SELECT Id, Name FROM vlocity_cmt__PriceList__c WHERE vlocity_cmt__IsActive__c = true LIMIT 50`;
                const res = await this.executeProxy('GET', `/services/data/v66.0/query?q=${encodeURIComponent(q)}`);
                if (res.success && res.data.records) {
                    this.priceLists = res.data.records;
                    // Try to default-select 'IDR B2B Pricelist', fall back to first item
                    const preferred = this.priceLists.find(pl => pl.Name.toLowerCase().includes('idr b2b'));
                    if (!this.quoteConfig.PriceListId) {
                        this.quoteConfig.PriceListId = preferred ? preferred.Id : this.priceLists[0].Id;
                    }
                } else {
                    console.error("Failed to load price lists.", res);
                }
                this.fetchingPriceLists = false;
            },

            async fetchOpportunities() {
                this.oppPage = 1;
                let query = `SELECT Id, Name, StageName, Owner.Name FROM Opportunity WHERE (StageName = 'Scoping' OR StageName = 'Quoting')`;

                const selectEl = document.getElementById('sf_persona');
                if (selectEl && selectEl.selectedIndex > 0) {
                    const username = selectEl.options[selectEl.selectedIndex].getAttribute('data-username');
                    if (username) {
                        query += ` AND Owner.Username = '${username}'`;
                    }
                }

                query += ` ORDER BY CreatedDate DESC LIMIT 100`;

                const res = await this.executeProxy('GET', `/services/data/v66.0/query?q=${encodeURIComponent(query)}`);
                if (res.success && res.data.records) {
                    this.opportunities = res.data.records;
                } else {
                    alert("Failed to fetch opportunities. Check persona access.");
                }
            },

            async createQuote() {
                const payload = {
                    methodName: "createCart",
                    objectType: "Quote",
                    subaction: "createQuote",
                    fields: "Id,Name",
                    filters: "Account.vlocity_cmt__Status__c:Inactive_Active_Pending",
                    inputFields: [
                        { OpportunityId: this.selectedOpportunityId },
                        { Name: this.quoteConfig.Name },
                        { vlocity_cmt__PriceListId__c: this.quoteConfig.PriceListId },
                        { CurrencyIsoCode: this.quoteConfig.CurrencyIsoCode },
                        { RecordTypeId: this.quoteConfig.RecordTypeId }
                    ]
                };
                const res = await this.executeProxy('POST', `/services/apexrest/vlocity_cmt/v2/carts`, payload);
                if (res.success) {
                    this.cartId = res.data.cartId || (res.data.records && res.data.records[0]?.Id) || res.data.Id || res.data;
                    if (typeof this.cartId === 'object' && this.cartId.cartId) {
                        this.cartId = this.cartId.cartId;
                    }
                    this.showNewQuoteModal = false;
                } else {
                    let errMsg = "Failed to create quote.\n";
                    try {
                        let errorData = res.data;
                        if (errorData && errorData.messages && errorData.messages.length > 0) {
                            errMsg += errorData.messages[0].message + "\n";
                        }
                        errMsg += JSON.stringify(errorData, null, 2);
                    } catch (e) {
                        errMsg += String(res.data);
                    }
                    console.error("Create quote failed:", res);
                    alert(errMsg);
                }
            },

            async getRootProducts() {
                const priceListId = this.cartPriceListId || this.quoteConfig.PriceListId;
                this.isLoading = true;
                try {
                    const response = await axios.get('/cpq-simulator/root-products', {
                        params: {
                            cart_id: this.cartId,
                            price_list_id: priceListId,
                            persona_id: this.selectedPersonaId || null
                        },
                        timeout: 60000
                    });
                    if (response.data && response.data.records) {
                        this.rootProducts = response.data.records;
                    } else {
                        alert('Failed to get root products.');
                    }
                } catch (error) {
                    console.error('[getRootProducts] Failed:', error);
                    alert(`Failed to get root products.\n${error.message}`);
                } finally {
                    this.isLoading = false;
                }
            },

            async addItemToCart() {
                this.itemAdded = false;
                const payload = {
                    cartId: this.cartId,
                    price: true,
                    validate: true,
                    items: [{ itemId: this.selectedPricebookEntryId, quantity: 1 }]
                };
                const res = await this.executeProxy('POST', `/services/apexrest/vlocity_cmt/v2/cpq/carts/${this.cartId}/items`, payload);
                if (res.success) {
                    this.itemAdded = true;
                    this.showAddProductModal = false;
                    this.selectedPricebookEntryId = '';
                    await this.loadRootLineItems();
                    await this.getCartItems();
                } else {
                    alert("Failed to add item to cart.");
                }
            },

            async getCartItems() {
                const res = await this.executeProxy('GET', `/services/apexrest/vlocity_cmt/v2/cpq/carts/${this.cartId}/items?includeAttachment=true&hierarchy=true`);
                if (res.success && res.data.records) {
                    const getItemId = (obj) => {
                        if (!obj) return null;
                        if (typeof obj.Id === 'string' && obj.Id.length > 0) return obj.Id;
                        if (typeof obj.id === 'string' && obj.id.length > 0) return obj.id;
                        if (typeof obj.itemId === 'string' && obj.itemId.length > 0) return obj.itemId;
                        if (obj.Id && obj.Id.value) return obj.Id.value;
                        return `local_${Math.random().toString(36).substr(2, 9)}`;
                    };

                    const extractChildren = (items) => {
                        let children = [];
                        items.forEach(item => {
                            if (item.lineItems && item.lineItems.records && item.lineItems.records.length > 0) {
                                item.lineItems.records.forEach(c => { c.Id = getItemId(c); });
                                children = children.concat(item.lineItems.records);
                                children = children.concat(extractChildren(item.lineItems.records));
                            }
                        });
                        return children;
                    };

                    res.data.records.forEach(r => r.Id = getItemId(r));
                    this.childItems = extractChildren(res.data.records);

                    const newPricing = {};
                    const newAttrs = {};
                    this.childItems.forEach(child => {
                        newPricing[child.Id] = { otc: child.vlocity_cmt__OneTimeCharge__c.value, rc: child.vlocity_cmt__AdditionalRecurringCharge__c.value, saved: false };
                        newAttrs[child.Id] = { _saved: false };
                    });
                    this.childPricing = newPricing;
                    this.childAttributes = newAttrs;
                } else {
                    alert("Failed to get cart items.");
                }
            },

            getAttributes(childId) {
                const child = this.childItems.find(c => c.Id === childId);
                if (!child) {
                    alert("Child item not found in cart");
                    return;
                }

                const attrs = {};
                const meta = {};

                if (child.attributeCategories && child.attributeCategories.records) {
                    child.attributeCategories.records.forEach(cat => {
                        if (cat.productAttributes && cat.productAttributes.records) {
                            cat.productAttributes.records.forEach(attr => {
                                if (attr.disabled === false && attr.hidden === false) {
                                    const key = attr.code || attr.label;

                                    // DEBUG: log raw attr to inspect userValues format
                                    if (attr.inputType === 'dropdown') {
                                        console.log('[CPQ Attr Debug]', key, {
                                            userValues: attr.userValues,
                                            value: attr.value,
                                            values: attr.values,
                                            inputType: attr.inputType
                                        });
                                    }

                                    // Extract the current value from userValues,
                                    // which can be: string, array, or {value: ...} object
                                    let currentVal = attr.userValues;
                                    if (currentVal === undefined || currentVal === null) {
                                        currentVal = attr.value || '';
                                    } else if (Array.isArray(currentVal)) {
                                        // e.g. ["Yes"] or [{value: "Yes"}]
                                        currentVal = currentVal.length > 0
                                            ? (typeof currentVal[0] === 'object' ? (currentVal[0].value ?? '') : currentVal[0])
                                            : '';
                                    } else if (typeof currentVal === 'object') {
                                        // e.g. {value: "Yes"}
                                        currentVal = currentVal.value ?? '';
                                    }
                                    // else: already a plain string

                                    attrs[key] = currentVal;
                                    meta[key] = {
                                        inputType: attr.inputType || 'text',
                                        values: attr.values || [],
                                        required: attr.required === true,
                                        label: attr.label
                                    };
                                }
                            });
                        }
                    });
                }

                attrs._saved = false;
                this.childAttributes[childId] = attrs;
                this.childAttrsMeta[childId] = meta;
            },

            async updatePricing(childId) {
                const payload = {
                    AdditionalOneTimeCharge__c: parseFloat(this.childPricing[childId].otc),
                    AdditionalRecurringCharge__c: parseFloat(this.childPricing[childId].rc)
                };
                const resPatch = await this.executeProxy('PATCH', `/services/data/v66.0/sobjects/QuoteLineItem/${childId}`, payload);
                if (resPatch.success) {
                    const resRecalc = await this.executeProxy('GET', `/services/apexrest/vlocity_cmt/v2/cpq/carts/${this.cartId}/price?price=true`);
                    if (resRecalc.success) {
                        this.childPricing[childId].saved = true;
                    } else {
                        alert("Patch succeeded but recalculation failed.");
                    }
                } else {
                    alert("Failed to update pricing.");
                }
            },

            async updateAttributes(childId) {
                const attrs = { ...this.childAttributes[childId] };
                delete attrs._saved;

                const payload = {
                    vlocity_cmt__AttributeSelectedValues__c: JSON.stringify(attrs)
                };
                const res = await this.executeProxy('PATCH', `/services/data/v66.0/sobjects/QuoteLineItem/${childId}`, payload);
                if (res.success) {
                    this.childAttributes[childId]._saved = true;
                } else {
                    alert("Failed to update attributes.");
                }
            }
        }));
    });
</script>

        </div>
    </div>
</x-app-layout>