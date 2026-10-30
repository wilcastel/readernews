<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->timestamps();

            $table->unique(['feed_id', 'url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_sources');
    }
};
