<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Services\OllamaService; // eventually adapt this service
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::where('group', 'ai')->pluck('value', 'key');
        
        return view('settings.index', [
            'provider' => $settings['ai_provider'] ?? 'ollama',
            'ollama_url' => $settings['ollama_url'] ?? 'http://localhost:11434',
            'ollama_model' => $settings['ollama_model'] ?? 'qwen3:4b',
            'openrouter_key' => $settings['openrouter_key'] ?? '',
            'openrouter_model' => $settings['openrouter_model'] ?? 'google/gemini-2.0-flash-exp:free',
            'openai_url' => $settings['openai_url'] ?? 'http://localhost:1234/v1/chat/completions',
            'openai_key' => $settings['openai_key'] ?? '',
            'openai_model' => $settings['openai_model'] ?? 'local-model',
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'ai_provider' => 'required|in:ollama,openrouter,openai',
            'ollama_url' => 'nullable|required_if:ai_provider,ollama',
            'ollama_model' => 'nullable|required_if:ai_provider,ollama',
            'openrouter_key' => 'nullable',
            'openrouter_model' => 'nullable',
            'openai_url' => 'nullable|required_if:ai_provider,openai',
            'openai_key' => 'nullable',
            'openai_model' => 'nullable',
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'ai']
            );
        }

        return redirect()->route('settings.index')->with('success', 'Configuración actualizada correctamente.');
    }

    public function fetchModels(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:ollama,openai,openrouter',
            'url' => 'required',
            'key' => 'nullable'
        ]);

        $provider = $request->provider;
        $url = rtrim($request->url, '/');
        $apiKey = $request->key;
        $models = [];

        try {
            if ($provider === 'ollama') {
                // Ollama API: /api/tags
                $response = Http::timeout(5)->get("$url/api/tags");
                if ($response->successful()) {
                    $block = $response->json();
                    if (isset($block['models'])) {
                        foreach ($block['models'] as $m) {
                            $models[] = $m['name']; // e.g., llama3:latest
                        }
                    }
                }
            } elseif ($provider === 'openai' || $provider === 'openrouter') {
                // OpenAI Compatible (LM Studio, OpenRouter): /models
                // Note: User provides base url like .../v1/chat/completions. We need .../v1/models
                $baseUrl = str_replace('/chat/completions', '', $url);
                if ($provider === 'openrouter') {
                    $baseUrl = 'https://openrouter.ai/api/v1';
                }
                
                $response = Http::withToken($apiKey)->timeout(10)->get("$baseUrl/models");
                
                if ($response->successful()) {
                    $data = $response->json();
                    // OpenRouter/OpenAI usually returns { data: [ { id: ... }, ... ] }
                    if (isset($data['data'])) {
                        foreach ($data['data'] as $m) {
                            $models[] = $m['id'];
                        }
                    }
                    // Some local servers might return just list
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['models' => $models]);
    }

    public function testModel(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:ollama,openai,openrouter',
            'url' => 'required',
            'model' => 'required',
            'key' => 'nullable'
        ]);

        $settings = [
            'ai_provider' => $request->provider,
            'ollama_url' => $request->url,
            'ollama_model' => $request->model,
            'openrouter_key' => $request->key,
            'openrouter_model' => $request->model,
            'openai_url' => $request->url,
            'openai_key' => $request->key,
            'openai_model' => $request->model,
        ];

        // Temporarily override config/settings for this test instance
        // We create a new Service instance just for this test
        $service = new OllamaService();
        
        // Manually inject the test configuration into the service properties
        // Reflection is one way, or we can make a 'configure' method on the service.
        // For now, let's use a simpler approach: create a temporary manual request to the provider directly
        // to avoid messing with the Service singleton or constructor logic which reads from DB.
        
        $provider = $request->provider;
        $url = rtrim($request->url, '/');
        $model = $request->model;
        $apiKey = $request->key;

        $startTime = microtime(true);
        $reply = '';

        try {
            if ($provider === 'ollama') {
                // Determine if we should use the Node Bridge or direct API
                // For a simple 'hello' test, direct API is usually faster and enough to prove connectivity
                // unless it's a docker container issue. Let's try direct API /api/generate first.
                $response = Http::timeout(10)->post("$url/api/generate", [
                    'model' => $model,
                    'prompt' => 'Say "OK"',
                    'stream' => false
                ]);
                
                if ($response->successful()) {
                    $reply = $response->json()['response'] ?? 'OK';
                } else {
                    throw new \Exception('Ollama connection failed: ' . $response->status());
                }

            } elseif ($provider === 'openai' || $provider === 'openrouter') {
                $baseUrl = $provider === 'openrouter' ? 'https://openrouter.ai/api/v1/chat/completions' : $url;
                
                $response = Http::withToken($apiKey)->timeout(15)->post($baseUrl, [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Say "OK"']
                    ]
                ]);

                if ($response->successful()) {
                    $reply = $response->json()['choices'][0]['message']['content'] ?? 'OK';
                } else {
                    throw new \Exception('API Error: ' . $response->body());
                }
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        $duration = round(microtime(true) - $startTime, 2);

        return response()->json([
            'success' => true,
            'message' => "Model responded in {$duration}s: " . Str::limit($reply, 50)
        ]);
    }
}
