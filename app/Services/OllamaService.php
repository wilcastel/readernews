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
        // Remove style, script, svg to save context window
        $html = preg_replace('/<(script|style|svg)[^>]*>.*?<\/\1>/si', '', $html);
        $html = strip_tags($html, '<a><div><h1><h2><h3><h4><h5><p><span><li><ul><img><article><time>');
        // Limit length roughly (approx 40k chars ~ 10k tokens)
        // We focus on the "body" part usually
        return Str::limit($html, 50000); 
    }
}
