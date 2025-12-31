<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Setting;

class OllamaService
{
    protected string $baseUrl;
    protected string $model;
    protected string $provider; // 'ollama' or 'openrouter'
    protected string $apiKey;

    public function __get($name)
    {
        if (in_array($name, ['baseUrl', 'model', 'provider'])) {
            return $this->$name;
        }
        return null;
    }

    public function __construct()
    {
        try {
            $settings = Setting::where('group', 'ai')->pluck('value', 'key');
        } catch (\Exception $e) {
            $settings = collect([]); // Fallback during migration/seeding
        }

        $this->provider = $settings['ai_provider'] ?? config('services.ai.provider', 'ollama');
        
        if ($this->provider === 'openrouter') {
            $this->baseUrl = 'https://openrouter.ai/api/v1/chat/completions';
            $this->model = $settings['openrouter_model'] ?? 'google/gemini-2.0-flash-exp:free';
            $this->apiKey = $settings['openrouter_key'] ?? '';
        } elseif ($this->provider === 'openai') {
            // Generic OpenAI (LM Studio, LocalAI, etc)
            $this->baseUrl = $settings['openai_url'] ?? 'http://localhost:1234/v1/chat/completions';
            $this->model = $settings['openai_model'] ?? 'local-model';
            $this->apiKey = $settings['openai_key'] ?? 'lm-studio';
        } else {
            // Ollama Default
            $this->baseUrl = $settings['ollama_url'] ?? config('services.ollama.base_url', 'http://localhost:11434');
            $this->model = $settings['ollama_model'] ?? config('services.ollama.model', 'qwen3:4b');
        }
    }

