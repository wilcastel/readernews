<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;

class WebScraperService
{
    public function scrape(string $url)
    {
        try {
            // 1. Fetch HTML
            // We pretend to be a browser to avoid some blocks
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get($url);

            if (!$response->successful()) {
                return ['error' => 'Failed to fetch URL. Status: ' . $response->status()];
            }

            $html = $response->body();

            // 2. Parse with Readability
            $configuration = new Configuration();
            $configuration->setFixRelativeUrls(true);
            $configuration->setOriginalURL($url);

            $readability = new Readability($configuration);

            try {
                $readability->parse($html);

                return [
                    'title' => $readability->getTitle(),
                    'content' => $readability->getContent(),
                    'excerpt' => $readability->getExcerpt(),
                    'image' => $readability->getImage(),
                    'direction' => $readability->getDirection(),
                    'url' => $url
                ];
            } catch (ParseException $e) {
                return ['error' => 'Error parsing content: ' . $e->getMessage()];
            }

        } catch (\Exception $e) {
            return ['error' => 'System error: ' . $e->getMessage()];
        }
    }
}
