<?php

namespace App\Services;

use App\Models\WordPressSite;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

            $contentType = $download->header('Content-Type') ?: 'image/jpeg';
            $filename = $this->mediaFilename($imageUrl, $contentType);

            $url = rtrim($this->site->site_url, '/') . '/wp-json/wp/v2/media';

            $response = Http::withHeaders(array_merge($this->baseHeaders(), [
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Type' => $contentType,
            ]))->withBody($download->body(), $contentType)
                ->post($url);

            if ($response->failed()) {
                Log::warning('WP media upload rejected', [
                    'image_url' => $imageUrl,
                    'filename' => $filename,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);
                return null;
            }

            return $response->json('id');
        } catch (\Throwable $e) {
            Log::warning('WP media upload failed', ['image_url' => $imageUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * WordPress rejects uploads without a valid image extension. CDN URLs such as
     * Brightspot's (".../quality/75/?url=...") have none, so derive it from the Content-Type.
     */
    private function mediaFilename(string $imageUrl, string $contentType): string
    {
        $filename = basename((string) parse_url($imageUrl, PHP_URL_PATH)) ?: 'image';

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
        ];

        if (in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'], true)) {
            return $filename;
        }

        $mime = strtolower(trim(explode(';', $contentType)[0]));

        return $filename . '.' . ($extensions[$mime] ?? 'jpg');
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