    public function extractArticlesFromHtml(string $html, ?string $selector = null): array
    {
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

            if ($this->provider === 'openrouter' || $this->provider === 'openai') {
                $responseText = $this->askOpenAICompatible($prompt);
            } else {
                $responseText = $this->askOllamaBridge($prompt);
            }

            if (empty($responseText)) return [];

            return $this->parseResponse($responseText);

        } catch (\Exception $e) {
            \Log::error('AI Service Error: ' . $e->getMessage());
            return [];
        }
    }



    public function generateText(string $prompt, ?\App\Models\AiConfig $config = null): string
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
            $model = $config->model_id; // Using field 'model_id' from table

            // Logic to handle empty BaseURLs for known providers if needed
            if (empty($baseUrl)) {
                if ($provider === 'openrouter') $baseUrl = 'https://openrouter.ai/api/v1/chat/completions';
                // Add more defaults if needed, e.g. OpenAI official
            }
        }

        // 2. Dispatch
        if ($provider === 'openrouter' || $provider === 'openai') {
            return $this->askOpenAICompatible($prompt, $baseUrl, $apiKey, $model);
        } else {
            // Ollama: we need to pass the custom URL if it's different, 
            // but the bridge script currently reads ENV or uses default.
            // If the user sets a custom Base URL for Ollama Config, we might need to modify the bridge or passed params.
            // For now, assume common Ollama bridge handles 'model'. 
            // Note: The bridge script currently hardcodes localhost:11434 usually unless passed.
            // Let's pass the URL to the bridge if supported, or just model.
            return $this->askOllamaBridge($prompt, $model, $baseUrl); 
        }
    }

    protected function askOllamaBridge(string $prompt, string $modelOverride = '', string $urlOverride = ''): string
    {
        $model = $modelOverride ?: $this->model;
        \Log::info("Sending request to Ollama via Bridge: {$model}");
            
        $payloadData = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
            'format' => '', 
            'options' => ['temperature' => 0.1]
        ];

        // Ensure we handle URL override if your bridge supports it. 
        // If the bridge script allows custom host via args or payload, usage here.
        // Assuming your bridge script primarily just talks to standard port. 
        // We will keep it simple: model is the key variant.
        
        $payload = json_encode($payloadData, JSON_UNESCAPED_SLASHES);
        
        $process = new \Symfony\Component\Process\Process([
            'node', 
            base_path('scripts/ollama-bridge.cjs')
        ]);
        
        // Pass custom URL via ENV if needed by the node script
        if ($urlOverride) {
            $process->setEnv(['OLLAMA_HOST' => $urlOverride]);
        }

        $process->setInput($payload);
        $process->setTimeout(600);
        $process->run();
        
        if (!$process->isSuccessful()) {
                \Log::error('Ollama Bridge failed: ' . $process->getErrorOutput());
                return '';
        }
        
        $output = $process->getOutput();
        $jsonResponse = json_decode($output, true);
        
        return $jsonResponse['response'] ?? '';
    }

    protected function askOpenAICompatible(string $prompt, string $url = '', string $key = '', string $model = ''): string
    {
        // Fallbacks to instance defaults
        $url = $url ?: $this->baseUrl;
        $key = $key ?: $this->apiKey;
        $model = $model ?: $this->model;

        \Log::info("Sending request to OpenAI Compatible API: {$model} at {$url}");

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
            \Log::error('AI API Failed: ' . $response->body());
            return '';
        }

        $json = $response->json();
        
        if (isset($json['usage'])) {
             \Log::info("AI Usage: " . json_encode($json['usage']));
        }

        return $json['choices'][0]['message']['content'] ?? '';
    }

    protected function parseResponse(string $responseText): array
    {
        $articles = [];
            
        // STRATEGY 1: Parse as pipe-delimited text lines
        \Log::info("Raw LLM Response: " . substr($responseText, 0, 500) . "..."); // Log first 500 chars

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
        
        // STRATEGY 2: Fallback to JSON extraction
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
        // Strategy Change: Instead of cleaning HTML, we extract the structural data
        // of links and texts directly. This turns 1MB of HTML into ~5KB of text context.
        
        $dom = new \DOMDocument();
        $originalLibXmlError = libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_use_internal_errors($originalLibXmlError);

        $xpath = new \DOMXPath($dom);
        
        // Remove obviously non-content distinct areas to reduce noise if no selector overrides
        if (!$selector) {
            foreach ($xpath->query('//script|//style|//nav|//footer|//header|//svg|//form') as $node) {
                $node->parentNode->removeChild($node);
            }
        }

        $output = [];
        $uniqueLinks = [];

        // Extract all Links with significant text
        // If selector is provided, scoped to that validation
        $nodes = null;
        if ($selector) {
             // If selector targets container, find 'a' inside. If targets 'a', use directly.
             // We can use a simple query like "$selector//a | $selector[self::a]"
             // But CSS to XPath conversion is complex. Let's assume user provides XPath or simple tag/id.
             // For now, let's assume specific ID or Class.
             // To support "CSS Selectors" nicely in PHP DOM without libs is hard.
             // Let's rely on specific XPath for now if it starts with /, else custom logic.
             // Wait, user will type ".class". We need simpler "str_contains" logic or similar if we don't include a library.
             // LIMITATION: Use Symfony CssSelector if installed, or assume XPath.
             // Let's try to query strict XPath for now, or just assume the user puts a class name and we search for div with that class.
             
             // Simple hack for now: If user puts ".class", we convert to `//*[@class and contains(concat(' ', normalize-space(@class), ' '), ' class ')]`
             // If #id, `//*[@id='id']`
             // If simple tag `main`, `//main`
             
             $query = $selector; // Assume valid XPath if complex, or simple map:
             if (str_starts_with($selector, '.')) {
                 $class = substr($selector, 1);
                 $query = "//*[contains(concat(' ', normalize-space(@class), ' '), ' $class ')]//a";
             } elseif (str_starts_with($selector, '#')) {
                 $id = substr($selector, 1);
                 $query = "//*[@id='$id']//a";
             } elseif (!str_starts_with($selector, '/')) {
                 // Assume tag name
                 $query = "//{$selector}//a";
             }
             
             try {
                $nodes = $xpath->query($query);
             } catch(\Exception $e) {
                // Fallback
                $nodes = $xpath->query('//a'); 
             }
        } else {
             $nodes = $xpath->query('//a');
        }
        
        if (!$nodes || $nodes->length === 0) {
             // Fallback if selector yields nothing
             $nodes = $xpath->query('//a');
        }
        
        foreach ($nodes as $node) {
            $url = $node->getAttribute('href');
            $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
            
            // Basic filtering
            if (empty($url) || strlen($text) < 10 || str_starts_with($url, '#') || str_starts_with($url, 'javascript:')) {
                continue;
            }
            
            // Check for image inside (some sites use image as title link)
            $imgSrc = '';
            $imgs = $node->getElementsByTagName('img');
            if ($imgs->length > 0) {
                $imgSrc = $imgs->item(0)->getAttribute('src');
            }

            // Deduplication
            if (isset($uniqueLinks[$url])) continue;
            $uniqueLinks[$url] = true;

            $entry = "Link: \"$text\" | URL: $url";
            if ($imgSrc) $entry .= " | Image: $imgSrc";
            
            $output[] = $entry;
        }

        // Return a raw list of candidates. 
        // The LLM is smart enough to pick the "News" ones from this list.
        return implode("\n", array_slice($output, 0, 300)); // Limit to first 300 detected links to fit context
    }
}
