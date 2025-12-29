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
use App\Services\OllamaService;

class FetchFeedArticles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Feed $feed)
    {
    }

    public function handle(OllamaService $ollama): void
    {
        if (!$this->feed->is_rss) {
            $this->scrapeWithAi($ollama);
            return;
        }

        $this->fetchRss();
    }

    protected function scrapeWithAi(OllamaService $ollama): void
    {
        $response = \Illuminate\Support\Facades\Http::withoutVerifying()->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ])->get($this->feed->url);
        
        if ($response->failed()) {
             \Log::error("Failed to fetch HTML for AI scraping: " . $this->feed->url);
             return;
        }

        $articles = $ollama->extractArticlesFromHtml($response->body(), $this->feed->selector);

        foreach ($articles as $item) {
            // Validate URL
            if (empty($item['url'])) continue;

            // Fix relative URLs
            $url = $item['url'];
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $baseUrl = rtrim($this->feed->url, '/');
                $url = $baseUrl . '/' . ltrim($url, '/');
            }

            // Uniqueness check per feed
            if (Article::where('url', $url)->where('feed_id', $this->feed->id)->exists()) continue;

            Article::create([
                'feed_id' => $this->feed->id,
                'title' => html_entity_decode($item['title']),
                'url' => $url,
                'author' => $item['author'] ?? null,
                'summary' => $item['summary'] ?? '',
                'published_at' => isset($item['date']) ? Carbon::parse($item['date']) : now(),
                // Image extraction could be improved here
            ]);
        }

        $this->feed->update(['last_scraped_at' => now()]);
    }

    protected function fetchRss(): void
    {
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
            
            if (Article::where('url', $url)->where('feed_id', $this->feed->id)->exists()) continue;

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
        }
        
        $this->feed->update(['last_scraped_at' => now()]);
    }
}
