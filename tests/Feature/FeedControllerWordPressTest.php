<?php

namespace Tests\Feature;

use App\Enums\PublicationStatus;
use App\Models\Article;
use App\Models\Feed;
use App\Models\User;
use App\Models\WordPressPublication;
use App\Models\WordPressSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedControllerWordPressTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Feed $feed;
    private Article $article;
    private WordPressSite $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->feed = Feed::create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/rss',
            'name' => 'Test Feed',
            'is_rss' => true,
        ]);

        $this->article = Article::create([
            'feed_id' => $this->feed->id,
            'title' => 'Test Article',
            'url' => 'https://example.com/article',
            'content' => 'Content',
        ]);

        $this->site = WordPressSite::create([
            'user_id' => $this->user->id,
            'name' => 'My Blog',
            'site_url' => 'https://myblog.example.com',
            'username' => 'editor',
            'application_password' => 'secret',
        ]);
    }

    public function test_dashboard_eager_loads_wordpress_publications(): void
    {
        WordPressPublication::create([
            'article_id' => $this->article->id,
            'wordpress_site_id' => $this->site->id,
            'status' => PublicationStatus::Published,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('articles');
        $response->assertViewHas('wordPressSites');

        $articles = $response->viewData('articles');
        $firstArticle = $articles->first();

        $this->assertNotNull($firstArticle, 'Dashboard should have at least one article');
        $this->assertTrue(
            $firstArticle->relationLoaded('wordpressPublications'),
            'wordpressPublications relation must be eager-loaded on dashboard articles'
        );
    }

    public function test_dashboard_only_loads_publications_for_current_user_sites(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = WordPressSite::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Blog',
            'site_url' => 'https://other.example.com',
            'username' => 'admin',
            'application_password' => 'secret',
        ]);

        // Publication for other user's site — should NOT be loaded
        WordPressPublication::create([
            'article_id' => $this->article->id,
            'wordpress_site_id' => $otherSite->id,
            'status' => PublicationStatus::Published,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $articles = $response->viewData('articles');
        $firstArticle = $articles->first();

        if ($firstArticle) {
            $this->assertCount(
                0,
                $firstArticle->wordpressPublications,
                'Publications for other users sites must not be loaded'
            );
        }
    }

    public function test_dashboard_passes_wordpress_sites_to_view(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('wordPressSites');

        $sites = $response->viewData('wordPressSites');
        $this->assertCount(1, $sites);
        $this->assertEquals($this->site->id, $sites->first()->id);
    }

    public function test_saved_view_eager_loads_wordpress_publications(): void
    {
        // Mark article as saved
        $this->user->articles()->attach($this->article->id, ['is_saved' => true]);

        WordPressPublication::create([
            'article_id' => $this->article->id,
            'wordpress_site_id' => $this->site->id,
            'status' => PublicationStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)->get(route('saved'));

        $response->assertOk();
        $response->assertViewHas('wordPressSites');

        $articles = $response->viewData('articles');
        if ($articles->count() > 0) {
            $this->assertTrue(
                $articles->first()->relationLoaded('wordpressPublications'),
                'wordpressPublications relation must be eager-loaded on saved view articles'
            );
        }
    }
}
