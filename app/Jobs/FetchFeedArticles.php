<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\Feed;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SimplePie\SimplePie;
use Spatie\Browsershot\Browsershot;
use App\Services\OllamaService;

class FetchFeedArticles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Feed $feed)
    {
    }

    public function handle(OllamaService $ollama): void
    {
        try {
            $feedInfo = "=== Processing Feed: {$this->feed->name} (ID: {$this->feed->id}) ===";
            echo "\n" . $feedInfo . "\n";
            \Log::info($feedInfo);
            
            $urlInfo = "URL: {$this->feed->url}";
            echo $urlInfo . "\n";
            \Log::info($urlInfo);
            
            $typeInfo = "Type: " . ($this->feed->is_rss ? 'RSS Feed' : 'AI Scraper');
            echo $typeInfo . "\n";
            \Log::info($typeInfo);
            
            if (!$this->feed->is_rss) {
                $this->scrapeWithAi($ollama);
                return;
            }

            $this->fetchRss();
            
            $successMsg = "✅ SUCCESS: Feed '{$this->feed->name}' updated successfully";
            echo $successMsg . "\n";
            \Log::info($successMsg);
        } catch (\Exception $e) {
            $errorMsg = "❌ FAILED: Feed '{$this->feed->name}' - Error: " . $e->getMessage();
            echo $errorMsg . "\n";
            \Log::error($errorMsg);
            throw $e;
        }
    }

    protected function scrapeWithAi(OllamaService $ollama): void
    {
        $startMsg = "🤖 Starting AI scrape for feed: " . $this->feed->name;
        echo $startMsg . "\n";
        \Log::info($startMsg);
        
        try {
            \Log::info("Fetching via Browsershot: " . $this->feed->url);
            
            $html = Browsershot::url($this->feed->url)
                ->noSandbox()
                ->setOption('args', ['--disable-web-security'])
                ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36')
                ->windowSize(1920, 1080)
                ->waitUntilNetworkIdle()
                ->timeout(60)
                ->bodyHtml();

        } catch (\Exception $e) {
            \Log::warning("Browsershot connection failed (" . $e->getMessage() . "). Falling back to standard HTTP.");
            
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get($this->feed->url);
            
            if ($response->failed()) {
                 $errMsg = "❌ Failed to fetch HTML for feed '{$this->feed->name}': " . $this->feed->url;
                 echo $errMsg . "\n";
                 \Log::error($errMsg);
                 return;
            }
            $html = $response->body();
        }
        
        // 1. Selector Extraction (if specific selector provided)
        if ($this->feed->selector && $this->feed->selector !== 'body') {
            $dom = new \DOMDocument();
            @$dom->loadHTML($html, LIBXML_NOERROR);
            $xpath = new \DOMXPath($dom);
            // Try query
            $nodes = @$xpath->query("//" . $this->feed->selector); 
            // If that fails (invalid xpath), try class match
            if (!$nodes || $nodes->length === 0) {
                 // fallback to simpler check e.g. "article" logic inside service if needed
                 // but for now let's adhere to consistent logic
            } else {
                $extractedContent = '';
                foreach ($nodes as $node) {
                    $extractedContent .= $dom->saveHTML($node);
                }
                if (!empty($extractedContent)) {
                    $html = $extractedContent;
                }
            }
        }

        // 2. Clean and Limit (Crucial step added to match Diagnose)
        $cleanContent = strip_tags($html, '<a><h1><h2><h3><h4><h5><h6><p><article><li><ul><ol>');
        $cleanContent = substr($cleanContent, 0, 35000);

        \Log::info("Sending content length " . strlen($cleanContent) . " to AI Service.");

        $articles = $ollama->extractArticlesFromHtml($cleanContent, $this->feed->url);
        
        \Log::info("AI Service returned " . count($articles) . " articles.");

        // FEATURE request: Save the result momentarily
        try {
            \Illuminate\Support\Facades\Storage::put(
                'scrapes/feed_' . $this->feed->id . '_latest.json', 
                json_encode($articles, JSON_PRETTY_PRINT)
            );
            \Log::info("Saved scrape result to scrapes/feed_{$this->feed->id}_latest.json");
        } catch (\Exception $e) {
            \Log::warning("Could not save scrape dump: " . $e->getMessage());
        }

        $newCount = 0;
        $skipCount = 0;

        foreach ($articles as $item) {
            // Validate URL
            if (empty($item['url'])) {
                $skipCount++;
                continue;
            }

            // Fix relative URLs & Clean garbage
            $url = trim($item['url']);
            
            // Sometimes the model leaves a trailing pipe or "null" text if parsing failed
            $url = preg_replace('/\|\s*null$/i', '', $url);
            $url = trim($url, " \t\n\r\0\x0B|."); 

            // Check if it's already absolute
            if (preg_match('#^https?://#i', $url)) {
                 // It is absolute, just ensure it's valid
                 if (!filter_var($url, FILTER_VALIDATE_URL)) {
                     // Try to fix common issues like spaces encoded oddly
                     $url = str_replace(' ', '%20', $url);
                 }
            } else {
                // It is relative
                $baseUrl = rtrim($this->feed->url, '/');
                $url = $baseUrl . '/' . ltrim($url, '/');
            }

            // Final validity check
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                \Log::warning("Skipping invalid URL after cleanup: " . $url);
                $skipCount++;
                continue;
            }

            // Uniqueness check per feed
            if (Article::where('url', $url)->where('feed_id', $this->feed->id)->exists()) {
                $skipCount++;
                continue;
            }

            try {
                Article::create([
                    'feed_id' => $this->feed->id,
                    'title' => html_entity_decode($item['title']),
                    'url' => $url,
                    'author' => $item['author'] ?? null,
                    'summary' => $item['summary'] ?? '',
                    'published_at' => isset($item['date']) ? Carbon::parse($item['date']) : now(),
                    // Image extraction could be improved here
                ]);
                $newCount++;
            } catch (\Exception $e) {
                \Log::error("Error creating article: " . $e->getMessage());
            }
        }
        
        \Log::info("✅ AI Scrape Finished for feed '{$this->feed->name}': {$newCount} created, {$skipCount} duplicate/skipped.");
        
        $finishMsg = "✅ AI Scrape Finished for feed '{$this->feed->name}': {$newCount} created, {$skipCount} duplicate/skipped.";
        echo $finishMsg . "\n";

        $this->feed->update(['last_scraped_at' => now()]);
    }

    protected function fetchRss(): void
    {
        $startTime = microtime(true);
        $newArticles = 0;
        $skippedArticles = 0;
        
        $rssMsg = "📡 Starting RSS fetch for: {$this->feed->name}";
        echo $rssMsg . "\n";
        \Log::info($rssMsg);
        
        // 1. Try Standard SimplePie Fetch first
        $pie = new SimplePie();
        $pie->set_feed_url($this->feed->url);
        $pie->enable_cache(false); 
        $pie->set_useragent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $pie->init();
        $pie->handle_content_type();

        $items = $pie->get_items(0, 20);

        // 2. Fallback: Manual Download & Sanitize (if standard fetch fails)
        if (!$items) {
            \Log::warning("Standard RSS fetch failed/empty for {$this->feed->name}. Trying manual download & sanitize.");
            
            try {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'])
                    ->timeout(20)
                    ->get($this->feed->url);

                if ($response->successful()) {
                    $content = $response->body();
                    
                    // Sanitize XML level 1: Remove low ascii
                    $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $content);
                    
                    // Try SimplePie on sanitized content
                    $pie = new SimplePie();
                    $pie->set_raw_data($content);
                    $pie->enable_cache(false);
                    $pie->init();
                    $items = $pie->get_items(0, 20);

                    // 3. Ultra-Fallback: DOMDocument Recovery (for malformed XML like Banca y Negocios)
                    if (!$items) {
                         \Log::info("Sanitization failed for {$this->feed->name}. Attempting DOMDocument recovery.");
                         $originalLibXmlError = libxml_use_internal_errors(true);
                         
                         $dom = new \DOMDocument();
                         $dom->recover = true; // Key option
                         $dom->loadXML($content, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_RECOVER);
                         
                         if ($dom->documentElement) {
                             $recoveredXml = $dom->saveXML();
                             
                             $pie = new SimplePie();
                             $pie->set_raw_data($recoveredXml);
                             $pie->enable_cache(false);
                             $pie->init();
                             $items = $pie->get_items(0, 20);
                         }
                         
                         libxml_clear_errors();
                         libxml_use_internal_errors($originalLibXmlError);
                    }
                }
            } catch (\Exception $e) {
                \Log::error("Manual RSS fallback failed for {$this->feed->url}: " . $e->getMessage());
            }
        }

        // Auto-fix name if it's unknown
        if ($this->feed->name === 'Unknown Feed' || $this->feed->name === $this->feed->url) {
            $title = $pie->get_title();
            if ($title) {
                $this->feed->update([
                    'name' => html_entity_decode($title),
                    'description' => html_entity_decode($pie->get_description() ?? '')
                ]);
            }
        }

        foreach ($items as $item) {
            $url = $item->get_permalink();
            
            if (Article::where('url', $url)->where('feed_id', $this->feed->id)->exists()) {
                $skippedArticles++;
                continue;
            }

            $image = null;
            
            // 1. Try Media RSS (YouTube and others use this for thumbnails)
            $media_group = $item->get_item_tags('http://search.yahoo.com/mrss/', 'group');
            if ($media_group && isset($media_group[0]['child']['http://search.yahoo.com/mrss/']['thumbnail'][0]['attribs']['']['url'])) {
                $image = $media_group[0]['child']['http://search.yahoo.com/mrss/']['thumbnail'][0]['attribs']['']['url'];
            }

            // 2. Try Standard Enclosure (if no image yet)
            if (!$image && ($enclosure = $item->get_enclosure())) {
                $type = $enclosure->get_type();
                if (!$type || str_starts_with($type, 'image/')) {
                     $image = $enclosure->get_link();
                }
            }

            // 3. Try finding <img> in content
            if (!$image && preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $item->get_content(), $imageMatches)) {
                 $image = $imageMatches['src'];
            }

            // Robust date parsing
            $date = $item->get_date();
            $publishedAt = $date ? Carbon::parse($date) : now();

            Article::create([
                'feed_id' => $this->feed->id,
                'title' => html_entity_decode($item->get_title()),
                'url' => $url,
                'author' => $item->get_author()?->get_name(),
                'image_url' => $image,
                'summary' => strip_tags(html_entity_decode($item->get_description())), 
                'content' => $item->get_content(),
                'published_at' => $publishedAt,
            ]);
            $newArticles++;
        }
        
        $elapsedTime = number_format((microtime(true) - $startTime) * 1000, 2);
        $finishMsg = "✅ RSS Feed '{$this->feed->name}' finished: {$newArticles} articles added, {$skippedArticles} duplicates skipped. ({$elapsedTime}ms)";
        echo $finishMsg . "\n";
        \Log::info($finishMsg);

        $this->feed->update(['last_scraped_at' => now()]);
    }
}
