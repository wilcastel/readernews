<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Str;
use App\Models\AiConfig;

class ResearchService
{
    protected $scraper;
    protected $ollama;

    public function __construct(WebScraperService $scraper, OllamaService $ollama)
    {
        $this->scraper = $scraper;
        $this->ollama = $ollama;
    }

    public function searchAndResearch(string $topic, int $limit = 3, array $manualUrls = [])
    {
        // 1. Determine Source URLs
        $urls = $manualUrls;
        
        // If no passed URLs, try finding some
        if (empty($urls)) {
            // Check for Perplexity to find High-Quality Links
            $perplexityConfig = AiConfig::where('model_id', 'like', '%perplexity%')
                ->orWhere('name', 'like', '%perplexity%')
                ->orWhere('base_url', 'like', '%perplexity%')
                ->first();

            if ($perplexityConfig) {
                 try {
                     $urls = $this->getUrlsFromPerplexity($topic, $limit, $perplexityConfig);
                 } catch (\Exception $e) {
                     \Log::warning("Perplexity URL discovery failed, falling back to Web Search: " . $e->getMessage());
                 }
            }
            
            // Fallback to Standard Web Search (DDG/Google) if Perplexity failed or wasn't used
            if (empty($urls)) {
                $urls = $this->searchWeb($topic, $limit);
            }
        }

        // 2. Scrape & Analyze each URL (The "Deep Research" Loop)
        $findings = [];
        foreach ($urls as $url) {
            // Skip invalid items that might have slipped in
            if (!filter_var($url, FILTER_VALIDATE_URL)) continue;

            $data = $this->scraper->scrape($url);
            
            // Fallback: If standard scraper failed or content is too thin, use Browsershot
            if (isset($data['error']) || empty($data['content']) || strlen(strip_tags($data['content'])) < 500) {
                 try {
                    $html = Browsershot::url($url)
                        ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox'])
                        ->windowSize(1920, 1080)
                        ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36')
                        ->dismissDialogs()
                        ->ignoreHttpsErrors()
                        ->waitUntilNetworkIdle()
                        ->timeout(60)
                        ->bodyHtml();

                    $configuration = new \fivefilters\Readability\Configuration();
                    $configuration->setFixRelativeUrls(true);
                    $configuration->setOriginalURL($url);
                    $readability = new \fivefilters\Readability\Readability($configuration);
                    $readability->parse($html);
                    
                    $data = [
                        'title' => $readability->getTitle(),
                        'content' => $readability->getContent(),
                        'url' => $url
                    ];
                 } catch (\Exception $e) {
                     \Log::warning("Research Scrape Fallback failed for $url: " . $e->getMessage());
                     continue;
                 }
            }

            if (empty($data['content'])) continue;

            // Clean content slightly before sending to AI to save tokens
            $content = Str::limit(strip_tags($data['content'] ?? ''), 20000); 

            // Extract Journalistic Value using AI (Local or otherwise)
            // We append the URL context so the AI knows where this info comes from
            $analysis = $this->analyzeContent($topic, $data['title'] ?? 'No Title', $content);
            
            $findings[] = [
                'url' => $url,
                'title' => $data['title'] ?? 'Unknown',
                'analysis' => $analysis,
                'raw_content' => $content 
            ];
        }

        // 3. Compile into a Draft Article Body
        return $this->compileDraft($topic, $findings);
    }
    
