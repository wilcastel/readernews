<?php

namespace App\Services;

use SimplePie\SimplePie;
use Illuminate\Support\Facades\Http;

class FeedDiscoveryService
{
    public function discover(string $url): array
    {
        // 1. Try to fetch as RSS directly
        $feed = new SimplePie();
        $feed->set_feed_url($url);
        $feed->enable_cache(false);
        $feed->set_useragent('ReaderNews/1.0 (+http://readernews.test)');
        $feed->init();

        if ($feed->error()) {
            // It might be a regular website, let's try to find the RSS link in HTML or fallback to Scraping Mode
            return $this->analyzeWebPage($url);
        }

        return [
            'type' => 'rss',
            'title' => $feed->get_title(),
            'description' => $feed->get_description(),
            'site_url' => $feed->get_permalink(),
            'feed_url' => $url,
            'favicon' => $this->getFavicon($feed->get_permalink()),
        ];
    }

    protected function analyzeWebPage(string $url): array
    {
        try {
            $response = Http::withoutVerifying()->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get($url);
            $html = $response->body();
            
            // Simple regex to find RSS/Atom links
            // <link rel="alternate" type="application/rss+xml" href="..." />
            $pattern = '/<link[^>]+rel=["\']alternate["\'][^>]+type=["\']application\/(rss\+xml|atom\+xml)["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i';
            
            if (preg_match($pattern, $html, $matches)) {
                $feedUrl = $matches[2];
                // Handle relative URLs
                if (!filter_var($feedUrl, FILTER_VALIDATE_URL)) {
                    $feedUrl = rtrim($url, '/') . '/' . ltrim($feedUrl, '/');
                }
                
                // Recursively check the found feed URL
                return $this->discover($feedUrl);
            }
            
            // No RSS found -> Candidates for AI Scraping
            // We'll scrape the title/meta from the page
            preg_match('/<title>(.*?)<\/title>/', $html, $titleMatches);
            $title = $titleMatches[1] ?? 'Unknown Site';
            
            return [
                'type' => 'scrape',
                'title' => $title,
                'site_url' => $url,
                'feed_url' => $url, // For scraper, the feed URL is the site URL
                'favicon' => $this->getFavicon($url),
                'notice' => 'No RSS feed found. We will use AI to monitor this site.'
            ];

        } catch (\Exception $e) {
             return [
                'type' => 'error',
                'message' => 'Could not access site: ' . $e->getMessage()
            ];
        }
    }

    protected function getFavicon($url)
    {
        // Simple Google Favicon service fallback
        $domain = parse_url($url, PHP_URL_HOST);
        return "https://www.google.com/s2/favicons?domain={$domain}&sz=64";
    }
}
