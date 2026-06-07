<?php

namespace Tests\Feature;

use App\Enums\PublicationStatus;
use App\Jobs\PublishToWordPress;
use App\Models\Article;
use App\Models\Feed;
use App\Models\User;
use App\Models\WordPressPublication;
use App\Models\WordPressSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ArticlePublishTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;
    private Feed $feed;
    private Article $article;
    private WordPressSite $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->feed = Feed::create([
            'user_id' => $this->user->id,
            'url' => 'https://feeds.example.com/rss',
            'name' => 'Test Feed',
            'is_rss' => true,
        ]);

        $this->article = Article::create([
            'feed_id' => $this->feed->id,
            'title' => 'Test Article',
            'url' => 'https://example.com/article',
            'content' => 'Original article content.',
        ]);

        $this->site = WordPressSite::create([
            'user_id' => $this->user->id,
            'name' => 'My Blog',
            'site_url' => 'https://myblog.example.com',
            'username' => 'editor',
            'application_password' => 'secret',
        ]);
    }

    public function test_queue_for_publish_dispatches_job_and_returns_queued(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)->postJson(
            "/articles/{$this->article->id}/publish-to-wordpress",
            ['wordpress_site_id' => $this->site->id]
        );

        $response->assertOk()
            ->assertJsonFragment(['status' => 'queued'])
            ->assertJsonStructure(['status', 'publication_id']);

        Queue::assertPushed(PublishToWordPress::class);

        $this->assertDatabaseHas('wordpress_publications', [
            'article_id' => $this->article->id,
            'wordpress_site_id' => $this->site->id,
            'status' => PublicationStatus::Pending->value,
        ]);
    }

    public function test_queue_for_publish_returns_409_when_pending_publication_exists(): void
    {
        Queue::fake();

        WordPressPublication::create([
            'article_id' => $this->article->id,
            'wordpress_site_id' => $this->site->id,
            'status' => PublicationStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)->postJson(
            "/articles/{$this->article->id}/publish-to-wordpress",
            ['wordpress_site_id' => $this->site->id]
        );

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'Already queued or processing']);

        Queue::assertNothingPushed();
    }

    public function test_queue_for_publish_returns_409_when_processing_publication_exists(): void
    {
        Queue::fake();

        WordPressPublication::create([
            'article_id' => $this->article->id,
            'wordpress_site_id' => $this->site->id,
            'status' => PublicationStatus::Processing,
        ]);

        $response = $this->actingAs($this->user)->postJson(
            "/articles/{$this->article->id}/publish-to-wordpress",
            ['wordpress_site_id' => $this->site->id]
        );

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'Already queued or processing']);

        Queue::assertNothingPushed();
    }

    public function test_queue_for_publish_returns_422_when_site_does_not_exist(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)->postJson(
            "/articles/{$this->article->id}/publish-to-wordpress",
            ['wordpress_site_id' => 99999]
        );

        $response->assertUnprocessable();

        Queue::assertNothingPushed();
    }

    public function test_queue_for_publish_returns_403_when_site_belongs_to_other_user(): void
    {
        Queue::fake();

        $otherSite = WordPressSite::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other Blog',
            'site_url' => 'https://other.example.com',
            'username' => 'admin',
            'application_password' => 'secret',
        ]);

        $response = $this->actingAs($this->user)->postJson(
            "/articles/{$this->article->id}/publish-to-wordpress",
            ['wordpress_site_id' => $otherSite->id]
        );

        $response->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_queue_for_publish_allows_republish_after_failed(): void
    {
        Queue::fake();

        WordPressPublication::create([
            'article_id' => $this->article->id,
            'wordpress_site_id' => $this->site->id,
            'status' => PublicationStatus::Failed,
        ]);

        $response = $this->actingAs($this->user)->postJson(
            "/articles/{$this->article->id}/publish-to-wordpress",
            ['wordpress_site_id' => $this->site->id]
        );

        $response->assertOk()
            ->assertJsonFragment(['status' => 'queued']);

        Queue::assertPushed(PublishToWordPress::class);
    }
}
