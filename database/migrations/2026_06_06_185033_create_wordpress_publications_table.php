<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wordpress_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wordpress_site_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('wp_post_id')->nullable();
            $table->string('wp_post_url')->nullable();
            $table->text('error_message')->nullable();
            $table->longText('rewritten_content')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['article_id', 'wordpress_site_id']);
            $table->index(['wordpress_site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wordpress_publications');
    }
};
