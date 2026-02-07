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
        $query = \App\Models\AiConfig::query();

        if ($request->get('sort') === 'provider') {
            $query->orderBy('provider', 'asc')->orderBy('name', 'asc');
        } elseif ($request->get('sort') === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } elseif ($request->get('sort') === 'free_first') {
            $query->orderBy('mode', 'asc')->orderBy('name', 'asc');
        } elseif ($request->get('sort') === 'paid_first') {
            $query->orderBy('mode', 'desc')->orderBy('name', 'asc'); // Paid > Local > Free
        } else {
            // Default: Newest first
            $query->orderBy('created_at', 'desc');
        }

        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        $configs = $query->get();
        
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
            'mode' => 'required|in:local,paid,free',
            'input_price' => 'nullable|numeric|min:0',
            'output_price' => 'nullable|numeric|min:0',
            'cantaprox' => 'nullable|integer|min:0',
            'description' => 'nullable|string'
        ]);
        
        // Defaults
        $validated['is_active'] = $request->has('is_active') || $request->is_active === 'true';

        // Auto-calculate approximate usage if prices are present AND mode is paid
        if ($validated['mode'] === 'paid' && isset($validated['input_price']) && isset($validated['output_price'])) {
             $calculated = $this->calculateApproximateUsage($validated['input_price'], $validated['output_price']);
             if (empty($validated['cantaprox'])) {
                 $validated['cantaprox'] = $calculated;
             }
        }

        \App\Models\AiConfig::create($validated);

        return redirect()->back()->with('success', 'AI Provider added.');
    }

    public function update(Request $request, \App\Models\AiConfig $aiConfig) // Ensure variable name matches
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'base_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'model_id' => 'required|string',
            'mode' => 'required|in:local,paid,free',
            'input_price' => 'nullable|numeric|min:0',
            'output_price' => 'nullable|numeric|min:0',
            'cantaprox' => 'nullable|integer|min:0',
            'description' => 'nullable|string'
        ]);

        $validated['is_active'] = $request->has('is_active');

        // Auto-calculate approximate usage if prices are updated and cantaprox is empty AND mode is paid
        if ($validated['mode'] === 'paid' && isset($validated['input_price']) && isset($validated['output_price'])) {
             if (empty($validated['cantaprox'])) {
                 $validated['cantaprox'] = $this->calculateApproximateUsage($validated['input_price'], $validated['output_price']);
             }
        }

        $aiConfig->update($validated);

        return redirect()->back()->with('success', 'AI Provider updated.');
    }

    /**
     * Calculate approximate usage for $10 based on standard payload.
     */
    private function calculateApproximateUsage($inputPrice, $outputPrice)
    {
        $budget = 10;
        $avgInputTokens = 1000;
        $avgOutputTokens = 800;

        // Prevent division by zero
        if ($inputPrice <= 0 && $outputPrice <= 0) return 0;

        $costPerUse = ($inputPrice * $avgInputTokens / 1000000) + ($outputPrice * $avgOutputTokens / 1000000);

        if ($costPerUse <= 0) return 0;

        return floor($budget / $costPerUse);
    }

    public function destroy(\App\Models\AiConfig $aiConfig)
    {
        $aiConfig->delete();
        return redirect()->back()->with('success', 'AI Provider deleted.');
    }

    public function export()
    {
        $configs = \App\Models\AiConfig::all()->makeHidden(['api_key', 'created_at', 'updated_at', 'id']);
        
        $filename = 'ai-configs-' . date('Y-m-d') . '.json';
        
        return response()->streamDownload(function () use ($configs) {
            echo $configs->toJson(JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json',
        ]);

        try {
            $json = file_get_contents($request->file('file')->getRealPath());
            $data = json_decode($json, true);

            if (!is_array($data)) {
                return back()->with('error', 'Invalid JSON format.');
            }

            $count = 0;
            foreach ($data as $item) {
                // strict validation could be done here similar to store()
                if (empty($item['model_id']) || empty($item['provider'])) {
                    continue; // Skip invalid entries
                }
                
                // Allow "update or create" logic based on model_id + provider
                // to avoid duplicates but allow updating settings if they changed in the export
                // For now, let's just create if not exists to avoid overwriting existing keys accidentally
                // OR we can rely on user intent.
                // Decision: Create new if not exists.
                
                $exists = \App\Models\AiConfig::where('provider', $item['provider'])
                                ->where('model_id', $item['model_id'])
                                ->exists();

                if (!$exists) {
                     // Sanitize: ensure no api_key is imported if strictly following "no api keys in export"
                     // but if user manually added keys to the file, maybe we let them?
                     // The prompt says "export (no importa que no incluya los api keys)".
                     // It implies the export file won't have them. 
                     // We should unset ID just in case.
                     unset($item['id']);
                     
                     // Helper: set default active if missing
                     if (!isset($item['is_active'])) $item['is_active'] = true;

                     \App\Models\AiConfig::create($item);
                     $count++;
                }
            }

            return back()->with('success', "Imported {$count} configurations.");
        } catch (\Exception $e) {
            return back()->with('error', 'Error parsing file: ' . $e->getMessage());
        }
    }
}
