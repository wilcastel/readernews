<?php

namespace Wilcastel\AiManager\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Wilcastel\AiManager\Models\AiConfig;

class AiConfigController extends Controller
{
    public function index()
    {
        $configs = AiConfig::orderBy('is_active', 'desc')->get();
        return view('ai-manager::index', compact('configs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'base_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'model_id' => 'required|string',
            'mode' => 'required|in:all,local,paid,free'
        ]);
        
        $validated['is_active'] = $request->has('is_active');

        AiConfig::create($validated);

        return redirect()->back()->with('success', 'AI Provider added successfully.');
    }

    public function update(Request $request, $id)
    {
        $aiConfig = AiConfig::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'base_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'model_id' => 'required|string',
            'mode' => 'required|in:all,local,paid,free'
        ]);

        $validated['is_active'] = $request->has('is_active');
        
        $aiConfig->update($validated);

        return redirect()->back()->with('success', 'AI Provider updated.');
    }

    public function destroy($id)
    {
        $aiConfig = AiConfig::findOrFail($id);
        $aiConfig->delete();
        return redirect()->back()->with('success', 'AI Provider deleted.');
    }
    
    public function toggle($id)
    {
        $aiConfig = AiConfig::findOrFail($id);
        $aiConfig->update(['is_active' => !$aiConfig->is_active]);
        return redirect()->back();
    }
}
