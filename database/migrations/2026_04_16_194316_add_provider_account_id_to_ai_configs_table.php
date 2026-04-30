<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->foreignId('provider_account_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        $this->migrateExistingData();
    }

    public function down(): void
    {
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->dropForeign(['provider_account_id']);
            $table->dropColumn('provider_account_id');
        });
    }

    private function migrateExistingData(): void
    {
        $configs = DB::table('ai_configs')->whereNotNull('api_key')->get();
        $accountCache = [];

        foreach ($configs as $config) {
            $provider = $config->provider;
            $baseUrl = $config->base_url ?? $this->defaultBaseUrl($provider);
            $cacheKey = $provider.'|'.$baseUrl;

            if (! isset($accountCache[$cacheKey])) {
                $accountId = DB::table('provider_accounts')->insertGetId([
                    'provider' => $provider,
                    'label' => ucfirst($provider).' Account',
                    'email' => null,
                    'api_key' => $config->api_key,
                    'base_url' => $baseUrl,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $accountCache[$cacheKey] = $accountId;
            }

            DB::table('ai_configs')
                ->where('id', $config->id)
                ->update(['provider_account_id' => $accountCache[$cacheKey]]);
        }
    }

    private function defaultBaseUrl(string $provider): ?string
    {
        return match ($provider) {
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'opencode-zen' => 'https://opencode.ai/zen/v1',
            'opencode-go' => 'https://opencode.ai/zen/go/v1',
            default => null,
        };
    }
};
