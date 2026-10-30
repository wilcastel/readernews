<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Some CDNs (e.g. Blu Radio) emit image URLs longer than 255 characters.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->text('image_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('image_url')->nullable()->change();
        });
    }
};
