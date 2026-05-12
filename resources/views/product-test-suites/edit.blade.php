<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-brand-dark leading-tight">
            Edit: {{ $productTestSuite->name }}
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto px-4">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4"
                x-data="{
                    productLine: '{{ $productTestSuite->product->product_line }}',
                    products: [],
                    selectedProductId: {{ $productTestSuite->product_id }},
                    productCode: '{{ $productTestSuite->product->product_code }}',
                    loading: false,

                    async init() {
                        if (this.productLine) await this.loadProducts();
                    },

                    async loadProducts() {
                        if (!this.productLine) { this.products = []; return; }
                        this.loading = true;
                        const res = await fetch('/product-test-suites/products?product_line=' + encodeURIComponent(this.productLine));
                        this.products = await res.json();
                        this.loading = false;
                    },

                    selectProduct(event) {
                        const id = parseInt(event.target.value);
                        const p = this.products.find(p => p.id === id);
                        if (p) {
                            this.selectedProductId = p.id;
                            this.productCode = p.product_code;
                        }
                    }
                }">

                <h3 class="text-base font-bold text-brand-dark mb-6">Suite Details</h3>

                <form method="POST" action="{{ route('product-test-suites.update', $productTestSuite) }}" class="space-y-5">
                    @csrf @method('PUT')

                    {{-- Product Line --}}
                    <div>
                        <x-input-label value="Product Line" />
                        <select name="_product_line" x-model="productLine" @change="loadProducts()"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-teal focus:ring-brand-teal text-sm">
                            <option value="">— Select product line —</option>
                            @foreach($productLines as $line)
                                <option value="{{ $line }}" {{ $productTestSuite->product->product_line === $line ? 'selected' : '' }}>{{ $line }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Product Name --}}
                    <div>
                        <x-input-label value="Product Name" />
                        <select name="product_id" @change="selectProduct($event)"
                            :disabled="products.length === 0"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-teal focus:ring-brand-teal text-sm disabled:bg-gray-50 disabled:text-gray-400">
                            <option value="">— Select product —</option>
                            <template x-for="p in products" :key="p.id">
                                <option :value="p.id" :selected="p.id === selectedProductId" x-text="p.product_offer"></option>
                            </template>
                        </select>
                        <p x-show="loading" class="text-xs text-gray-400 mt-1">Loading products…</p>
                        @error('product_id')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Product Code (read-only) --}}
                    <div>
                        <x-input-label value="Product Code" />
                        <input type="text" :value="productCode" readonly
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 text-sm font-mono text-gray-600" />
                    </div>

                    {{-- Suite Name --}}
                    <div>
                        <x-input-label for="name" value="Suite Name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                            value="{{ old('name', $productTestSuite->name) }}" required />
                        @error('name')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <x-input-label for="description" value="Description (optional)" />
                        <textarea id="description" name="description" rows="2"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-teal focus:ring-brand-teal text-sm">{{ old('description', $productTestSuite->description) }}</textarea>
                    </div>

                    {{-- Module Sequence --}}
                    @php
                        $linkedIds = $productTestSuite->modules->pluck('id')->toArray();
                        $linkedOrders = $productTestSuite->modules->pluck('pivot.sequence_order', 'id')->toArray();
                    @endphp
                    <div x-data="{
                        selected: @js(collect($linkedIds)->map(fn($id) => ['id' => $id, 'order' => $linkedOrders[$id] ?? 0])->values()->toArray()),
                        toggle(id) {
                            const idx = this.selected.findIndex(s => s.id === id);
                            if (idx >= 0) { this.selected.splice(idx, 1); }
                            else { this.selected.push({ id, order: this.selected.length + 1 }); }
                        },
                        isSelected(id) { return this.selected.some(s => s.id === id); },
                        orderFor(id) { return this.selected.find(s => s.id === id)?.order ?? ''; }
                    }">
                        <x-input-label value="Module Sequence" />
                        <p class="text-xs text-gray-400 mb-3 mt-0.5">Check the modules to include and set their run order.</p>

                        <div class="border border-gray-200 rounded-lg divide-y divide-gray-100">
                            @foreach($modules as $module)
                            <div class="flex items-center gap-4 px-4 py-2.5"
                                :class="isSelected({{ $module->id }}) ? 'bg-brand-teal/5' : ''">
                                <input type="checkbox"
                                    :id="'mod_{{ $module->id }}'"
                                    name="module_ids[]"
                                    value="{{ $module->id }}"
                                    :checked="isSelected({{ $module->id }})"
                                    @change="toggle({{ $module->id }})"
                                    class="rounded border-gray-300 text-brand-teal focus:ring-brand-teal">
                                <label :for="'mod_{{ $module->id }}'" class="flex-1 text-sm text-gray-700 cursor-pointer select-none">
                                    {{ $module->display_name }}
                                    @if($module->category)
                                        <span class="ml-1.5 text-xs text-brand-teal font-medium">{{ $module->category }}</span>
                                    @endif
                                </label>
                                <input type="number"
                                    :name="'sequence_order[{{ $module->id }}]'"
                                    :value="orderFor({{ $module->id }})"
                                    @input="e => { const s = selected.find(s => s.id === {{ $module->id }}); if(s) s.order = e.target.value; }"
                                    :disabled="!isSelected({{ $module->id }})"
                                    min="1"
                                    class="w-16 text-xs border-gray-300 rounded-md shadow-sm focus:border-brand-teal focus:ring-brand-teal disabled:bg-gray-50 disabled:text-gray-300 text-center"
                                    placeholder="#">
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('product-test-suites.show', $productTestSuite) }}"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-5 py-2 bg-brand-teal text-white rounded-lg text-sm font-semibold hover:opacity-90 transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </div>
</x-app-layout>
