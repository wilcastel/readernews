<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ai_configs')) {
            Schema::create('ai_configs', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g. "Groq Llama 3"
                $table->string('provider'); // e.g. "openai", "ollama", "openrouter"
                $table->string('base_url')->nullable();
                $table->string('api_key')->nullable();
                $table->string('model_id'); // e.g. "llama3-8b-8192"
                $table->boolean('is_active')->default(true);
                $table->string('mode')->default('all'); // 'local', 'paid', 'free' (for round robin)
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_configs');
    }
};
