<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SeedUserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|max:255|unique:users,email',
            'password'   => ['required', 'confirmed', Password::min(8)],
            'role'       => 'required|in:Tester,Admin',
        ]);

        $user = User::create([
            'name'              => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'role'              => $validated['role'],
            'email_verified_at' => now(),
        ]);

        (new SeedUserData)($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'You cannot delete your own account.');

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User "' . $user->name . '" deleted.');
    }
}
