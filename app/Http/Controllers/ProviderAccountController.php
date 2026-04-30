<?php

namespace App\Http\Controllers;

use App\Models\ProviderAccount;
use Illuminate\Http\Request;

class ProviderAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = ProviderAccount::withCount('aiConfigs')->orderBy('provider', 'asc')->orderBy('label', 'asc')->get();

        if ($request->wantsJson()) {
            return response()->json($accounts->makeVisible('api_key'));
        }

        return view('settings.provider_accounts', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string',
            'label' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'api_key' => 'nullable|string',
            'base_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        ProviderAccount::create($validated);

        return redirect()->back()->with('success', 'API account added.');
    }

    public function update(Request $request, ProviderAccount $providerAccount)
    {
        $validated = $request->validate([
            'provider' => 'required|string',
            'label' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'api_key' => 'nullable|string',
            'base_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }

        $providerAccount->update($validated);

        return redirect()->back()->with('success', 'API account updated.');
    }

    public function destroy(ProviderAccount $providerAccount)
    {
        if ($providerAccount->aiConfigs()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete account: '.$providerAccount->aiConfigs()->count().' AI config(s) are using it. Reassign them first.');
        }

        $providerAccount->delete();

        return redirect()->back()->with('success', 'API account deleted.');
    }

    public function toggleActive(ProviderAccount $providerAccount)
    {
        $providerAccount->update(['is_active' => ! $providerAccount->is_active]);

        return redirect()->back()->with('success', 'API account '.($providerAccount->is_active ? 'activated' : 'deactivated').'.');
    }
}
