<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WordPressSite;
use App\Services\WordPressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WordPressSiteControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    // --- index ---

    public function test_index_returns_only_authenticated_users_sites(): void
    {
        WordPressSite::create([
            'user_id' => $this->user->id,
            'name' => 'My Blog',
            'site_url' => 'https://myblog.example.com',
            'username' => 'editor',
            'application_password' => 'secret',
        ]);

        WordPressSite::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other Blog',
            'site_url' => 'https://other.example.com',
            'username' => 'admin',
            'application_password' => 'other-secret',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/wordpress-sites');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'My Blog'])
            ->assertJsonMissing(['name' => 'Other Blog']);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/wordpress-sites')->assertUnauthorized();
    }

    // --- store ---

    public function test_store_creates_site_for_authenticated_user(): void
    {
        Http::fake([
            '*/wp-json/wp/v2/users/me' => Http::response(['id' => 1], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson('/wordpress-sites', [
            'name' => 'New Blog',
            'site_url' => 'https://newblog.example.com',
            'username' => 'editor',
            'application_password' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'New Blog']);

        $this->assertDatabaseHas('wordpress_sites', [
            'user_id' => $this->user->id,
            'name' => 'New Blog',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson('/wordpress-sites', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'site_url', 'username', 'application_password']);
    }

    public function test_store_validates_site_url_format(): void
    {
        $response = $this->actingAs($this->user)->postJson('/wordpress-sites', [
            'name' => 'Blog',
            'site_url' => 'not-a-url',
            'username' => 'editor',
            'application_password' => 'secret',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['site_url']);
    }

    // --- update ---

    public function test_update_modifies_own_site(): void
    {
        $site = WordPressSite::create([
            'user_id' => $this->user->id,
            'name' => 'Old Name',
            'site_url' => 'https://blog.example.com',
            'username' => 'editor',
            'application_password' => 'secret',
        ]);

        $response = $this->actingAs($this->user)->putJson("/wordpress-sites/{$site->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('wordpress_sites', [
            'id' => $site->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_returns_403_for_other_users_site(): void
    {
        $site = WordPressSite::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other Blog',
            'site_url' => 'https://other.example.com',
            'username' => 'admin',
            'application_password' => 'secret',
        ]);

        $response = $this->actingAs($this->user)->putJson("/wordpress-sites/{$site->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    // --- destroy ---

    public function test_destroy_deletes_own_site(): void
    {
        $site = WordPressSite::create([
            'user_id' => $this->user->id,
            'name' => 'My Blog',
            'site_url' => 'https://myblog.example.com',
            'username' => 'editor',
            'application_password' => 'secret',
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/wordpress-sites/{$site->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('wordpress_sites', ['id' => $site->id]);
    }

    public function test_destroy_returns_403_for_other_users_site(): void
    {
        $site = WordPressSite::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other Blog',
            'site_url' => 'https://other.example.com',
            'username' => 'admin',
            'application_password' => 'secret',
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/wordpress-sites/{$site->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('wordpress_sites', ['id' => $site->id]);
    }

    // --- testConnection ---

    public function test_test_connection_returns_success_true_on_valid_credentials(): void
    {
        Http::fake([
            '*/wp-json/wp/v2/users/me' => Http::response(['id' => 1], 200),
        ]);

        $site = WordPressSite::create([
            'user_id' => $this->user->id,
            'name' => 'My Blog',
            'site_url' => 'https://myblog.example.com',
            'username' => 'editor',
            'application_password' => 'secret',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/wordpress-sites/{$site->id}/test-connection");

        $response->assertOk()
            ->assertJsonStructure(['success', 'message'])
            ->assertJsonFragment(['success' => true]);
    }

    public function test_test_connection_returns_success_false_on_invalid_credentials(): void
    {
        Http::fake([
            '*/wp-json/wp/v2/users/me' => Http::response(['message' => 'Invalid credentials'], 401),
        ]);

        $site = WordPressSite::create([
            'user_id' => $this->user->id,
            'name' => 'My Blog',
            'site_url' => 'https://myblog.example.com',
            'username' => 'editor',
            'application_password' => 'wrong-pass',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/wordpress-sites/{$site->id}/test-connection");

        $response->assertOk()
            ->assertJsonFragment(['success' => false]);
    }

    public function test_test_connection_returns_403_for_other_users_site(): void
    {
        $site = WordPressSite::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other Blog',
            'site_url' => 'https://other.example.com',
            'username' => 'admin',
            'application_password' => 'secret',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/wordpress-sites/{$site->id}/test-connection");

        $response->assertForbidden();
    }

    public function test_encrypted_password_not_returned_in_response(): void
    {
        Http::fake([
            '*/wp-json/wp/v2/users/me' => Http::response(['id' => 1], 200),
        ]);

        $site = WordPressSite::create([
            'user_id' => $this->user->id,
            'name' => 'My Blog',
            'site_url' => 'https://myblog.example.com',
            'username' => 'editor',
            'application_password' => 'my-secret-password',
        ]);

        $response = $this->actingAs($this->user)->getJson('/wordpress-sites');

        $response->assertOk()
            ->assertJsonMissing(['application_password']);
    }
}
