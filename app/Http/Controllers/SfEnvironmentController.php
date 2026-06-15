<?php

namespace App\Http\Controllers;

use App\Models\SfEnvironment;
use Illuminate\Http\Request;

class SfEnvironmentController extends Controller
{
    public function index()
    {
        $environments = SfEnvironment::orderBy('persona_key')->get();
        return view('sf-environments.index', compact('environments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'persona_key'     => ['required', 'string', 'max:50', 'unique:sf_environments,persona_key', 'regex:/^[a-zA-Z][a-zA-Z0-9_]*$/'],
            'sf_url'          => 'required|url|max:255',
            'after_login_url' => 'required|url|max:255',
            'username'        => 'required|string|max:255',
            'password'        => 'required|string|max:500',
            'client_id'       => 'nullable|string|max:500',
            'client_secret'   => 'nullable|string|max:500',
        ]);

        SfEnvironment::create($validated);

        return redirect()->route('sf-environments.index')
            ->with('success', 'Persona "' . $validated['persona_key'] . '" created successfully.');
    }

    public function update(Request $request, SfEnvironment $sfEnvironment)
    {
        $validated = $request->validate([
            'sf_url'          => 'required|url|max:255',
            'after_login_url' => 'required|url|max:255',
            'username'        => 'required|string|max:255',
            'password'        => 'nullable|string|max:500',
            'client_id'       => 'nullable|string|max:500',
            'client_secret'   => 'nullable|string|max:500',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $sfEnvironment->update($validated);

        return redirect()->route('sf-environments.index')
            ->with('success', 'Persona "' . $sfEnvironment->persona_key . '" updated.');
    }

    public function destroy(SfEnvironment $sfEnvironment)
    {
        $key = $sfEnvironment->persona_key;
        $sfEnvironment->delete();

        return redirect()->route('sf-environments.index')
            ->with('success', 'Persona "' . $key . '" deleted.');
    }
}
