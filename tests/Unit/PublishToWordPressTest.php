<?php

namespace Tests\Unit;

use App\Enums\PublicationStatus;
use App\Jobs\PublishToWordPress;
use App\Models\AiConfig;
use App\Models\Article;
use App\Models\Feed;
use App\Models\Prompt;
use Mockery;
use App\Models\User;
use App\Models\WordPressPublication;
use App\Models\WordPressSite;
use App\Services\OllamaService;
use App\Services\WordPressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PublishToWordPressTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WordPressSite $site;
    private Article $article;
    private WordPressPublication $publication;
    private AiConfig $aiConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $feed = Feed::create([
            'user_id' => $this->user->id,
            'url' => 'https://feeds.example.com/rss',
            'name' => 'Test Feed',
            'is_rss' => true,
        ]);

        $this->article = Article::create([
            'feed_id' => $feed->id,
            'title' => 'Test Article',
            'url' => 'https://example.com/article',
            'content' => 'Original content here.',
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $this->site = WordPressSite::create([
            'user_id' => $this->user->id,
            'name' => 'My Blog',
            'site_url' => 'https://myblog.example.com',
            'username' => 'editor',
            'application_password' => 'secret-password',
        ]);

        $this->publication = WordPressPublication::create([
            'article_id' => $this->article->id,
            'wordpress_site_id' => $this->site->id,
            'status' => PublicationStatus::Pending,
        ]);

        $this->aiConfig = AiConfig::create([
            'name' => 'Test Config',
            'provider' => 'openai',
            'base_url' => 'https://api.example.com/v1/chat/completions',
            'api_key' => 'test-key',
            'model_id' => 'gpt-4',
            'is_active' => true,
        ]);
    }

    public function test_handle_transitions_status_to_processing_then_published(): void
    {
        $aiService = $this->createMock(OllamaService::class);
        $aiService->expects($this->once())
            ->method('generateText')
            ->willReturn('Rewritten HTML content');

        $wpService = $this->createMock(WordPressService::class);
        $wpService->method('uploadMedia')->willReturn(null);
        $wpService->method('createDraftPost')->willReturn([
            'id' => 55,
            'link' => 'https://myblog.example.com/?p=55',
        ]);

        $this->app->bind(WordPressService::class, fn($app, $params) => $wpService);

        $job = new PublishToWordPress($this->publication);
        $job->handle($aiService);

        $this->publication->refresh();

        $this->assertEquals(PublicationStatus::Published, $this->publication->status);
        $this->assertSame(55, $this->publication->wp_post_id);
        $this->assertSame('https://myblog.example.com/?p=55', $this->publication->wp_post_url);
        $this->assertSame('Rewritten HTML content', $this->publication->rewritten_content);
        $this->assertNotNull($this->publication->published_at);
    }

    public function test_handle_uploads_image_and_passes_media_id_to_create_post(): void
    {
        $aiService = $this->createMock(OllamaService::class);
        $aiService->method('generateText')->willReturn('<p>Rewritten</p>');

        $wpService = $this->createMock(WordPressService::class);
        $wpService->expects($this->once())
            ->method('uploadMedia')
            ->with('https://example.com/image.jpg')
            ->willReturn(42);
        $wpService->expects($this->once())
            ->method('createDraftPost')
            ->with('Test Article', '<p>Rewritten</p>', 42)
            ->willReturn(['id' => 1, 'link' => 'https://myblog.example.com/?p=1']);

        $this->app->bind(WordPressService::class, fn($app, $params) => $wpService);

        $job = new PublishToWordPress($this->publication);
        $job->handle($aiService);

        $this->publication->refresh();
        $this->assertEquals(PublicationStatus::Published, $this->publication->status);
    }

    public function test_image_upload_failure_does_not_abort_job(): void
    {
        $aiService = $this->createMock(OllamaService::class);
        $aiService->method('generateText')->willReturn('<p>Content</p>');

        $wpService = $this->createMock(WordPressService::class);
        $wpService->expects($this->once())
            ->method('uploadMedia')
            ->willThrowException(new \Exception('Connection timeout'));
        $wpService->expects($this->once())
            ->method('createDraftPost')
            ->with('Test Article', '<p>Content</p>', null)
            ->willReturn(['id' => 7, 'link' => 'https://myblog.example.com/?p=7']);

        $this->app->bind(WordPressService::class, fn($app, $params) => $wpService);

        Log::shouldReceive('warning')
            ->once()
            ->with('WP image upload failed', \Mockery::on(fn($ctx) => isset($ctx['error'])));

        $job = new PublishToWordPress($this->publication);
        $job->handle($aiService);

        $this->publication->refresh();
        $this->assertEquals(PublicationStatus::Published, $this->publication->status);
    }

    public function test_failed_sets_status_failed_and_stores_error_message(): void
    {
        $job = new PublishToWordPress($this->publication);
        $exception = new \RuntimeException('Something went wrong');

        $job->failed($exception);

        $this->publication->refresh();
        $this->assertEquals(PublicationStatus::Failed, $this->publication->status);
        $this->assertSame('Something went wrong', $this->publication->error_message);
    }

    public function test_handle_throws_when_no_active_ai_config(): void
    {
        AiConfig::query()->update(['is_active' => false]);

        $aiService = $this->createMock(OllamaService::class);

        $job = new PublishToWordPress($this->publication);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No active AI config');

        $job->handle($aiService);
    }

    public function test_handle_uses_prompt_template_when_slug_exists(): void
    {
        Prompt::create([
            'name' => 'WordPress Rewrite',
            'slug' => 'wordpress-rewrite',
            'content' => 'Custom template: {{content}}',
            'is_active' => true,
        ]);

        $aiService = $this->createMock(OllamaService::class);
        $aiService->expects($this->once())
            ->method('generateText')
            ->with(
                $this->equalTo('Custom template: Original content here.'),
                $this->isInstanceOf(AiConfig::class)
            )
            ->willReturn('<p>Rewritten</p>');

        $wpService = $this->createMock(WordPressService::class);
        $wpService->method('uploadMedia')->willReturn(null);
        $wpService->method('createDraftPost')->willReturn(['id' => 1, 'link' => 'https://myblog.example.com/?p=1']);

        $this->app->bind(WordPressService::class, fn($app, $params) => $wpService);

        $job = new PublishToWordPress($this->publication);
        $job->handle($aiService);
    }

    public function test_handle_uses_fallback_prompt_when_no_slug_prompt(): void
    {
        $aiService = $this->createMock(OllamaService::class);
        $aiService->expects($this->once())
            ->method('generateText')
            ->with(
                $this->stringContains('Original content here.'),
                $this->isInstanceOf(AiConfig::class)
            )
            ->willReturn('<p>Rewritten</p>');

        $wpService = $this->createMock(WordPressService::class);
        $wpService->method('uploadMedia')->willReturn(null);
        $wpService->method('createDraftPost')->willReturn(['id' => 1, 'link' => 'https://myblog.example.com/?p=1']);

        $this->app->bind(WordPressService::class, fn($app, $params) => $wpService);

        $job = new PublishToWordPress($this->publication);
        $job->handle($aiService);
    }
}
