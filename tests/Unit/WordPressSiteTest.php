<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\User;
use App\Models\WordPressPublication;
use App\Models\WordPressSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordPressSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_password_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create();

        $site = WordPressSite::create([
            'user_id' => $user->id,
            'name' => 'My Blog',
            'site_url' => 'https://example.com',
            'username' => 'editor',
            'application_password' => 'secret pass word',
            'is_active' => true,
        ]);

        // Stored raw value in DB must NOT equal the plain text
        $rawValue = \DB::table('wordpress_sites')->where('id', $site->id)->value('application_password');
        $this->assertNotEquals('secret pass word', $rawValue);
    }

    public function test_application_password_decrypts_correctly_on_retrieval(): void
    {
        $user = User::factory()->create();

        $site = WordPressSite::create([
            'user_id' => $user->id,
            'name' => 'My Blog',
            'site_url' => 'https://example.com',
            'username' => 'editor',
            'application_password' => 'secret pass word',
            'is_active' => true,
        ]);

        $fresh = WordPressSite::find($site->id);
        $this->assertSame('secret pass word', $fresh->application_password);
    }

    public function test_application_password_is_hidden_from_serialization(): void
    {
        $user = User::factory()->create();

        $site = WordPressSite::create([
            'user_id' => $user->id,
            'name' => 'My Blog',
            'site_url' => 'https://example.com',
            'username' => 'editor',
            'application_password' => 'secret pass word',
            'is_active' => true,
        ]);

        $array = $site->toArray();
        $this->assertArrayNotHasKey('application_password', $array);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $user = User::factory()->create();

        $site = WordPressSite::create([
            'user_id' => $user->id,
            'name' => 'My Blog',
            'site_url' => 'https://example.com',
            'username' => 'editor',
            'application_password' => 'secret',
            'is_active' => true,
        ]);

        $this->assertIsBool($site->fresh()->is_active);
        $this->assertTrue($site->fresh()->is_active);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $site = WordPressSite::create([
            'user_id' => $user->id,
            'name' => 'My Blog',
            'site_url' => 'https://example.com',
            'username' => 'editor',
            'application_password' => 'secret',
        ]);

        $this->assertInstanceOf(User::class, $site->user);
        $this->assertEquals($user->id, $site->user->id);
    }

    public function test_has_many_publications(): void
    {
        $user = User::factory()->create();

        $site = WordPressSite::create([
            'user_id' => $user->id,
            'name' => 'My Blog',
            'site_url' => 'https://example.com',
            'username' => 'editor',
            'application_password' => 'secret',
        ]);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $site->publications()
        );
    }

    public function test_fillable_fields_are_correct(): void
    {
        $site = new WordPressSite();
        $fillable = $site->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('site_url', $fillable);
        $this->assertContains('username', $fillable);
        $this->assertContains('application_password', $fillable);
        $this->assertContains('is_active', $fillable);
    }
}
