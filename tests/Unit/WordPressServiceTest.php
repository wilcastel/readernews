<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WordPressSite;
use App\Services\WordPressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WordPressServiceTest extends TestCase
{
    use RefreshDatabase;

    private WordPressSite $site;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->site = WordPressSite::create([
            'user_id' => $user->id,
            'name' => 'Test Blog',
            'site_url' => 'https://myblog.example.com',
            'username' => 'editor',
            'application_password' => 'xxxx yyyy zzzz',
        ]);
    }

    // --- testConnection ---

    public function test_test_connection_returns_true_on_200(): void
    {
        Http::fake([
            'myblog.example.com/wp-json/wp/v2/users/me' => Http::response(['id' => 1], 200),
        ]);

        $service = new WordPressService($this->site);
        $this->assertTrue($service->testConnection());
    }

    public function test_test_connection_returns_false_on_401(): void
    {
        Http::fake([
            'myblog.example.com/wp-json/wp/v2/users/me' => Http::response(['message' => 'Incorrect credentials.'], 401),
        ]);

        $service = new WordPressService($this->site);
        $this->assertFalse($service->testConnection());
    }

    public function test_test_connection_sends_basic_auth_header(): void
    {
        Http::fake([
            'myblog.example.com/wp-json/wp/v2/users/me' => Http::response(['id' => 1], 200),
        ]);

        $service = new WordPressService($this->site);
        $service->testConnection();

        Http::assertSent(function (Request $request) {
            $expected = 'Basic ' . base64_encode('editor:xxxx yyyy zzzz');
            return $request->hasHeader('Authorization', $expected);
        });
    }

    // --- uploadMedia ---

    public function test_upload_media_returns_media_id_on_success(): void
    {
        Http::fake([
            'example.com/image.jpg' => Http::response('binary-image-data', 200, ['Content-Type' => 'image/jpeg']),
            'myblog.example.com/wp-json/wp/v2/media' => Http::response(['id' => 42], 201),
        ]);

        $service = new WordPressService($this->site);
        $result = $service->uploadMedia('https://example.com/image.jpg');

        $this->assertSame(42, $result);
    }

    public function test_upload_media_returns_null_on_image_download_failure(): void
    {
        Http::fake([
            'example.com/image.jpg' => Http::response('', 403),
        ]);

        $service = new WordPressService($this->site);
        $result = $service->uploadMedia('https://example.com/image.jpg');

        $this->assertNull($result);
    }

    public function test_upload_media_returns_null_on_wp_api_failure(): void
    {
        Http::fake([
            'example.com/image.jpg' => Http::response('binary-image-data', 200),
            'myblog.example.com/wp-json/wp/v2/media' => Http::response(['message' => 'Error'], 500),
        ]);

        $service = new WordPressService($this->site);
        $result = $service->uploadMedia('https://example.com/image.jpg');

        $this->assertNull($result);
    }

    // --- createDraftPost ---

    public function test_create_draft_post_returns_id_and_link_on_success(): void
    {
        Http::fake([
            'myblog.example.com/wp-json/wp/v2/posts' => Http::response([
                'id' => 99,
                'link' => 'https://myblog.example.com/?p=99',
            ], 201),
        ]);

        $service = new WordPressService($this->site);
        $result = $service->createDraftPost('My Title', '<p>Content</p>', null);

        $this->assertIsArray($result);
        $this->assertSame(99, $result['id']);
        $this->assertSame('https://myblog.example.com/?p=99', $result['link']);
    }

    public function test_create_draft_post_includes_featured_media_when_provided(): void
    {
        Http::fake([
            'myblog.example.com/wp-json/wp/v2/posts' => Http::response([
                'id' => 100,
                'link' => 'https://myblog.example.com/?p=100',
            ], 201),
        ]);

        $service = new WordPressService($this->site);
        $service->createDraftPost('My Title', '<p>Content</p>', 42);

        Http::assertSent(function (Request $request) {
            $body = $request->data();
            return isset($body['featured_media']) && $body['featured_media'] === 42;
        });
    }

    public function test_create_draft_post_throws_runtime_exception_on_error(): void
    {
        Http::fake([
            'myblog.example.com/wp-json/wp/v2/posts' => Http::response(['message' => 'Server Error'], 500),
        ]);

        $this->expectException(\RuntimeException::class);

        $service = new WordPressService($this->site);
        $service->createDraftPost('My Title', '<p>Content</p>', null);
    }

    public function test_create_draft_post_sends_status_draft(): void
    {
        Http::fake([
            'myblog.example.com/wp-json/wp/v2/posts' => Http::response([
                'id' => 1,
                'link' => 'https://myblog.example.com/?p=1',
            ], 201),
        ]);

        $service = new WordPressService($this->site);
        $service->createDraftPost('My Title', '<p>Content</p>', null);

        Http::assertSent(function (Request $request) {
            $body = $request->data();
            return isset($body['status']) && $body['status'] === 'draft';
        });
    }
}
