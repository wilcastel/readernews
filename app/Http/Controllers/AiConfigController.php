<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AiConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $configs = \App\Models\AiConfig::orderBy('is_active', 'desc')->get();
        
        if ($request->wantsJson()) {
            return response()->json($configs);
        }

        // Return view for settings page (we might need to merge this into SettingsController index logic later, 
        // but for now let's assume we render a partial or view)
        // Actually, user wants "a place where manage IAs".
        // Let's create a dedicated view or assume it will be included in Settings.
        // For simplicity, let's keep it API-centric for the interactions we've built (AJAX in Settings).
        // But since we are creating a whole CRUD, let's return a view 'ai_configs.index' or redirect back.
        
        return view('settings.ai_configs', compact('configs')); 
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'base_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'model_id' => 'required|string',
            'mode' => 'required|in:local,paid,free'
        ]);
        
        // Defaults
        $validated['is_active'] = $request->has('is_active') || $request->is_active === 'true';

        \App\Models\AiConfig::create($validated);

        return redirect()->back()->with('success', 'AI Provider added.');
    }

    public function update(Request $request, \App\Models\AiConfig $aiConfig)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'base_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'model_id' => 'required|string',
            'mode' => 'required|in:local,paid,free'
        ]);

        $validated['is_active'] = $request->has('is_active');

        $aiConfig->update($validated);

        return redirect()->back()->with('success', 'AI Provider updated.');
    }

    public function destroy(\App\Models\AiConfig $aiConfig)
    {
        $aiConfig->delete();
        return redirect()->back()->with('success', 'AI Provider deleted.');
    }
}
