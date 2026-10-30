<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Services\ArticleImageResolver;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArticleImageResolverTest extends TestCase
{
    private const FEED_IMAGE = 'https://estaticos.elcolombiano.com/binrepository/432x300/0c0/0d0/center/11101/RAUD/photo.jpg';
    private const OG_IMAGE = 'https://estaticos.elcolombiano.com/binrepository/1280x889/0c0/1280d720/none/11101/YDXX/photo.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.og_image_domains' => ['elcolombiano.com']]);
        Http::preventStrayRequests();
    }

    private function article(string $url, ?string $imageUrl = self::FEED_IMAGE): Article
    {
        return new Article(['url' => $url, 'image_url' => $imageUrl]);
    }

    public function test_returns_og_image_for_configured_domain(): void
    {
        Http::fake([
            'www.elcolombiano.com/*' => Http::response(
                '<html><head><meta property="og:image" content="' . self::OG_IMAGE . '"></head></html>'
            ),
        ]);

        $resolved = (new ArticleImageResolver())->resolve($this->article('https://www.elcolombiano.com/inicio/nota-123'));

        $this->assertSame(self::OG_IMAGE, $resolved);
    }

    public function test_handles_content_attribute_before_property(): void
    {
        Http::fake([
            '*' => Http::response('<meta content="' . self::OG_IMAGE . '" property="og:image" />'),
        ]);

        $resolved = (new ArticleImageResolver())->resolve($this->article('https://elcolombiano.com/nota'));

        $this->assertSame(self::OG_IMAGE, $resolved);
    }

    public function test_returns_feed_image_for_unconfigured_domain_without_fetching(): void
    {
        Http::fake();

        $resolved = (new ArticleImageResolver())->resolve($this->article('https://example.com/article'));

        $this->assertSame(self::FEED_IMAGE, $resolved);
        Http::assertNothingSent();
    }

    public function test_does_not_match_lookalike_domain(): void
    {
        Http::fake();

        $resolved = (new ArticleImageResolver())->resolve($this->article('https://notelcolombiano.com/nota'));

        $this->assertSame(self::FEED_IMAGE, $resolved);
        Http::assertNothingSent();
    }

    public function test_falls_back_to_feed_image_when_page_has_no_og_image(): void
    {
        Http::fake(['*' => Http::response('<html><head></head></html>')]);

        $resolved = (new ArticleImageResolver())->resolve($this->article('https://www.elcolombiano.com/nota'));

        $this->assertSame(self::FEED_IMAGE, $resolved);
    }

    public function test_falls_back_to_feed_image_when_request_fails(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $resolved = (new ArticleImageResolver())->resolve($this->article('https://www.elcolombiano.com/nota'));

        $this->assertSame(self::FEED_IMAGE, $resolved);
    }

    public function test_falls_back_to_feed_image_on_connection_error(): void
    {
        Http::fake(fn() => throw new \Illuminate\Http\Client\ConnectionException('timeout'));

        $resolved = (new ArticleImageResolver())->resolve($this->article('https://www.elcolombiano.com/nota'));

        $this->assertSame(self::FEED_IMAGE, $resolved);
    }

    public function test_returns_null_when_article_has_no_url_and_no_image(): void
    {
        $resolved = (new ArticleImageResolver())->resolve(new Article(['url' => null, 'image_url' => null]));

        $this->assertNull($resolved);
    }
}
