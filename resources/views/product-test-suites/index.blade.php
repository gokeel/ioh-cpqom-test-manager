<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-brand-dark leading-tight">
            Product Test Suites
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <div x-data="{ activeLine: 'all' }">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-brand-dark">All Product Suites</h3>
                    <a href="{{ route('product-test-suites.create') }}"
                        class="text-xs px-3 py-1.5 bg-brand-teal text-white rounded-lg font-semibold hover:opacity-90 transition">
                        + New Suite
                    </a>
                </div>

                {{-- Product line filter pills --}}
                @if($productLines->isNotEmpty())
                <div class="flex flex-wrap gap-2 mb-5">
                    <button
                        @click="activeLine = 'all'"
                        :class="activeLine === 'all'
                            ? 'bg-brand-teal text-white border-brand-teal'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-brand-teal hover:text-brand-teal'"
                        class="text-xs px-3 py-1.5 border rounded-full font-semibold transition">
                        All
                    </button>
                    @foreach($productLines as $line)
                    <button
                        @click="activeLine = '{{ $line }}'"
                        :class="activeLine === '{{ $line }}'
                            ? 'bg-brand-teal text-white border-brand-teal'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-brand-teal hover:text-brand-teal'"
                        class="text-xs px-3 py-1.5 border rounded-full font-semibold transition">
                        {{ $line }}
                    </button>
                    @endforeach
                </div>
                @endif

            @if($suites->isEmpty())
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center text-gray-400 text-sm">
                    No product test suites yet. Create one to get started.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($suites as $suite)
                    <div
                        x-show="activeLine === 'all' || activeLine === '{{ $suite->product->product_line }}'"
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col hover:shadow-md transition-shadow">
                        <div class="p-6 flex-1">
                            <span class="text-xs py-0.5 px-2 rounded-full bg-brand-teal/10 text-brand-teal font-semibold inline-block mb-3">
                                {{ $suite->product->product_line }}
                            </span>

                            <a href="{{ route('product-test-suites.show', $suite) }}"
                                class="block font-bold text-brand-dark text-base leading-snug hover:text-brand-teal transition-colors">
                                {{ $suite->name }}
                            </a>

                            <p class="text-xs font-mono text-gray-400 mt-1">{{ $suite->product->product_code }}</p>

                            @if($suite->description)
                                <p class="text-xs text-gray-400 mt-1.5 line-clamp-2">{{ $suite->description }}</p>
                            @endif

                            <span class="text-xs text-gray-400 font-medium mt-2 inline-block">
                                {{ $suite->modules_count }} {{ Str::plural('module', $suite->modules_count) }}
                            </span>
                        </div>

                        <div class="px-6 pb-6 pt-4 border-t border-gray-100 flex gap-2">
                            <a href="{{ route('product-test-suites.show', $suite) }}"
                                class="flex-1 text-center text-xs px-2 py-1.5 bg-brand-teal text-white rounded-lg font-semibold hover:opacity-90 transition">
                                View / Run
                            </a>
                            <a href="{{ route('product-test-suites.edit', $suite) }}"
                                class="text-xs px-3 py-1.5 border border-gray-200 text-gray-500 rounded-lg hover:bg-gray-50 transition">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('product-test-suites.destroy', $suite) }}"
                                onsubmit="return confirm('Delete suite {{ addslashes($suite->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="text-xs px-3 py-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif

            </div>{{-- end x-data --}}

        </div>
    </div>
</x-app-layout>
