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
        $query = \App\Models\AiConfig::with('providerAccount');

        // Search filter
        if ($q = $request->get('q')) {
            $query->where(function ($qBuilder) use ($q) {
                $qBuilder->where('name', 'like', "%{$q}%")
                    ->orWhere('model_id', 'like', "%{$q}%")
                    ->orWhere('provider', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('providerAccount', fn ($aq) => $aq->where('label', 'like', "%{$q}%"));
            });
        }

        if ($request->get('sort') === 'provider') {
            $query->orderBy('provider', 'asc')->orderBy('name', 'asc');
        } elseif ($request->get('sort') === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } elseif ($request->get('sort') === 'free_first') {
            $query->orderBy('mode', 'asc')->orderBy('name', 'asc');
        } elseif ($request->get('sort') === 'paid_first') {
            $query->orderBy('mode', 'desc')->orderBy('name', 'asc');
        } else {
            $query->orderBy('cantaprox', 'desc');
        }

        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        $configs = $query->get();

        if ($request->wantsJson()) {
            return response()->json($configs);
        }

        return view('settings.ai_configs', compact('configs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'model_id' => 'required|string',
            'mode' => 'required|in:local,paid,free',
            'provider_account_id' => 'nullable|exists:provider_accounts,id',
            'input_price' => 'nullable|numeric|min:0',
            'output_price' => 'nullable|numeric|min:0',
            'cantaprox' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active') || $request->is_active === 'true';
        $validated['use_for_scraping'] = $request->has('use_for_scraping') || $request->use_for_scraping === 'true';

        if ($validated['mode'] === 'free' && empty($validated['cantaprox'])) {
            $validated['cantaprox'] = 100000;
        } elseif ($validated['mode'] === 'paid' && isset($validated['input_price']) && isset($validated['output_price'])) {
            $calculated = $this->calculateApproximateUsage($validated['input_price'], $validated['output_price']);
            if (empty($validated['cantaprox'])) {
                $validated['cantaprox'] = $calculated;
            }
        }

        \App\Models\AiConfig::create($validated);

        return redirect()->back()->with('success', 'AI Provider added.');
    }

    public function update(Request $request, \App\Models\AiConfig $aiConfig)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'model_id' => 'required|string',
            'mode' => 'required|in:local,paid,free',
            'provider_account_id' => 'nullable|exists:provider_accounts,id',
            'input_price' => 'nullable|numeric|min:0',
            'output_price' => 'nullable|numeric|min:0',
            'cantaprox' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['use_for_scraping'] = $request->has('use_for_scraping');

        if ($validated['mode'] === 'free' && empty($validated['cantaprox'])) {
            $validated['cantaprox'] = 100000;
        } elseif ($validated['mode'] === 'paid' && isset($validated['input_price']) && isset($validated['output_price'])) {
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
        if ($inputPrice <= 0 && $outputPrice <= 0) {
            return 0;
        }

        $costPerUse = ($inputPrice * $avgInputTokens / 1000000) + ($outputPrice * $avgOutputTokens / 1000000);

        if ($costPerUse <= 0) {
            return 0;
        }

        return floor($budget / $costPerUse);
    }

    public function destroy(\App\Models\AiConfig $aiConfig)
    {
        $aiConfig->delete();

        return redirect()->back()->with('success', 'AI Provider deleted.');
    }

    public function export()
    {
        $accounts = \App\Models\ProviderAccount::all()->map(fn ($a) => [
            'provider' => $a->provider,
            'label' => $a->label,
            'email' => $a->email,
            'api_key' => $a->api_key,
            'base_url' => $a->base_url,
            'is_active' => $a->is_active,
        ]);

        $configs = \App\Models\AiConfig::with('providerAccount')->get()->map(fn ($c) => [
            'name' => $c->name,
            'provider' => $c->provider,
            'model_id' => $c->model_id,
            'mode' => $c->mode,
            'is_active' => $c->is_active,
            'input_price' => $c->input_price,
            'output_price' => $c->output_price,
            'cantaprox' => $c->cantaprox,
            'description' => $c->description,
            'account_label' => $c->providerAccount?->label,
        ]);

        $payload = [
            'version' => 2,
            'exported_at' => now()->toIso8601String(),
            'provider_accounts' => $accounts,
            'ai_configs' => $configs,
        ];

        $filename = 'ai-configs-'.date('Y-m-d').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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

            if (! is_array($data)) {
                return back()->with('error', 'Invalid JSON format.');
            }

            $accountMap = [];
            $accountsImported = 0;
            $configsImported = 0;

            if (isset($data['version']) && $data['version'] === 2) {
                foreach ($data['provider_accounts'] ?? [] as $item) {
                    if (empty($item['provider']) || empty($item['label'])) {
                        continue;
                    }

                    $account = \App\Models\ProviderAccount::firstOrCreate(
                        ['provider' => $item['provider'], 'label' => $item['label']],
                        [
                            'email' => $item['email'] ?? null,
                            'api_key' => $item['api_key'] ?? null,
                            'base_url' => $item['base_url'] ?? null,
                            'is_active' => $item['is_active'] ?? true,
                        ]
                    );

                    if (! empty($item['api_key']) && empty($account->api_key)) {
                        $account->update(['api_key' => $item['api_key']]);
                    }

                    $accountMap[$item['label']] = $account->id;
                    $accountsImported++;
                }

                foreach ($data['ai_configs'] ?? [] as $item) {
                    if (empty($item['model_id']) || empty($item['provider'])) {
                        continue;
                    }

                    $exists = \App\Models\AiConfig::where('provider', $item['provider'])
                        ->where('model_id', $item['model_id'])
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $accountLabel = $item['account_label'] ?? null;
                    unset($item['account_label'], $item['id']);

                    if (! isset($item['is_active'])) {
                        $item['is_active'] = true;
                    }

                    if ($accountLabel && isset($accountMap[$accountLabel])) {
                        $item['provider_account_id'] = $accountMap[$accountLabel];
                    }

                    \App\Models\AiConfig::create($item);
                    $configsImported++;
                }
            } else {
                foreach ($data as $item) {
                    if (empty($item['model_id']) || empty($item['provider'])) {
                        continue;
                    }

                    $exists = \App\Models\AiConfig::where('provider', $item['provider'])
                        ->where('model_id', $item['model_id'])
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $apiKey = $item['api_key'] ?? null;
                    $baseUrl = $item['base_url'] ?? null;
                    unset($item['id'], $item['api_key'], $item['base_url'], $item['created_at'], $item['updated_at']);

                    if (! isset($item['is_active'])) {
                        $item['is_active'] = true;
                    }

                    if ($apiKey || $baseUrl) {
                        $account = \App\Models\ProviderAccount::firstOrCreate(
                            [
                                'provider' => $item['provider'],
                                'base_url' => $baseUrl ?? $this->defaultBaseUrl($item['provider']),
                            ],
                            [
                                'label' => ucfirst($item['provider']).' Account (imported)',
                                'api_key' => $apiKey,
                                'is_active' => true,
                            ]
                        );
                        $item['provider_account_id'] = $account->id;
                    }

                    \App\Models\AiConfig::create($item);
                    $configsImported++;
                }

                return back()->with('success', "Imported {$configsImported} configs (legacy format).");
            }

            return back()->with('success', "Imported {$accountsImported} API accounts and {$configsImported} AI configs.");
        } catch (\Exception $e) {
            return back()->with('error', 'Error parsing file: '.$e->getMessage());
        }
    }

    private function defaultBaseUrl(string $provider): ?string
    {
        return match ($provider) {
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'opencode-zen' => 'https://opencode.ai/zen/v1',
            'opencode-go' => 'https://opencode.ai/zen/go/v1',
            default => null,
        };
    }
}
