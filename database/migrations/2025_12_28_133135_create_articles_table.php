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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('url', 768)->unique(); // Limit length for index if needed, unique is important
            $table->string('author')->nullable();
            $table->string('image_url')->nullable();
            $table->longText('content')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_saved')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
