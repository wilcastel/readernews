<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OllamaService
{
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        // Defaults to localhost:11434 with llama3
        $this->baseUrl = config('services.ollama.base_url', 'http://localhost:11434');
        $this->model = config('services.ollama.model', 'llama3');
    }

    public function extractArticlesFromHtml(string $html): array
    {
        // 1. Clean HTML to reduce token usage
        $cleanHtml = $this->cleanHtmlForContext($html);

        // 2. Construct Prompt
        $prompt = <<<EOT
You are an expert web scraper. Analyze the HTML content below and extract a list of news articles found in it.
Return ONLY a valid JSON object with a key "articles" containing an array of objects.
Each object must have:
- "title": string
- "url": string (absolute URL preferred, if relative keep it as is)
- "summary": string (brief description if available, else first sentence)
- "author": string (optional)
- "date": string (optional, format YYYY-MM-DD if found)

Focus on the main list of articles (e.g. latest news, features). Ignore navigation links, footers, and ads.

HTML Content (Truncated):
$cleanHtml
EOT;

        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}/api/generate", [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json', // Force JSON mode
            ]);

            if ($response->failed()) {
                \Log::error('Ollama connection failed: ' . $response->body());
                return [];
            }

            $json = $response->json('response');
            
            // Try to fix common JSON issues from LLMs (e.g. Markdown code blocks)
            $json = preg_replace('/^```json\s*/', '', $json);
            $json = preg_replace('/\s*```$/', '', $json);

            $data = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                 \Log::error('Ollama JSON parse error: ' . json_last_error_msg() . ' Content: ' . $json);
                 return [];
            }

            return $data['articles'] ?? [];

        } catch (\Exception $e) {
            \Log::error('Ollama processing error: ' . $e->getMessage());
            return [];
        }
    }

    protected function cleanHtmlForContext(string $html): string
    {
        // 1. Remove styles, scripts, SVGs, iframes, noscripts
        $html = preg_replace('/<(script|style|svg|iframe|noscript|nav|footer|header|aside)[^>]*>.*?<\/\1>/si', '', $html);
        
        // 2. Remove comments
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // 3. Keep only essential tags for structure
        $html = strip_tags($html, '<a><h1><h2><h3><h4><h5><p><li><ul><article><time><img><span><div>');

        // 4. Aggressively remove class, id, style attributes to save tokens
        // This regex removes all attributes except href and src
        $html = preg_replace('/<([a-z][a-z0-9]*)[^>]*?(\s(href|src)=["\'][^"\']*["\'])?[^>]*?>/i', '<$1$2>', $html);
        
        // 5. Compress whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        
        // 6. Limit length strictly to fit context (approx 30k chars is safer for small models)
        return Str::limit($html, 30000); 
    }
}