    protected function getUrlsFromPerplexity(string $topic, int $limit, AiConfig $config): array
    {
        // Ask for MORE urls than needed to account for duplicates or junk
        $askLimit = $limit * 2;
        
        $prompt = <<<EOT
Task: Find at least $askLimit distinct, high-quality, and authoritative web, news, or academic articles about the topic: "$topic".
Output: Return ONLY a raw JSON array of strings containing the absolute URLs. 
Do not include any other text, markdown formatting, or numbering. 
Example format: ["https://site1.com/article", "https://site2.com/news"]
EOT;

        $model = $config->model_id ?: 'llama-3.1-sonar-small-128k-online';
        $timeHelper = date('Y-m-d');
        $json = $this->ollama->generateText("Current Date: $timeHelper\n" . $prompt, $config);
        
        $cleanJson = str_replace(['```json', '```', "\n"], '', $json);
        $cleanJson = trim($cleanJson);
        
        $urls = json_decode($cleanJson, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($urls)) {
            return array_slice($urls, 0, $limit);
        }
        
        // Fallback: Robust Regex
        // Matches standard URLs
        preg_match_all('#https?://[^\s()<>"]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $json, $matches);
        $extracted = $matches[0] ?? [];
        
        // Also look for Markdown links if standard regex missed them: [Title](url)
        preg_match_all('/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/', $json, $markdownMatches);
        if (!empty($markdownMatches[2])) {
            $extracted = array_merge($extracted, $markdownMatches[2]);
        }
        
        if (!empty($extracted)) {
            $finalUrls = [];
            foreach (array_unique($extracted) as $u) {
                if (
                    !str_contains($u, 'duckduckgo.com/y.js') && 
                    !str_contains($u, 'ad_domain') &&
                    !str_contains($u, 'google.com/aclk') &&
                    !str_contains($u, 'doubleclick.net')
                ) {
                    $finalUrls[] = $u;
                }
            }
            return array_slice($finalUrls, 0, $limit);
        }

        \Log::warning("Perplexity URL Parsing Failed. Raw Response: " . substr($json, 0, 500));
        throw new \Exception("Could not parse URLs from Perplexity response.");
    }



    protected function compileDraft(string $topic, array $findings): array
    {
        $body = "# Research Report: $topic\n\n";
        $body .= "Date: " . date('Y-m-d H:i') . "\n";
        $body .= "Sources Analyzed: " . count($findings) . "\n\n";
        
        $body .= "## Executive Synthesis (Automated)\n";
        $body .= "This report compiles key information extracted from " . count($findings) . " sources.\n\n";
        
        foreach ($findings as $index => $finding) {
            $num = $index + 1;
            
            // Header for the source
            $body .= "### Source $num: [{$finding['title']}]({$finding['url']})\n\n";
            
            // Provide the AI Analysis with spacing
            $body .= "**Key Insights:**\n\n";
            $body .= $finding['analysis'] . "\n\n";
            
            // Separator between Analysis and Raw Context
            $body .= "<br>\n\n";
            
            // CRITICAL: Provide the Raw Context for the Writer to use later
            // Increased limit to 25000 to capture full/long articles
            $body .= "<details><summary>Extracted Context (Raw Text)</summary>\n\n";
            $body .= "> " . str_replace("\n", "\n> ", Str::limit($finding['raw_content'], 25000)) . "\n";
            $body .= "</details>\n\n";
            
            // Final separator for the block
            $body .= "\n\n<br>\n\n"; 
            $body .= "---\n"; 
            $body .= "\n<br><br>\n\n";
        }
        
        return [
            'title' => "Research: $topic",
            'content' => $body,
            'findings' => $findings
        ];
    }

    protected function searchWeb(string $query, int $limit): array
    {
        $urls = $this->searchDuckDuckGo($query, $limit);
        if (empty($urls)) {
            $urls = $this->searchGoogle($query, $limit);
        }
        return $urls;
    }
    
    protected function searchDuckDuckGo(string $query, int $limit)
    {
        $url = 'https://html.duckduckgo.com/html/?q=' . urlencode($query);
        
        try {
            // DDG often requires a standard User-Agent to return results in HTML
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Referer' => 'https://html.duckduckgo.com/'
            ])->get($url);
            
            if (!$response->successful()) return [];
            
            $html = $response->body();
            $crawler = new Crawler($html);
            
            $urls = [];
            // DDG HTML results are usually in .result__a or .result__url
            $crawler->filter('.result__a')->each(function (Crawler $node) use (&$urls, $limit) {
                if (count($urls) >= $limit) return;
                
                $href = $node->attr('href');
                
                if ($href) {
                     // Check if it's a redirect
                     if (str_contains($href, 'duckduckgo.com/l/?uddg=')) {
                         parse_str(parse_url($href, PHP_URL_QUERY), $query);
                         if (isset($query['uddg'])) {
                             $urls[] = $query['uddg'];
                         }
                     } elseif (str_starts_with($href, 'http')) {
                         // Filter out Ads/Spam
                         if (
                            !str_contains($href, 'duckduckgo.com/y.js') && 
                            !str_contains($href, 'ad_domain') &&
                            !str_contains($href, 'google.com/aclk')
                         ) {
                            $urls[] = $href;
                         }
                     }
                }
            });
            
            return array_unique($urls);
            
        } catch (\Exception $e) {
            \Log::error("DDG Search Failed: " . $e->getMessage());
            return [];
        }
    }
    
    protected function searchGoogle(string $query, int $limit)
    {
        $url = 'https://www.google.com/search?q=' . urlencode($query);
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get($url);
            
            if (!$response->successful()) return [];
            
            $html = $response->body();
            $crawler = new Crawler($html);
            
            $urls = [];
            $crawler->filter('a')->each(function (Crawler $node) use (&$urls, $limit) {
                if (count($urls) >= $limit) return;
                $href = $node->attr('href');
                if ($href && str_contains($href, '/url?q=')) {
                     parse_str(parse_url($href, PHP_URL_QUERY), $matches);
                     if (isset($matches['q']) && str_starts_with($matches['q'], 'http') && !str_contains($matches['q'], 'google.com')) {
                         $urls[] = $matches['q'];
                     }
                }
            });
            
            return array_unique($urls);
        } catch (\Exception $e) {
            \Log::error("Google Search Failed: " . $e->getMessage());
            return [];
        }
    }
    
    protected function analyzeContent(string $topic, string $title, string $content): string
    {
        $prompt = <<<EOT
You are a journalistic researcher.
Topic: "$topic"
Article Title: "$title"
Content:
$content

Task: Extract the key journalistic value from this article regarding the topic.
Identify:
1. Key Facts/Figures
2. Quotes (verbatim if possible)
3. Main Arguments/Angles
4. Unique Data Points

Format: Markdown bullet points.
EOT;
        return $this->ollama->generateText($prompt);
    }
    

}
