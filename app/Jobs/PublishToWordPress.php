<?php

namespace App\Jobs;

use App\Enums\PublicationStatus;
use App\Models\AiConfig;
use App\Models\Prompt;
use App\Models\WordPressPublication;
use App\Services\OllamaService;
use App\Services\WordPressService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishToWordPress implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(private WordPressPublication $publication)
    {
        $this->onQueue('wordpress');
    }

    public function handle(OllamaService $aiService): void
    {
        $this->publication->update(['status' => PublicationStatus::Processing]);

        $article = $this->publication->article;
        $site = $this->publication->wordPressSite;

        // Resolve AI config
        $aiConfig = AiConfig::where('is_active', true)->inRandomOrder()->first();
        if (! $aiConfig) {
            throw new \RuntimeException('No active AI config');
        }

        // Load prompt template
        $prompt = Prompt::where('slug', 'wordpress-rewrite')->first();
        $promptTemplate = $prompt
            ? $prompt->content
            : "Rewrite the following article as a professional blog post in HTML using <p>, <h2>, <h3> tags. Maintain factual accuracy. Return only HTML content.\n\n{{content}}";

        // Build final prompt
        $articleText = $article->content ?? $article->summary ?? '';
        $finalPrompt = str_replace('{{content}}', $articleText, $promptTemplate);

        // Generate rewritten content
        $rewritten = $aiService->generateText($finalPrompt, $aiConfig);

        // Resolve WordPress service (allows test mocking via app()->bind())
        $wpService = app(WordPressService::class, ['site' => $site]);

        // Best-effort image upload
        $mediaId = null;
        if ($article->image_url) {
            try {
                $mediaId = $wpService->uploadMedia($article->image_url);
            } catch (\Throwable $e) {
                Log::warning('WP image upload failed', ['error' => $e->getMessage()]);
                $mediaId = null;
            }
        }

        // Create draft post
        $post = $wpService->createDraftPost($article->title, $rewritten, $mediaId);

        $this->publication->update([
            'status' => PublicationStatus::Published,
            'wp_post_id' => $post['id'],
            'wp_post_url' => $post['link'],
            'rewritten_content' => $rewritten,
            'published_at' => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $this->publication->update([
            'status' => PublicationStatus::Failed,
            'error_message' => $e->getMessage(),
        ]);
    }
}
