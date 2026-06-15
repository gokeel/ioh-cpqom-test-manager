<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-brand-dark leading-tight">UI Tester Persona</h2>
                <p class="text-xs text-gray-400 mt-0.5">Salesforce credentials used by Playwright automation</p>
            </div>
            <button
                onclick="document.getElementById('sfCreateDialog').showModal()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-dark text-white text-sm font-medium rounded-lg hover:bg-black transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Persona
            </button>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="px-4 py-3 bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl space-y-1">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-brand-dark">Registered Personas</h3>
                    <span class="text-xs text-gray-400">{{ $environments->count() }} total</span>
                </div>

                @if($environments->isEmpty())
                    <div class="px-6 py-16 text-center text-gray-400 text-sm">
                        No personas yet. Click <strong>Add Persona</strong> to get started.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-5 py-3 text-left">Persona Key</th>
                                    <th class="px-5 py-3 text-left">Username</th>
                                    <th class="px-5 py-3 text-left">SF URL</th>
                                    <th class="px-5 py-3 text-left">App Credentials</th>
                                    <th class="px-5 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($environments as $env)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-3 font-mono font-semibold text-brand-dark text-xs">
                                        {{ $env->persona_key }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-700">{{ $env->username }}</td>
                                    <td class="px-5 py-3 text-gray-500 text-xs truncate max-w-xs">{{ $env->sf_url }}</td>
                                    <td class="px-5 py-3">
                                        @if($env->client_id)
                                            <span class="inline-flex items-center gap-1 text-xs px-2.5 py-0.5 rounded-full font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                                </svg>
                                                Connected App
                                            </span>
                                        @else
                                            <span class="text-gray-300 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <button
                                                onclick="sfOpenEdit({
                                                    id: {{ $env->id }},
                                                    persona_key: {{ Js::from($env->persona_key) }},
                                                    sf_url: {{ Js::from($env->sf_url) }},
                                                    after_login_url: {{ Js::from($env->after_login_url) }},
                                                    username: {{ Js::from($env->username) }}
                                                })"
                                                class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Edit
                                            </button>
                                            <button
                                                onclick="sfOpenDelete({{ $env->id }}, {{ Js::from($env->persona_key) }})"
                                                class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('modals')

    <style>
        dialog::backdrop { background: rgba(17,24,39,0.6); }
        dialog[open] { display: flex; flex-direction: column; }
        dialog { border: none; border-radius: 1rem; padding: 0; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); width: 100%; max-width: 32rem; }
        dialog.dialog-sm { max-width: 24rem; }
    </style>

    {{-- ── CREATE DIALOG ─────────────────────────────────────────────────────── --}}
    <dialog id="sfCreateDialog" onclick="if(event.target===this)this.close()">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-brand-dark">Add Persona</h3>
            <button type="button" onclick="document.getElementById('sfCreateDialog').close()"
                class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="overflow-y-auto" style="max-height:80vh">
            <form action="{{ route('sf-environments.store') }}" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                <div>
                    <x-input-label for="c_persona_key" value="Persona Key" />
                    <x-text-input id="c_persona_key" name="persona_key" type="text"
                        class="mt-1 block w-full font-mono"
                        placeholder="e.g. salesOperation"
                        value="{{ old('persona_key') }}" required />
                    <p class="mt-1 text-xs text-gray-400">camelCase, no spaces. Used as the key in Playwright config.</p>
                </div>
                <div>
                    <x-input-label for="c_sf_url" value="SF URL" />
                    <x-text-input id="c_sf_url" name="sf_url" type="url"
                        class="mt-1 block w-full"
                        placeholder="https://b2b-io--uat.sandbox.my.salesforce.com/"
                        value="{{ old('sf_url') }}" required />
                </div>
                <div>
                    <x-input-label for="c_after_login_url" value="After Login URL" />
                    <x-text-input id="c_after_login_url" name="after_login_url" type="url"
                        class="mt-1 block w-full"
                        placeholder="https://b2b-io--uat.sandbox.lightning.force.com/"
                        value="{{ old('after_login_url') }}" required />
                </div>
                <div>
                    <x-input-label for="c_username" value="SF Username" />
                    <x-text-input id="c_username" name="username" type="text"
                        class="mt-1 block w-full" placeholder="at.persona@b2b.uat"
                        value="{{ old('username') }}" required />
                </div>
                <div>
                    <x-input-label for="c_password" value="Password" />
                    <x-text-input id="c_password" name="password" type="password"
                        class="mt-1 block w-full" required />
                </div>
                <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide border-t border-dashed border-gray-200 pt-4">
                    Connected App (optional — sysadmin only)
                </p>
                <div>
                    <x-input-label for="c_client_id" value="Client ID" />
                    <x-text-input id="c_client_id" name="client_id" type="text"
                        class="mt-1 block w-full font-mono text-xs"
                        placeholder="3MVG9..." value="{{ old('client_id') }}" />
                </div>
                <div>
                    <x-input-label for="c_client_secret" value="Client Secret" />
                    <x-text-input id="c_client_secret" name="client_secret" type="password"
                        class="mt-1 block w-full" />
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <button type="button" onclick="document.getElementById('sfCreateDialog').close()"
                        class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm bg-brand-dark text-white rounded-lg hover:bg-black transition font-medium">
                        Create Persona
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- ── EDIT DIALOG ───────────────────────────────────────────────────────── --}}
    <dialog id="sfEditDialog" onclick="if(event.target===this)this.close()">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-brand-dark">Edit Persona</h3>
                <p id="sfEditKey" class="text-xs text-gray-400 mt-0.5 font-mono"></p>
            </div>
            <button type="button" onclick="document.getElementById('sfEditDialog').close()"
                class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="overflow-y-auto" style="max-height:80vh">
            <form id="sfEditForm" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-sm font-medium text-gray-700">Persona Key</label>
                    <p id="sfEditKeyDisplay"
                       class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg font-mono text-sm text-gray-500"></p>
                </div>
                <div>
                    <label for="e_sf_url" class="block text-sm font-medium text-gray-700">SF URL</label>
                    <input id="e_sf_url" name="sf_url" type="url"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required />
                </div>
                <div>
                    <label for="e_after_login_url" class="block text-sm font-medium text-gray-700">After Login URL</label>
                    <input id="e_after_login_url" name="after_login_url" type="url"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required />
                </div>
                <div>
                    <label for="e_username" class="block text-sm font-medium text-gray-700">SF Username</label>
                    <input id="e_username" name="username" type="text"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required />
                </div>
                <div>
                    <label for="e_password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input id="e_password" name="password" type="password"
                        placeholder="Leave blank to keep current password"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
                </div>
                <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide border-t border-dashed border-gray-200 pt-4">
                    Connected App (optional)
                </p>
                <div>
                    <label for="e_client_id" class="block text-sm font-medium text-gray-700">Client ID</label>
                    <input id="e_client_id" name="client_id" type="text"
                        placeholder="Leave blank to keep current value"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm font-mono" />
                    <p class="mt-1 text-xs text-gray-400">Stored encrypted. Leave blank to keep current.</p>
                </div>
                <div>
                    <label for="e_client_secret" class="block text-sm font-medium text-gray-700">Client Secret</label>
                    <input id="e_client_secret" name="client_secret" type="password"
                        placeholder="Leave blank to keep current value"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <button type="button" onclick="document.getElementById('sfEditDialog').close()"
                        class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm bg-brand-dark text-white rounded-lg hover:bg-black transition font-medium">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- ── DELETE DIALOG ─────────────────────────────────────────────────────── --}}
    <dialog id="sfDeleteDialog" class="dialog-sm" onclick="if(event.target===this)this.close()">
        <div class="p-6">
            <div class="text-center">
                <div class="mx-auto mb-4 w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-brand-dark">Delete Persona?</h3>
                <p class="mt-2 text-sm text-gray-500">
                    You are about to delete
                    <strong id="sfDeleteKey" class="font-mono text-red-600"></strong>.
                    This cannot be undone.
                </p>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="button" onclick="document.getElementById('sfDeleteDialog').close()"
                    class="flex-1 px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition font-medium">
                    Cancel
                </button>
                <form id="sfDeleteForm" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="w-full px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </dialog>

    <script>
        function sfOpenEdit(row) {
            document.getElementById('sfEditKey').textContent        = row.persona_key;
            document.getElementById('sfEditKeyDisplay').textContent = row.persona_key;
            document.getElementById('sfEditForm').action            = '/sf-environments/' + row.id;
            document.getElementById('e_sf_url').value               = row.sf_url;
            document.getElementById('e_after_login_url').value      = row.after_login_url;
            document.getElementById('e_username').value             = row.username;
            document.getElementById('e_password').value             = '';
            document.getElementById('e_client_id').value            = '';
            document.getElementById('e_client_secret').value        = '';
            document.getElementById('sfEditDialog').showModal();
        }

        function sfOpenDelete(id, key) {
            document.getElementById('sfDeleteKey').textContent  = '«' + key + '»';
            document.getElementById('sfDeleteForm').action      = '/sf-environments/' + id;
            document.getElementById('sfDeleteDialog').showModal();
        }
    </script>

    @endpush

</x-app-layout>
