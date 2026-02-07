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
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->decimal('input_price', 16, 8)->nullable()->after('mode')->comment('Price per 1M tokens (or unit)');
            $table->decimal('output_price', 16, 8)->nullable()->after('input_price')->comment('Price per 1M tokens (or unit)');
            $table->mediumText('description')->nullable()->after('output_price');
            $table->integer('cantaprox')->nullable()->after('description')->comment('Approximate usage count for $10');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->dropColumn(['input_price', 'output_price', 'description', 'cantaprox']);
        });
    }
};
