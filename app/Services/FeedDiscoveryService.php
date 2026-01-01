<?php

namespace App\Services;

use SimplePie\SimplePie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FeedDiscoveryService
{
    public function discover(string $url): array
    {
        // Special Handling for YouTube
        if (str_contains($url, 'youtube.com/') || str_contains($url, 'youtu.be/')) {
            $youtubeFeed = $this->discoverYoutubeFeed($url);
            if ($youtubeFeed && $youtubeFeed !== $url) {
                // Now run standard discovery on the XML feed URL we found
                return $this->discover($youtubeFeed);
            }
        }

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
            
            // 1. Check for linked RSS/Atom in HTML
            // <link rel="alternate" type="application/rss+xml" href="..." />
            $pattern = '/<link[^>]+rel=["\']alternate["\'][^>]+type=["\']application\/(rss\+xml|atom\+xml)["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i';
            
            if (preg_match($pattern, $html, $matches)) {
                $feedUrl = $matches[2];
                // Handle relative URLs
                if (!filter_var($feedUrl, FILTER_VALIDATE_URL)) {
                    $feedUrl = rtrim($url, '/') . '/' . ltrim($feedUrl, '/');
                }
                
                return $this->discover($feedUrl);
            }

            // 2. Try Common RSS Paths (Heuristic) before falling back to scrape
            $commonPaths = ['/rss', '/feed', '/rss.xml', '/atom.xml', '/feeds/posts/default', '/rss/rss.xml'];
            $baseUrl = rtrim($url, '/');

            foreach ($commonPaths as $path) {
                $guessUrl = $baseUrl . $path;
                try {
                    $testFeed = new SimplePie();
                    $testFeed->set_feed_url($guessUrl);
                    $testFeed->enable_cache(false);
                    // Use a browser-like UA to avoid blocking
                    $testFeed->set_useragent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                    $testFeed->init();

                    if (!$testFeed->error()) {
                         return [
                            'type' => 'rss',
                            'title' => html_entity_decode($testFeed->get_title()),
                            'description' => $testFeed->get_description(),
                            'site_url' => $testFeed->get_permalink(),
                            'feed_url' => $guessUrl,
                            'favicon' => $this->getFavicon($testFeed->get_permalink() ?? $url),
                        ];
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // 3. No RSS found -> Candidates for AI Scraping
            
            // Heuristic Check: If the URL looks like an RSS feed but failed SimplePie initially (maybe due to strict checks),
            // we should try one last time or flag it differently.
            // But usually, if SimplePie failed, it's malformed.
            
            // However, verify if we missed a common path that WAS valid but we didn't check?
            // (We checked common paths above).
            
            // If the user entered a direct feed URL (e.g. ending in .xml or /feed/), we should probably treat it as RSS attempt first.
            if (Str::endsWith($url, ['/feed', '/feed/', '/rss', '/rss/', '.xml', '.rss'])) {
                 return [
                    'type' => 'rss',
                     'title' => html_entity_decode($title ?? 'Unknown Feed'),
                     'description' => '',
                     'site_url' => $url,
                     'feed_url' => $url,
                     'favicon' => $this->getFavicon($url),
                 ];
            }

            // We'll scrape the title/meta from the page
            preg_match('/<title>(.*?)<\/title>/', $html, $titleMatches);
            $title = $titleMatches[1] ?? 'Unknown Site';
            
            return [
                'type' => 'scrape',
                'title' => html_entity_decode($title),
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

    protected function discoverYoutubeFeed(string $url): ?string
    {
        // 1. If it's already a feed URL
        if (str_contains($url, 'feeds/videos.xml')) return $url;

        // 2. Channel URL: youtube.com/channel/CHANNEL_ID
        if (preg_match('/youtube\.com\/channel\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $matches[1];
        }

        // 3. User URL: youtube.com/user/USERNAME
        if (preg_match('/youtube\.com\/user\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/feeds/videos.xml?user=' . $matches[1];
        }
        
        // 4. Handle @username or Custom URL - We need to scrape the channel ID
        // youtube.com/@username or youtube.com/c/custom
        try {
            $response = Http::withoutVerifying()->get($url);
            $html = $response->body();
            
            // Check for channelId meta tag
            if (preg_match('/<meta itemprop="channelId" content="([^"]+)"/', $html, $matches) || 
                preg_match('/"channelId":"([^"]+)"/', $html, $matches)) {
                return 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $matches[1];
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}
