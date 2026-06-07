<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WordPressSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WordPressSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_wordpress_sites_table_exists_after_migration(): void
    {
        $this->assertTrue(\Schema::hasTable('wordpress_sites'));
    }

    public function test_wordpress_sites_table_has_expected_columns(): void
    {
        $columns = ['id', 'user_id', 'name', 'site_url', 'username', 'application_password', 'is_active', 'created_at', 'updated_at'];

        foreach ($columns as $column) {
            $this->assertTrue(
                \Schema::hasColumn('wordpress_sites', $column),
                "Column '$column' is missing from wordpress_sites table"
            );
        }
    }

    public function test_can_create_site_and_retrieve_with_decrypted_password(): void
    {
        $user = User::factory()->create();

        WordPressSite::create([
            'user_id' => $user->id,
            'name' => 'My Production Blog',
            'site_url' => 'https://blog.example.com',
            'username' => 'publisher',
            'application_password' => 'abcd 1234 efgh 5678',
            'is_active' => true,
        ]);

        $retrieved = WordPressSite::where('user_id', $user->id)->first();

        $this->assertNotNull($retrieved);
        $this->assertSame('My Production Blog', $retrieved->name);
        $this->assertSame('abcd 1234 efgh 5678', $retrieved->application_password);
    }

    public function test_application_password_stored_encrypted_in_database(): void
    {
        $user = User::factory()->create();
        $plaintext = 'super secret password';

        $site = WordPressSite::create([
            'user_id' => $user->id,
            'name' => 'Test Blog',
            'site_url' => 'https://test.example.com',
            'username' => 'admin',
            'application_password' => $plaintext,
        ]);

        $raw = DB::table('wordpress_sites')->where('id', $site->id)->value('application_password');

        $this->assertNotEquals($plaintext, $raw);
        $this->assertStringNotContainsString($plaintext, $raw);
    }

    public function test_site_is_active_by_default(): void
    {
        $user = User::factory()->create();

        $site = WordPressSite::create([
            'user_id' => $user->id,
            'name' => 'My Blog',
            'site_url' => 'https://example.com',
            'username' => 'editor',
            'application_password' => 'pass',
        ]);

        $this->assertTrue($site->fresh()->is_active);
    }

    public function test_deleting_user_cascades_to_sites(): void
    {
        $user = User::factory()->create();

        WordPressSite::create([
            'user_id' => $user->id,
            'name' => 'Blog',
            'site_url' => 'https://example.com',
            'username' => 'editor',
            'application_password' => 'pass',
        ]);

        $this->assertDatabaseCount('wordpress_sites', 1);

        $user->delete();

        $this->assertDatabaseCount('wordpress_sites', 0);
    }

    public function test_prompts_table_has_slug_column(): void
    {
        $this->assertTrue(\Schema::hasColumn('prompts', 'slug'));
    }
}
