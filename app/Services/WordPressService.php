<?php

namespace App\Services;

use App\Models\WordPressSite;
use Illuminate\Support\Facades\Http;

class WordPressService
{
    public function __construct(private WordPressSite $site) {}

    public function testConnection(): bool
    {
        $url = rtrim($this->site->site_url, '/') . '/wp-json/wp/v2/users/me';

        $response = Http::withHeaders($this->baseHeaders())
            ->get($url);

        return $response->successful();
    }

    public function uploadMedia(string $imageUrl): ?int
    {
        try {
            $download = Http::get($imageUrl);

            if ($download->failed()) {
                return null;
            }

            $filename = basename(parse_url($imageUrl, PHP_URL_PATH)) ?: 'image.jpg';
            $contentType = $download->header('Content-Type') ?: 'image/jpeg';

            $url = rtrim($this->site->site_url, '/') . '/wp-json/wp/v2/media';

            $response = Http::withHeaders(array_merge($this->baseHeaders(), [
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Type' => $contentType,
            ]))->withBody($download->body(), $contentType)
                ->post($url);

            if ($response->failed()) {
                return null;
            }

            return $response->json('id');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function createDraftPost(string $title, string $content, ?int $featuredMediaId): array
    {
        $url = rtrim($this->site->site_url, '/') . '/wp-json/wp/v2/posts';

        $payload = [
            'title' => $title,
            'content' => $content,
            'status' => 'draft',
        ];

        if ($featuredMediaId !== null) {
            $payload['featured_media'] = $featuredMediaId;
        }

        $response = Http::withHeaders($this->baseHeaders())
            ->post($url, $payload);

        if ($response->failed()) {
            throw new \RuntimeException(
                'WordPress API error (' . $response->status() . '): ' . $response->body()
            );
        }

        return [
            'id' => $response->json('id'),
            'link' => $response->json('link'),
        ];
    }

    private function baseHeaders(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode(
                $this->site->username . ':' . $this->site->application_password
            ),
        ];
    }
}
