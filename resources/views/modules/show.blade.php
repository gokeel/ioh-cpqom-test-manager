<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-brand-dark leading-tight flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-brand-dark transition-colors">Dashboard</a>
            <span class="text-gray-300">/</span>
            {{ $module->name }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="mb-6 flex justify-between items-center bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div>
                    <h3 class="text-2xl font-bold text-brand-dark">{{ $module->name }} Tests</h3>
                    <p class="text-gray-600 mt-1">{{ $module->description }}</p>
                </div>
                <div class="space-x-3">
                    <button class="px-4 py-2 bg-brand-teal hover:bg-opacity-90 text-white rounded-lg shadow font-medium text-sm transition-colors cursor-pointer" onclick="document.getElementById('add-test-modal').classList.remove('hidden')">
                        + Add Test Case
                    </button>
                    <!-- Mock pull allure report button to simulate external CI pulling -->
                    <form action="{{ route('test-runs.store') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="action_type" value="pull_allure">
                        <input type="hidden" name="module_id" value="{{ $module->id }}">
                        <button type="submit" class="px-4 py-2 bg-white border border-brand-dark text-brand-dark hover:bg-gray-50 rounded-lg shadow-sm font-medium text-sm transition-colors">
                            Pull Allure UI Reports
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y-2 divide-gray-200 bg-white text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 text-left rounded-tl-lg">Type</th>
                                <th class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 text-left">Test Name</th>
                                <th class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 text-left">Configuration</th>
                                <th class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 text-left">Status</th>
                                <th class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 text-right rounded-tr-lg">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($module->testCases as $test)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($test->type === 'API')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-yellow bg-opacity-20 text-yellow-800">API Test</span>
                                    @elseif($test->type === 'Apex')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-purple bg-opacity-20 text-brand-purple">Apex Test</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-pink bg-opacity-20 text-brand-pink">UI Test</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">{{ $test->title }}</td>
                                <td class="px-6 py-4 text-gray-500 max-w-xs truncate text-xs font-mono bg-gray-50 rounded">
                                    {{ json_encode($test->configuration) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @php
                                        $latestRun = $test->testRuns->first();
                                    @endphp
                                    @if(!$latestRun)
                                        <span class="text-gray-400 italic">Not Run</span>
                                    @elseif($latestRun->status === 'Pass')
                                        <span class="inline-flex items-center text-green-600 font-medium">✓ Pass</span>
                                    @else
                                        <span class="inline-flex items-center text-red-600 font-medium">✗ Fail</span>
                                    @endif
                                    @if($latestRun)
                                        <div class="text-xs text-gray-400 mt-1">{{ $latestRun->executed_at?->diffForHumans() }}</div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    @if($test->type !== 'UI')
                                    <form action="{{ route('test-runs.store') }}" method="POST" class="inline flex items-center justify-end gap-2">
                                        @csrf
                                        <input type="hidden" name="test_case_id" value="{{ $test->id }}">
                                        <select name="salesforce_user_id" class="text-xs border-gray-300 rounded py-1 pl-2 pr-6">
                                            <option value="">System Default</option>
                                            @foreach($sfUsers as $sfu)
                                                <option value="{{ $sfu->id }}">As {{ $sfu->label }}</option>
                                            @endforeach
                                        </select>
                                        <button title="Run Test" class="text-white bg-brand-teal hover:bg-brand-dark p-1.5 rounded transition-colors shadow">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </form>
                                    @else
                                        <span class="text-gray-300 italic text-xs">Run via CI/CD</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <p>No tests defined for this module yet.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Add Test Modal (Simplified for Demo) -->
    <div id="add-test-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('add-test-modal').classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('modules.test-cases.store', $module) }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Create New Test Case</h3>
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="title" value="Test Title" />
                                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label for="type" value="Test Type" />
                                <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-brand-teal focus:ring-brand-teal rounded-md shadow-sm" onchange="toggleConfigView(this.value)">
                                    <option value="API">API Test</option>
                                    <option value="Apex">Apex Class Test</option>
                                    <option value="UI">UI Test (UTAM/Allure)</option>
                                </select>
                            </div>
                            
                            @if($module->api_schema)
                            <!-- Dynamic UI for predefined module API Schema -->
                            <div id="dynamic-schema-fields" class="mt-4 p-4 bg-gray-50 border rounded-lg">
                                <h4 class="text-sm font-bold text-gray-700 mb-2">API Configuration ({{ $module->api_schema['method'] }} {{ $module->api_schema['endpoint'] }})</h4>
                                <div class="space-y-3">
                                    @foreach($module->api_schema['fields'] as $field)
                                        <div>
                                            <x-input-label for="payload_{{ $field['name'] }}" value="{{ $field['name'] }} {{ $field['required'] ? '*' : '' }}" />
                                            <x-text-input id="payload_{{ $field['name'] }}" name="payload[{{ $field['name'] }}]" type="{{ $field['type'] === 'number' ? 'number' : 'text' }}" class="mt-1 block w-full text-sm" {{ $field['required'] ? 'required' : '' }} />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <div id="raw-config-field" class="mt-4 {{ $module->api_schema ? 'hidden' : '' }}">
                                <x-input-label for="configuration" value="Configuration (JSON payload, Apex Class Name, or UI Path)" />
                                <textarea id="configuration" name="configuration" rows="3" class="mt-1 block w-full border-gray-300 focus:border-brand-teal focus:ring-brand-teal rounded-md shadow-sm"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                        <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-brand-teal text-base font-medium text-white hover:bg-opacity-90 sm:ml-3 sm:w-auto sm:text-sm">
                            Save Test Case
                        </button>
                        <button type="button" onclick="document.getElementById('add-test-modal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function toggleConfigView(type) {
            const hasSchema = {{ $module->api_schema ? 'true' : 'false' }};
            const dynamicFields = document.getElementById('dynamic-schema-fields');
            const rawConfig = document.getElementById('raw-config-field');
            const rawTextarea = document.getElementById('configuration');
            
            if (type === 'API' && hasSchema) {
                if(dynamicFields) dynamicFields.classList.remove('hidden');
                if(rawConfig) rawConfig.classList.add('hidden');
                // Remove required from Raw configuration if hidden
                if(rawTextarea) rawTextarea.removeAttribute('required');
            } else {
                if(dynamicFields) dynamicFields.classList.add('hidden');
                if(rawConfig) rawConfig.classList.remove('hidden');
                // Make raw config required
                if(rawTextarea) rawTextarea.setAttribute('required', 'required');
            }
        }
    </script>
</x-app-layout>
