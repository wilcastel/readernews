<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PromptController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json(\App\Models\Prompt::where('is_active', true)->get());
        }
        // If not json, it's irrelevant for now as we view prompts in settings/index
        return redirect()->route('settings.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string'
        ]);

        \App\Models\Prompt::create($validated);

        return back()->with('success', 'Prompt created successfully.');
    }

    public function update(Request $request, \App\Models\Prompt $prompt)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string'
        ]);

        $prompt->update($validated);

        return back()->with('success', 'Prompt updated successfully.');
    }

    public function destroy(\App\Models\Prompt $prompt)
    {
        $prompt->delete();
        return back()->with('success', 'Prompt deleted successfully.');
    }
}
