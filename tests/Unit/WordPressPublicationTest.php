<?php

namespace Tests\Unit;

use App\Enums\PublicationStatus;
use App\Models\Article;
use App\Models\Feed;
use App\Models\User;
use App\Models\WordPressPublication;
use App\Models\WordPressSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordPressPublicationTest extends TestCase
{
    use RefreshDatabase;

    private function makeSite(User $user): WordPressSite
    {
        return WordPressSite::create([
            'user_id' => $user->id,
            'name' => 'Test Blog',
            'site_url' => 'https://example.com',
            'username' => 'admin',
            'application_password' => 'test-pass',
        ]);
    }

    private function makeArticle(): Article
    {
        $feed = Feed::create([
            'name' => 'Test Feed',
            'url' => 'https://example.com/feed',
            'user_id' => User::factory()->create()->id,
        ]);

        return Article::create([
            'feed_id' => $feed->id,
            'title' => 'Test Article',
            'url' => 'https://example.com/article',
            'content' => 'Some content',
            'published_at' => now(),
        ]);
    }

    public function test_status_is_cast_to_publication_status_enum(): void
    {
        $user = User::factory()->create();
        $site = $this->makeSite($user);
        $article = $this->makeArticle();

        $publication = WordPressPublication::create([
            'article_id' => $article->id,
            'wordpress_site_id' => $site->id,
            'status' => PublicationStatus::Pending,
        ]);

        $fresh = WordPressPublication::find($publication->id);
        $this->assertInstanceOf(PublicationStatus::class, $fresh->status);
        $this->assertSame(PublicationStatus::Pending, $fresh->status);
    }

    public function test_published_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create();
        $site = $this->makeSite($user);
        $article = $this->makeArticle();

        $now = now()->startOfSecond();

        $publication = WordPressPublication::create([
            'article_id' => $article->id,
            'wordpress_site_id' => $site->id,
            'status' => PublicationStatus::Published,
            'published_at' => $now,
        ]);

        $fresh = WordPressPublication::find($publication->id);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->published_at);
    }

    public function test_belongs_to_article(): void
    {
        $user = User::factory()->create();
        $site = $this->makeSite($user);
        $article = $this->makeArticle();

        $publication = WordPressPublication::create([
            'article_id' => $article->id,
            'wordpress_site_id' => $site->id,
            'status' => PublicationStatus::Pending,
        ]);

        $this->assertInstanceOf(Article::class, $publication->article);
        $this->assertEquals($article->id, $publication->article->id);
    }

    public function test_belongs_to_wordpress_site(): void
    {
        $user = User::factory()->create();
        $site = $this->makeSite($user);
        $article = $this->makeArticle();

        $publication = WordPressPublication::create([
            'article_id' => $article->id,
            'wordpress_site_id' => $site->id,
            'status' => PublicationStatus::Pending,
        ]);

        $this->assertInstanceOf(WordPressSite::class, $publication->wordPressSite);
        $this->assertEquals($site->id, $publication->wordPressSite->id);
    }

    public function test_fillable_fields_are_correct(): void
    {
        $publication = new WordPressPublication();
        $fillable = $publication->getFillable();

        $expected = [
            'article_id',
            'wordpress_site_id',
            'status',
            'wp_post_id',
            'wp_post_url',
            'error_message',
            'rewritten_content',
            'published_at',
        ];

        foreach ($expected as $field) {
            $this->assertContains($field, $fillable, "Expected '$field' in fillable");
        }
    }

    public function test_default_status_is_pending(): void
    {
        $user = User::factory()->create();
        $site = $this->makeSite($user);
        $article = $this->makeArticle();

        $publication = WordPressPublication::create([
            'article_id' => $article->id,
            'wordpress_site_id' => $site->id,
        ]);

        $this->assertSame(PublicationStatus::Pending, $publication->fresh()->status);
    }
}
