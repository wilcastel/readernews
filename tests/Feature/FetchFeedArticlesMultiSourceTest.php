<?php

namespace Tests\Feature;

use App\Jobs\FetchFeedArticles;
use App\Models\Article;
use App\Models\Feed;
use App\Models\FeedSource;
use App\Models\User;
use App\Services\OllamaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FetchFeedArticlesMultiSourceTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(string $name): string
    {
        return base_path("tests/Fixtures/{$name}.xml");
    }

    private function makeFeed(): Feed
    {
        return Feed::create([
            'user_id' => User::factory()->create()->id,
            'url' => $this->fixture('section_a'),
            'name' => 'Multi',
            'is_rss' => true,
        ]);
    }

    private function run_job(Feed $feed): void
    {
        ob_start();
        try {
            (new FetchFeedArticles($feed))->handle(app(OllamaService::class));
        } finally {
            ob_end_clean();
        }
    }

    public function test_articles_from_all_sources_are_saved_under_the_feed(): void
    {
        $feed = $this->makeFeed();
        FeedSource::create(['feed_id' => $feed->id, 'url' => $this->fixture('section_b')]);

        $this->run_job($feed);

        $this->assertEqualsCanonicalizing(
            ['https://example.com/a1', 'https://example.com/b1', 'https://example.com/shared'],
            Article::where('feed_id', $feed->id)->pluck('url')->all()
        );
        $this->assertNotNull($feed->fresh()->last_scraped_at);
    }

    public function test_article_present_in_two_sources_is_saved_once_and_rerun_adds_nothing(): void
    {
        $feed = $this->makeFeed();
        FeedSource::create(['feed_id' => $feed->id, 'url' => $this->fixture('section_b')]);

        $this->run_job($feed);
        $this->run_job($feed);

        $this->assertSame(1, Article::where('url', 'https://example.com/shared')->count());
        $this->assertSame(3, Article::where('feed_id', $feed->id)->count());
    }

    public function test_failing_source_does_not_abort_the_others(): void
    {
        Http::fake(['*' => Http::response('', 500)]);
        $feed = $this->makeFeed();
        FeedSource::create(['feed_id' => $feed->id, 'url' => base_path('tests/Fixtures/missing.xml')]);
        FeedSource::create(['feed_id' => $feed->id, 'url' => $this->fixture('section_b')]);

        $this->run_job($feed);

        $this->assertSame(3, Article::where('feed_id', $feed->id)->count());
    }

    public function test_failing_item_does_not_abort_the_rest_of_its_source(): void
    {
        $feed = $this->makeFeed();
        FeedSource::create(['feed_id' => $feed->id, 'url' => $this->fixture('section_broken_item')]);

        // Simulates a DB error on one item (e.g. a value too long for its column)
        $job = new class($feed) extends FetchFeedArticles {
            protected function createArticle($item, ?string $url): void
            {
                if ($url === 'https://example.com/broken') {
                    throw new \RuntimeException('Data too long');
                }
                parent::createArticle($item, $url);
            }
        };

        ob_start();
        try {
            $job->handle(app(OllamaService::class));
        } finally {
            ob_end_clean();
        }

        $this->assertTrue(Article::where('url', 'https://example.com/c1')->exists());
        $this->assertFalse(Article::where('url', 'https://example.com/broken')->exists());
    }

    public function test_image_url_column_accepts_long_urls(): void
    {
        // Some CDNs (e.g. Blu Radio) emit image URLs longer than 255 chars
        $this->assertSame('text', Schema::getColumnType('articles', 'image_url'));
    }
}
