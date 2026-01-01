<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OllamaService;
use Illuminate\Support\Facades\Http;

class DiagnosticOllama extends Command
{
    protected $signature = 'ollama:diagnose {url} {selector?}';
    protected $description = 'Deep diagnostic for AI Scraping';

    public function handle(OllamaService $ollama)
    {
        $url = $this->argument('url');
        $selector = $this->argument('selector');
        $this->info("1. Fetching URL: $url");
        if ($selector) $this->info("   Using Selector: $selector");

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ])
                ->timeout(20)
                ->get($url);

            if ($response->failed()) {
                $this->error("HTTP Failed: " . $response->status());
                return;
            }

            $html = $response->body();
            $this->info("   HTTP Success. Length: " . strlen($html));
            
            // Allow accessing protected method via reflection for debugging
            $reflection = new \ReflectionClass($ollama);
            $method = $reflection->getMethod('cleanHtmlForContext');
            $method->setAccessible(true);
            
            $this->info("   Calling cleanHtmlForContext...");
            $cleanHtml = $method->invoke($ollama, $html, $selector);
            $this->info("2. Cleaned HTML Length: " . strlen($cleanHtml));
            $this->info("   Snippet of Clean HTML:\n" . substr($cleanHtml, 0, 500) . "...");

            $this->info("3. Sending to Ollama...");
            $startTime = microtime(true);
            
            // We need to capture the raw response to debug
            $response = Http::timeout(300)->connectTimeout(10)->post("{$ollama->baseUrl}/api/generate", [
                'model' => $ollama->model,
                'prompt' => <<<EOT
You are an expert web scraper. Analyze the HTML content below and extract a list of news articles found in it.
Return ONLY a valid JSON object with a key "articles" containing an array of objects.
Each object must have:
- "title": string
- "url": string (absolute URL preferred)
- "summary": string (brief description)

HTML Content:
$cleanHtml
EOT
,
                'stream' => false,
                'format' => 'json',
            ]);
            
            $duration = microtime(true) - $startTime;
            $this->info("4. Ollama Response Time: " . round($duration, 2) . "s");
            
            // ... debug logic ...
            
            $articles = $ollama->extractArticlesFromHtml($html, $selector);
            
            if (empty($articles)) {
                $this->error("   RESULT: 0 Articles found.");
                $this->warn("   Possible reasons: Context limit exceeded, model timeout, or model confusion.");
            } else {
                $this->info("   RESULT: " . count($articles) . " articles found!");
                foreach ($articles as $a) {
                    $this->line("   - " . ($a['title'] ?? 'No Title'));
                }
            }

        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage());
        }
    }
}
