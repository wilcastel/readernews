<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WordPressPromptSeeder extends Seeder
{
    public function run(): void
    {
        if (\App\Models\Prompt::where('slug', 'wordpress-rewrite')->exists()) {
            return;
        }

        \App\Models\Prompt::create([
            'name' => 'WordPress Rewrite',
            'slug' => 'wordpress-rewrite',
            'is_active' => true,
            'content' => "You are a professional content editor. Rewrite the following article as a polished blog post. Use proper HTML formatting with <p> for paragraphs, <h2> for main sections, <h3> for subsections. Maintain all factual information. Write in a clear, engaging style suitable for web publishing. Return only the HTML content without any wrapper tags.\n\n{{content}}",
        ]);
    }
}
