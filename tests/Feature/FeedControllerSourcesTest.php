<?php

namespace Tests\Feature;

use App\Models\Feed;
use App\Models\FeedSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedControllerSourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Feed $feed;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->feed = Feed::create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/main.rss',
            'name' => 'Multi',
            'is_rss' => true,
        ]);
    }

    private function save(array $extra, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->user)->put(route('feeds.toggle-mode', $this->feed), array_merge([
            'is_rss' => 1,
            'url' => 'https://example.com/main.rss',
            'name' => 'Multi',
        ], $extra));
    }

    private function sourceUrls(): array
    {
        return FeedSource::where('feed_id', $this->feed->id)->orderBy('url')->pluck('url')->all();
    }

    public function test_saves_additional_urls(): void
    {
        $this->save(['additional_urls' => "https://example.com/a.rss\nhttps://example.com/b.rss"])
            ->assertSessionHasNoErrors();

        $this->assertSame(['https://example.com/a.rss', 'https://example.com/b.rss'], $this->sourceUrls());
    }

    public function test_sync_removes_deleted_and_keeps_existing(): void
    {
        FeedSource::create(['feed_id' => $this->feed->id, 'url' => 'https://example.com/a.rss']);
        FeedSource::create(['feed_id' => $this->feed->id, 'url' => 'https://example.com/b.rss']);

        $this->save(['additional_urls' => "https://example.com/b.rss\nhttps://example.com/c.rss"]);

        $this->assertSame(['https://example.com/b.rss', 'https://example.com/c.rss'], $this->sourceUrls());
    }

    public function test_empty_field_removes_all_sources(): void
    {
        FeedSource::create(['feed_id' => $this->feed->id, 'url' => 'https://example.com/a.rss']);

        $this->save(['additional_urls' => ''])->assertSessionHasNoErrors();

        $this->assertSame([], $this->sourceUrls());
    }

    public function test_rejects_invalid_url_and_changes_nothing(): void
    {
        FeedSource::create(['feed_id' => $this->feed->id, 'url' => 'https://example.com/a.rss']);

        $this->save(['additional_urls' => "https://example.com/b.rss\nnot a url"])
            ->assertSessionHasErrors('additional_urls');

        $this->assertSame(['https://example.com/a.rss'], $this->sourceUrls());
    }

    public function test_ignores_main_url_blanks_and_duplicates(): void
    {
        $this->save(['additional_urls' => "https://example.com/main.rss\n\n  \nhttps://example.com/a.rss\r\nhttps://example.com/a.rss\n"]);

        $this->assertSame(['https://example.com/a.rss'], $this->sourceUrls());
    }

    public function test_forbids_other_users_feed(): void
    {
        $this->save(['additional_urls' => 'https://example.com/a.rss'], User::factory()->create())
            ->assertForbidden();

        $this->assertSame([], $this->sourceUrls());
    }

    public function test_omitting_field_keeps_existing_sources(): void
    {
        FeedSource::create(['feed_id' => $this->feed->id, 'url' => 'https://example.com/a.rss']);

        $this->save([]);

        $this->assertSame(['https://example.com/a.rss'], $this->sourceUrls());
    }
}
