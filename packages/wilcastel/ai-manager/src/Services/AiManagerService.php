<?php

namespace Wilcastel\AiManager\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Wilcastel\AiManager\Models\AiConfig;

class AiManagerService
{
    protected string $baseUrl;
    protected string $model;
    protected string $provider; // 'ollama', 'openrouter', 'openai'
    protected string $apiKey = '';

    public function __get($name)
    {
        if (in_array($name, ['baseUrl', 'model', 'provider'])) {
            return $this->$name;
        }
        return null;
    }

    public function __construct()
    {
        // Load defaults from package config
        $this->provider = config('ai-manager.default_provider', 'ollama');
        
        $configKey = "ai-manager.{$this->provider}";
        
        if ($this->provider === 'openrouter') {
            $this->baseUrl = config("{$configKey}.base_url", 'https://openrouter.ai/api/v1/chat/completions');
            $this->model = config("{$configKey}.model", 'google/gemini-2.0-flash-exp:free');
            $this->apiKey = config("{$configKey}.api_key", '');
        } elseif ($this->provider === 'openai') {
            $this->baseUrl = config("{$configKey}.base_url", 'http://localhost:1234/v1/chat/completions');
            $this->model = config("{$configKey}.model", 'local-model');
            $this->apiKey = config("{$configKey}.api_key", 'lm-studio');
        } else {
            // Ollama Default
            $this->baseUrl = config("{$configKey}.base_url", 'http://localhost:11434');
            $this->model = config("{$configKey}.model", 'qwen3:4b');
        }
    }

    public function extractArticlesFromHtml(string $html, ?string $selector = null): array
    {
        // SMART FALLBACK / ROUND ROBIN: 
        // If we are using default Ollama (local) but have Remote Configs active in DB,
        // switch to one of them to ensure this works on Remote Servers where localhost:11434 is missing.
        // Or simply if we want to distribute load.
        if ($this->provider === 'ollama' || $this->baseUrl === 'http://localhost:11434') {
             try {
                 $remoteConfig = AiConfig::where('is_active', true)
                    ->where('provider', '!=', 'ollama')
                    ->where('mode', '!=', 'local') 
                    ->inRandomOrder() 
                    ->first();
                    
                 if ($remoteConfig) {
                     \Log::info("AiManager: Switching Provider for extraction: Local -> " . $remoteConfig->name);
                     $this->useConfig($remoteConfig);
                 }
             } catch (\Exception $e) {
                 // Table might not exist yet or connection issue
                 \Log::warning("AiManager: Could not check AiConfigs: " . $e->getMessage());
             }
        }

        // 1. Clean HTML to reduce token usage
        $cleanHtml = $this->cleanHtmlForContext($html, $selector);

        // 2. Construct Prompt
        $prompt = <<<EOT
Extract news articles from the list below.
Output EXACTLY one article per line using this format:
Title ||| URL ||| ImageURL

Rules:
1. No Markdown. No introductory text.
2. Only valid news articles.
3. If no image, put "null" or leave empty.
4. Use "|||" as separator.

Input:
$cleanHtml
EOT;

        try {
            $responseText = '';

            // Check provider again after potential swap
            if (in_array($this->provider, ['openrouter', 'openai', 'groq', 'cerebras'])) {
                $responseText = $this->askOpenAICompatible($prompt);
            } else {
                $responseText = $this->askOllamaBridge($prompt);
            }

            if (empty($responseText)) return [];

            return $this->parseResponse($responseText);

        } catch (\Exception $e) {
            \Log::error('AiManager Service Error: ' . $e->getMessage());
            return [];
        }
    }

    // Helper to switch context dynamically
    public function useConfig(AiConfig $config)
    {
        $this->provider = $config->provider;
        $this->baseUrl = $config->base_url;
        $this->apiKey = $config->api_key;
        $this->model = $config->model_id;
        
        // Normalize provider for dispatch logic
        if (!in_array($this->provider, ['ollama', 'openrouter', 'openai'])) {
             // Most custom providers (Groq, Cerebras, etc) are OpenAI compatible
             $this->provider = 'openai';
        }
    }

    /**
     * Generate generic text response
     */
    public function generateText(string $prompt, ?AiConfig $config = null): string
    {
        // 1. Determine parameters (Default vs Config)
        $provider = $this->provider;
        $baseUrl = $this->baseUrl;
        $apiKey = $this->apiKey;
        $model = $this->model;

        if ($config) {
            $provider = $config->provider;
            $baseUrl = $config->base_url;
            $apiKey = $config->api_key;
            $model = $config->model_id; 

            if (empty($baseUrl)) {
                if ($provider === 'openrouter') $baseUrl = 'https://openrouter.ai/api/v1/chat/completions';
            }
            
             if (!in_array($provider, ['ollama', 'openrouter', 'openai'])) {
                 $provider = 'openai';
            }
        }

        // 2. Dispatch
        if ($provider === 'openrouter' || $provider === 'openai') {
            return $this->askOpenAICompatible($prompt, $baseUrl, $apiKey, $model);
        } else {
            return $this->askOllamaBridge($prompt, $model, $baseUrl); 
        }
    }

    protected function askOllamaBridge(string $prompt, string $modelOverride = '', string $urlOverride = ''): string
    {
        $model = $modelOverride ?: $this->model;
        \Log::info("AiManager: Requesting Ollama via Bridge: {$model}");
            
        $payloadData = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
            'format' => '', 
            'options' => ['temperature' => 0.1]
        ];

        // Locate the bridge script
        // 1. Check config path (relative to base_path)
        $scriptPath = base_path(config('ai-manager.ollama.bridge_path', 'scripts/ollama-bridge.cjs'));
        
