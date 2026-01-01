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
        Schema::create('feeds', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique(); // Feed URL or Site URL
            $table->string('name');
            $table->string('website_url')->nullable();
            $table->string('favicon')->nullable();
            $table->foreignId('folder_id')->nullable()->index(); // Manually handling relation due to migration order
            $table->boolean('is_rss')->default(true);
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feeds');
    }
};
