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

        $articles = $ollama->extractArticlesFromHtml($response->body());

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
        $pie = new SimplePie();
        $pie->set_feed_url($this->feed->url);
        $pie->enable_cache(false); 
        $pie->init();

        $items = $pie->get_items(0, 20); 

        foreach ($items as $item) {
            $url = $item->get_permalink();
            
            if (Article::where('url', $url)->where('feed_id', $this->feed->id)->exists()) continue;

            $image = null;
            if ($enclusure = $item->get_enclosure()) {
                $image = $enclusure->get_link();
            }
            if (!$image && preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $item->get_content(), $imageMatches)) {
                 $image = $imageMatches['src'];
            }

            Article::create([
                'feed_id' => $this->feed->id,
                'title' => html_entity_decode($item->get_title()),
                'url' => $url,
                'author' => $item->get_author()?->get_name(),
                'image_url' => $image,
                'summary' => strip_tags(html_entity_decode($item->get_description())), 
                'content' => $item->get_content(),
                'published_at' => Carbon::parse($item->get_date()),
            ]);
        }
        
        $this->feed->update(['last_scraped_at' => now()]);
    }
}
