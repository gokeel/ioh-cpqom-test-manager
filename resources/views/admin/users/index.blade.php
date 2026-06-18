<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-brand-dark leading-tight">Manage Users</h2>
                <p class="text-xs text-gray-400 mt-0.5">Create and manage application accounts</p>
            </div>
            <button
                onclick="document.getElementById('createUserDialog').showModal()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-dark text-white text-sm font-medium rounded-lg hover:bg-black transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add User
            </button>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

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
                    <h3 class="text-sm font-bold text-brand-dark">All Users</h3>
                    <span class="text-xs text-gray-400">{{ $users->count() }} total</span>
                </div>

                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Name</th>
                            <th class="px-5 py-3 text-left">Email</th>
                            <th class="px-5 py-3 text-left">Role</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($users as $user)
                        @php
                            $isMe = $user->id === auth()->id();
                            $roleLabel = match($user->role) {
                                'Admin'  => 'System Admin',
                                'Tester' => 'QA Tester',
                                default  => $user->role,
                            };
                            $roleBadge = match($user->role) {
                                'Admin'  => 'bg-purple-50 text-purple-700 border border-purple-200',
                                'Tester' => 'bg-blue-50 text-blue-700 border border-blue-200',
                                default  => 'bg-gray-100 text-gray-600 border border-gray-200',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-5 py-3 font-medium text-brand-dark">
                                {{ $user->name }}
                                @if($isMe)
                                    <span class="ml-1.5 text-xs text-gray-400">(you)</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $user->email }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center text-xs px-2.5 py-0.5 rounded-full font-semibold {{ $roleBadge }}">
                                    {{ $roleLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if($isMe)
                                    <span class="text-xs text-gray-300">—</span>
                                @else
                                    <button
                                        onclick="deleteUser({{ $user->id }}, {{ Js::from($user->name) }})"
                                        class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Delete
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    @push('modals')

    <style>
        #createUserDialog, #deleteUserDialog {
            border: none;
            border-radius: 1rem;
            padding: 0;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            width: 100%;
        }
        #createUserDialog { max-width: 30rem; }
        #deleteUserDialog { max-width: 24rem; }
        #createUserDialog::backdrop,
        #deleteUserDialog::backdrop { background: rgba(17,24,39,0.6); }
    </style>

    {{-- CREATE USER DIALOG --}}
    <dialog id="createUserDialog" onclick="if(event.target===this)this.close()">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-brand-dark">Add User</h3>
            <button type="button" onclick="document.getElementById('createUserDialog').close()"
                class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form action="{{ route('admin.users.store') }}" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="first_name" value="First Name" />
                    <x-text-input id="first_name" name="first_name" type="text"
                        class="mt-1 block w-full"
                        value="{{ old('first_name') }}" required autofocus />
                    <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="last_name" value="Last Name" />
                    <x-text-input id="last_name" name="last_name" type="text"
                        class="mt-1 block w-full"
                        value="{{ old('last_name') }}" required />
                    <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="email" value="Email Address" />
                <x-text-input id="email" name="email" type="email"
                    class="mt-1 block w-full"
                    value="{{ old('email') }}" required />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="password" value="Password" />
                <x-text-input id="password" name="password" type="password"
                    class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Confirm Password" />
                <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                    class="mt-1 block w-full" required />
            </div>

            <div>
                <x-input-label for="role" value="Role" />
                <select id="role" name="role"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required>
                    <option value="">— Select role —</option>
                    <option value="Tester"  {{ old('role') === 'Tester'  ? 'selected' : '' }}>QA Tester</option>
                    <option value="Admin"   {{ old('role') === 'Admin'   ? 'selected' : '' }}>System Admin</option>
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-1" />
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('createUserDialog').close()"
                    class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 text-sm bg-brand-dark text-white rounded-lg hover:bg-black transition font-medium">
                    Create User
                </button>
            </div>
        </form>
    </dialog>

    {{-- DELETE USER DIALOG --}}
    <dialog id="deleteUserDialog" onclick="if(event.target===this)this.close()">
        <div class="p-6">
            <div class="text-center">
                <div class="mx-auto mb-4 w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-brand-dark">Delete User?</h3>
                <p class="mt-2 text-sm text-gray-500">
                    You are about to delete
                    <strong id="deleteUserName" class="text-red-600"></strong>.
                    This cannot be undone.
                </p>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="button" onclick="document.getElementById('deleteUserDialog').close()"
                    class="flex-1 px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition font-medium">
                    Cancel
                </button>
                <form id="deleteUserForm" method="POST" class="flex-1">
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
        function deleteUser(id, name) {
            document.getElementById('deleteUserName').textContent = name;
            document.getElementById('deleteUserForm').action = '/admin/users/' + id;
            document.getElementById('deleteUserDialog').showModal();
        }

        // Re-open create dialog if there were validation errors
        @if($errors->any())
            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('createUserDialog').showModal();
            });
        @endif
    </script>

    @endpush

</x-app-layout>