        // 2. If not found, check if it exists in the package vendor directory (fallback)
        // This is tricky. simpler to assume user published it or we published it.
        // For now, rely on correct config.
        
        if (!file_exists($scriptPath)) {
             \Log::error("AiManager: Bridge script not found at $scriptPath");
             return '';
        }
        
        $payload = json_encode($payloadData, JSON_UNESCAPED_SLASHES);
        
        $process = new \Symfony\Component\Process\Process([
            'node', 
            $scriptPath
        ]);
        
        if ($urlOverride) {
            $process->setEnv(['OLLAMA_HOST' => $urlOverride]);
        }

        $process->setInput($payload);
        $process->setTimeout(600);
        $process->run();
        
        if (!$process->isSuccessful()) {
                \Log::error('AiManager Bridge failed: ' . $process->getErrorOutput());
                return '';
        }
        
        $output = $process->getOutput();
        $jsonResponse = json_decode($output, true);
        
        return $jsonResponse['response'] ?? '';
    }

    protected function askOpenAICompatible(string $prompt, string $url = '', string $key = '', string $model = ''): string
    {
        $url = $url ?: $this->baseUrl;
        $key = $key ?: $this->apiKey;
        $model = $model ?: $this->model;

        if (!empty($url) && !str_ends_with($url, '/chat/completions')) {
            if (str_ends_with($url, '/v1')) {
                $url .= '/chat/completions';
            } elseif (str_ends_with($url, '/v1/')) {
                $url .= 'chat/completions';
            }
        }

        \Log::info("AiManager: Requesting OpenAI Compatible API: {$model} at {$url}");

        $response = Http::withToken($key)
            ->withHeaders([
                'HTTP-Referer' => config('app.url'), 
                'X-Title' => config('app.name'),
            ])
            ->timeout(120) 
            ->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.1,
            ]);

        if ($response->failed()) {
            \Log::error('AiManager API Failed: ' . $response->body());
            return '';
        }

        $json = $response->json();
        
        if (isset($json['usage'])) {
             \Log::info("AiManager Usage: " . json_encode($json['usage']));
        }

        return $json['choices'][0]['message']['content'] ?? '';
    }

    protected function parseResponse(string $responseText): array
    {
        $articles = [];
        // Raw Log
        \Log::debug("AiManager Raw LLM Response: " . substr($responseText, 0, 500) . "..."); 

        $lines = explode("\n", $responseText);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (str_contains($line, '|||')) {
                $parts = explode('|||', $line);
                if (count($parts) >= 2) {
                    $title = trim($parts[0]);
                    $url = trim($parts[1]);
                    $image = isset($parts[2]) ? trim($parts[2]) : null;
                    if ($image === 'null') $image = null;
                    
                    if (strlen($title) > 5 && !empty($url)) {
                        $articles[] = [
                            'title' => $title,
                            'url' => $url,
                            'image' => $image,
                            'summary' => ''
                        ];
                    }
                }
            }
        }
        
        if (empty($articles)) {
            $start = strpos($responseText, '[');
            $end = strrpos($responseText, ']');
            
            if ($start !== false && $end !== false && $end > $start) {
                $jsonCandidate = substr($responseText, $start, $end - $start + 1);
                $data = json_decode($jsonCandidate, true);
                
                if (is_array($data)) {
                    foreach ($data as $item) {
                        if (isset($item['title']) && isset($item['url'])) {
                                $articles[] = [
                                'title' => $item['title'],
                                'url' => $item['url'],
                                'image' => $item['image'] ?? null,
                                'summary' => $item['summary'] ?? ''
                            ];
                        }
                    }
                }
            }
        }

        return $articles;
    }

    protected function cleanHtmlForContext(string $html, ?string $selector = null): string
    {
        $dom = new \DOMDocument();
        $originalLibXmlError = libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_use_internal_errors($originalLibXmlError);

        $xpath = new \DOMXPath($dom);
        
        if (!$selector) {
            foreach ($xpath->query('//script|//style|//nav|//footer|//header|//svg|//form') as $node) {
                $node->parentNode->removeChild($node);
            }
        }

        $output = [];
        $uniqueLinks = [];
        $nodes = null;
        
        if ($selector) {
             $query = $selector; 
             if (str_starts_with($selector, '.')) {
                 $class = substr($selector, 1);
                 $query = "//*[contains(concat(' ', normalize-space(@class), ' '), ' $class ')]//a";
             } elseif (str_starts_with($selector, '#')) {
                 $id = substr($selector, 1);
                 $query = "//*[@id='$id']//a";
             } elseif (!str_starts_with($selector, '/')) {
                 $query = "//{$selector}//a";
             }
             try {
                $nodes = $xpath->query($query);
             } catch(\Exception $e) {
                $nodes = $xpath->query('//a'); 
             }
        } else {
             $nodes = $xpath->query('//a');
        }
        
        if (!$nodes || $nodes->length === 0) {
             $nodes = $xpath->query('//a');
        }
        
        foreach ($nodes as $node) {
            $url = $node->getAttribute('href');
            $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
            
            if (empty($url) || strlen($text) < 10 || str_starts_with($url, '#') || str_starts_with($url, 'javascript:')) {
                continue;
            }
            
            $imgSrc = '';
            $imgs = $node->getElementsByTagName('img');
            if ($imgs->length > 0) {
                $imgSrc = $imgs->item(0)->getAttribute('src');
            }

            if (isset($uniqueLinks[$url])) continue;
            $uniqueLinks[$url] = true;

            $entry = "Link: \"$text\" | URL: $url";
            if ($imgSrc) $entry .= " | Image: $imgSrc";
            
            $output[] = $entry;
        }

        return implode("\n", array_slice($output, 0, 300));
    }
}
