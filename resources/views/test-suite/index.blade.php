<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-brand-dark leading-tight">
            {{ __('Test Suite Manager') }}
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if(session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ── Module Cards ──────────────────────────────────────────── --}}
            <div x-data="{ activeCategory: '{{ $categories->first() ?? 'all' }}' }">

                {{-- Header + Import button --}}
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-brand-dark">Spec Modules</h3>
                    <a href="{{ route('test-suite.import.index') }}"
                        class="text-xs px-3 py-1.5 border border-brand-teal text-brand-teal rounded-lg font-semibold hover:bg-brand-teal hover:text-white transition">
                        Import from Excel
                    </a>
                </div>

                {{-- Category filter pills --}}
                @if($categories->isNotEmpty())
                <div class="flex flex-wrap gap-2 mb-5">
                    <button
                        @click="activeCategory = 'all'"
                        :class="activeCategory === 'all'
                            ? 'bg-brand-teal text-white border-brand-teal'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-brand-teal hover:text-brand-teal'"
                        class="text-xs px-3 py-1.5 border rounded-full font-semibold transition">
                        All
                    </button>
                    @foreach($categories as $cat)
                    <button
                        @click="activeCategory = '{{ $cat }}'"
                        :class="activeCategory === '{{ $cat }}'
                            ? 'bg-brand-teal text-white border-brand-teal'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-brand-teal hover:text-brand-teal'"
                        class="text-xs px-3 py-1.5 border rounded-full font-semibold transition">
                        {{ $cat }}
                    </button>
                    @endforeach
                </div>
                @endif

                {{-- Module cards grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-2">
                    @foreach($modules as $module)
                    <div
                        x-show="activeCategory === 'all' || activeCategory === '{{ $module->category }}'"
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col hover:shadow-md transition-shadow">

                        {{-- Card body --}}
                        <div class="p-6 flex-1">
                            {{-- Category pill --}}
                            @if($module->category)
                                <span class="text-xs py-0.5 rounded-full bg-brand-teal/10 text-brand-teal font-semibold mb-3 inline-block">
                                    {{ $module->category }}
                                </span>
                            @endif

                            {{-- Title as link --}}
                            <a href="{{ route('test-suite.show', $module) }}"
                                class="block font-bold text-brand-dark text-base leading-snug hover:text-brand-teal transition-colors">
                                {{ $module->display_name }}
                            </a>

                            {{-- Description --}}
                            @if($module->description)
                                <p class="text-xs text-gray-400 mt-1.5 line-clamp-2">{{ $module->description }}</p>
                            @endif

                            {{-- Case count --}}
                            <span class="text-xs text-gray-400 font-medium mt-2 inline-block">{{ $module->test_parameters_count }} {{ Str::plural('case', $module->test_parameters_count) }}</span>
                        </div>

                        {{-- Counter --}}
                        <div class="px-6 pb-6 pt-5 border-t border-gray-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs text-gray-400 font-semibold uppercase tracking-widest">Run Counter</span>
                                <span class="text-2xl font-extrabold text-brand-teal tabular-nums">{{ $module->counter }}</span>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('test-suite.counter.increment', $module) }}" class="flex-1">
                                    @csrf
                                    <button class="w-full text-xs px-2 py-1.5 bg-brand-teal text-white rounded-lg font-semibold hover:opacity-90 transition">
                                        +1 Increment
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('test-suite.counter.reset', $module) }}"
                                    onsubmit="return confirm('Reset counter to 0?')">
                                    @csrf
                                    <button class="text-xs px-3 py-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                                        Reset
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Runtime State ─────────────────────────────────────────── --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-brand-dark">Runtime State</h3>
                    <button onclick="document.getElementById('add-state-modal').classList.remove('hidden')"
                        class="text-xs px-3 py-1.5 bg-brand-teal text-white rounded-lg font-semibold hover:opacity-90 transition">
                        + Add Key
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <table class="min-w-full text-sm divide-y divide-gray-100">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3 text-left">Key</th>
                                <th class="px-5 py-3 text-left">Value</th>
                                <th class="px-5 py-3 text-left">Description</th>
                                <th class="px-5 py-3 text-left">Last Updated</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($runtimeState as $state)
                            <tr x-data="{ editing: false }">
                                <td class="px-5 py-3 font-mono text-brand-dark font-semibold text-xs">{{ $state->state_key }}</td>
                                <td class="px-5 py-3">
                                    <span x-show="!editing" class="font-mono text-xs text-gray-700">{{ $state->state_value ?? '—' }}</span>
                                    <form x-show="editing" method="POST"
                                        action="{{ route('test-suite.runtime.update', $state) }}"
                                        class="flex gap-2 items-start" @submit="editing = false">
                                        @csrf @method('PUT')
                                        <div class="flex-1 space-y-1">
                                            <input type="text" name="state_value" value="{{ $state->state_value }}"
                                                class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-brand-teal focus:ring-brand-teal">
                                            <input type="text" name="description" value="{{ $state->description }}"
                                                placeholder="Description…"
                                                class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-brand-teal focus:ring-brand-teal">
                                        </div>
                                        <button class="text-xs px-2 py-1 bg-brand-teal text-white rounded font-semibold whitespace-nowrap">Save</button>
                                    </form>
                                </td>
                                <td class="px-5 py-3 text-xs text-gray-500" x-show="!editing">{{ $state->description ?? '—' }}</td>
                                <td class="px-5 py-3 text-xs text-gray-400" x-show="!editing">{{ $state->last_updated_at?->diffForHumans() }}</td>
                                <td class="px-5 py-3 text-right" colspan="{{ $state ? 1 : 3 }}">
                                    <div class="flex justify-end gap-2">
                                        <button @click="editing = !editing"
                                            class="text-xs text-brand-teal hover:underline font-medium">
                                            <span x-text="editing ? 'Cancel' : 'Edit'"></span>
                                        </button>
                                        <form method="POST" action="{{ route('test-suite.runtime.destroy', $state) }}"
                                            onsubmit="return confirm('Delete key {{ $state->state_key }}?')">
                                            @csrf @method('DELETE')
                                            <button class="text-xs text-red-500 hover:underline font-medium">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-gray-400 text-sm">No runtime state entries yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- Add State Modal --}}
    <div id="add-state-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="document.getElementById('add-state-modal').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
            <h3 class="font-bold text-lg mb-4 text-brand-dark">Add Runtime State Key</h3>
            <form method="POST" action="{{ route('test-suite.runtime.store') }}" class="space-y-4">
                @csrf
                <div>
                    <x-input-label for="state_key" value="Key" />
                    <x-text-input id="state_key" name="state_key" type="text" class="mt-1 block w-full" placeholder="e.g. opportunityId" required />
                </div>
                <div>
                    <x-input-label for="state_value" value="Value" />
                    <x-text-input id="state_value" name="state_value" type="text" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="description" value="Description" />
                    <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('add-state-modal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2 bg-brand-teal text-white rounded-lg text-sm font-semibold hover:opacity-90">Save</button>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
