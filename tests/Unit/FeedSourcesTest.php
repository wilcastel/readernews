<?php

namespace Tests\Unit;

use App\Models\Feed;
use App\Models\FeedSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedSourcesTest extends TestCase
{
    use RefreshDatabase;

    private function makeFeed(): Feed
    {
        return Feed::create([
            'user_id' => User::factory()->create()->id,
            'url' => 'https://example.com/main.rss',
            'name' => 'Multi',
            'is_rss' => true,
        ]);
    }

    public function test_all_urls_returns_only_main_url_without_sources(): void
    {
        $this->assertSame(['https://example.com/main.rss'], $this->makeFeed()->allUrls());
    }

    public function test_all_urls_returns_main_first_then_sources_unique(): void
    {
        $feed = $this->makeFeed();
        FeedSource::create(['feed_id' => $feed->id, 'url' => 'https://example.com/a.rss']);
        FeedSource::create(['feed_id' => $feed->id, 'url' => 'https://example.com/main.rss']);
        FeedSource::create(['feed_id' => $feed->id, 'url' => 'https://example.com/b.rss']);

        $this->assertSame([
            'https://example.com/main.rss',
            'https://example.com/a.rss',
            'https://example.com/b.rss',
        ], $feed->fresh()->allUrls());
    }

    public function test_sources_are_deleted_with_feed(): void
    {
        $feed = $this->makeFeed();
        FeedSource::create(['feed_id' => $feed->id, 'url' => 'https://example.com/a.rss']);

        $feed->delete();

        $this->assertSame(0, FeedSource::count());
    }
}
