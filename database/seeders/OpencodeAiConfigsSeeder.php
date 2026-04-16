<?php

namespace Database\Seeders;

use App\Models\AiConfig;
use App\Models\ProviderAccount;
use Illuminate\Database\Seeder;

class OpencodeAiConfigsSeeder extends Seeder
{
    public function run(): void
    {
        $zenAccount = ProviderAccount::firstOrCreate(
            ['provider' => 'opencode-zen', 'base_url' => 'https://opencode.ai/zen/v1'],
            [
                'label' => 'OpenCode Zen',
                'email' => null,
                'api_key' => '',
                'is_active' => true,
            ]
        );

        $goAccount = ProviderAccount::firstOrCreate(
            ['provider' => 'opencode-go', 'base_url' => 'https://opencode.ai/zen/go/v1'],
            [
                'label' => 'OpenCode Go',
                'email' => null,
                'api_key' => '',
                'is_active' => true,
            ]
        );

        $configs = [
            ['name' => 'Zen - Big Pickle (Free)', 'model_id' => 'big-pickle', 'mode' => 'free', 'description' => 'Stealth free model on OpenCode Zen. Available for a limited time during beta.'],
            ['name' => 'Zen - Nemotron 3 Super Free', 'model_id' => 'nemotron-3-super-free', 'mode' => 'free', 'description' => 'NVIDIA Nemotron 3 Super free endpoint on OpenCode Zen.'],
            ['name' => 'Zen - MiniMax M2.5 Free', 'model_id' => 'minimax-m2.5-free', 'mode' => 'free', 'description' => 'MiniMax M2.5 free on OpenCode Zen. Available for a limited time.'],
            ['name' => 'Zen - GPT 5 Nano (Free)', 'model_id' => 'gpt-5-nano', 'mode' => 'free', 'description' => 'GPT 5 Nano via OpenCode Zen. Free tier model.'],
            ['name' => 'Zen - GLM 5.1', 'model_id' => 'glm-5.1', 'mode' => 'paid', 'input_price' => 1.40, 'output_price' => 4.40, 'description' => 'GLM 5.1 via OpenCode Zen. $1.40/$4.40 per 1M tokens.'],
            ['name' => 'Zen - GLM 5', 'model_id' => 'glm-5', 'mode' => 'paid', 'input_price' => 1.00, 'output_price' => 3.20, 'description' => 'GLM 5 via OpenCode Zen. $1.00/$3.20 per 1M tokens.'],
            ['name' => 'Zen - Kimi K2.5', 'model_id' => 'kimi-k2.5', 'mode' => 'paid', 'input_price' => 0.60, 'output_price' => 3.00, 'description' => 'Kimi K2.5 via OpenCode Zen. $0.60/$3.00 per 1M tokens.'],
            ['name' => 'Zen - Qwen3.6 Plus', 'model_id' => 'qwen3.6-plus', 'mode' => 'paid', 'input_price' => 0.50, 'output_price' => 3.00, 'description' => 'Qwen3.6 Plus via OpenCode Zen. $0.50/$3.00 per 1M tokens.'],
            ['name' => 'Zen - Qwen3.5 Plus', 'model_id' => 'qwen3.5-plus', 'mode' => 'paid', 'input_price' => 0.20, 'output_price' => 1.20, 'description' => 'Qwen3.5 Plus via OpenCode Zen. $0.20/$1.20 per 1M tokens.'],
            ['name' => 'Zen - MiniMax M2.5', 'model_id' => 'minimax-m2.5', 'mode' => 'paid', 'input_price' => 0.30, 'output_price' => 1.20, 'description' => 'MiniMax M2.5 via OpenCode Zen. $0.30/$1.20 per 1M tokens.'],
            ['name' => 'Zen - GPT 5.1 Codex Mini', 'model_id' => 'gpt-5.1-codex-mini', 'mode' => 'paid', 'input_price' => 0.25, 'output_price' => 2.00, 'description' => 'GPT 5.1 Codex Mini via OpenCode Zen. $0.25/$2.00 per 1M tokens.'],
            ['name' => 'Zen - Gemini 3 Flash', 'model_id' => 'gemini-3-flash', 'mode' => 'paid', 'input_price' => 0.50, 'output_price' => 3.00, 'description' => 'Gemini 3 Flash via OpenCode Zen. $0.50/$3.00 per 1M tokens.'],
        ];

        foreach ($configs as $config) {
            AiConfig::firstOrCreate(
                ['provider' => 'opencode-zen', 'model_id' => $config['model_id']],
                array_merge($config, [
                    'provider' => 'opencode-zen',
                    'provider_account_id' => $zenAccount->id,
                    'is_active' => false,
                ])
            );
        }

        $goConfigs = [
            ['name' => 'Go - GLM 5.1', 'model_id' => 'glm-5.1', 'mode' => 'paid', 'description' => 'GLM 5.1 via OpenCode Go. ~880 requests/5hrs.'],
            ['name' => 'Go - GLM 5', 'model_id' => 'glm-5', 'mode' => 'paid', 'description' => 'GLM 5 via OpenCode Go. ~1,150 requests/5hrs.'],
            ['name' => 'Go - Kimi K2.5', 'model_id' => 'kimi-k2.5', 'mode' => 'paid', 'description' => 'Kimi K2.5 via OpenCode Go. ~1,850 requests/5hrs.'],
            ['name' => 'Go - MiMo V2 Pro', 'model_id' => 'mimo-v2-pro', 'mode' => 'paid', 'description' => 'MiMo V2 Pro via OpenCode Go. ~1,290 requests/5hrs.'],
            ['name' => 'Go - Qwen3.6 Plus', 'model_id' => 'qwen3.6-plus', 'mode' => 'paid', 'description' => 'Qwen3.6 Plus via OpenCode Go. ~3,300 requests/5hrs.'],
            ['name' => 'Go - Qwen3.5 Plus', 'model_id' => 'qwen3.5-plus', 'mode' => 'paid', 'description' => 'Qwen3.5 Plus via OpenCode Go. ~10,200 requests/5hrs.'],
        ];

        foreach ($goConfigs as $config) {
            AiConfig::firstOrCreate(
                ['provider' => 'opencode-go', 'model_id' => $config['model_id']],
                array_merge($config, [
                    'provider' => 'opencode-go',
                    'provider_account_id' => $goAccount->id,
                    'is_active' => false,
                ])
            );
        }

        $this->command->info('OpenCode Zen & Go AI configs seeded with provider accounts.');
    }
}
