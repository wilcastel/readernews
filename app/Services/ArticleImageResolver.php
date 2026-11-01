<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Some sources (e.g. El Colombiano) serve a watermarked image variant in their
 * RSS feed while the article page's og:image is clean. For configured domains,
 * prefer the og:image and fall back to the feed image on any failure.
 */
class ArticleImageResolver
{
    public function resolve(Article $article): ?string
    {
        if (!$article->url || !$this->usesOgImage($article->url)) {
            return $article->image_url;
        }

        try {
            $response = Http::timeout(15)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get($article->url);

            if ($response->successful() && ($ogImage = $this->extractOgImage($response->body()))) {
                return $ogImage;
            }
        } catch (\Throwable $e) {
            Log::warning('og:image resolution failed', ['url' => $article->url, 'error' => $e->getMessage()]);
        }

        return $article->image_url;
    }

    private function usesOgImage(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        foreach (config('services.og_image_domains', []) as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }

    private function extractOgImage(string $html): ?string
    {
        $dom = new \DOMDocument();
        if (!@$dom->loadHTML($html)) {
            return null;
        }

        $content = (new \DOMXPath($dom))->evaluate('string(//meta[@property="og:image"]/@content)');

        return filter_var($content, FILTER_VALIDATE_URL) ? $content : null;
    }
}
