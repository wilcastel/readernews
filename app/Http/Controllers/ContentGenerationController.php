<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContentGenerationController extends Controller
{
    public function __construct(protected \App\Services\OllamaService $ollama) 
    {}

    public function generate(Request $request)
    {
        $request->validate([
            'article_id' => 'required', // Can be single ID (int) or array of IDs
            'prompt_id' => 'required|exists:prompts,id',
            'custom_instructions' => 'nullable|string'
        ]);

        // 1. Get Prompt
        $promptTemplate = \App\Models\Prompt::find($request->prompt_id);
        if (!$promptTemplate) {
            return response()->json(['error' => 'Prompt not found'], 404);
        }

        // 2. Get Articles Content
        $articleIds = is_array($request->article_id) ? $request->article_id : [$request->article_id];
        $articles = \App\Models\Article::whereIn('id', $articleIds)->get();
        
        $context = "";
        foreach ($articles as $article) {
             $text = strip_tags($article->content ?? $article->summary);
             $context .= "Source: {$article->title}\nContent: " . substr($text, 0, 10000) . "\n\n";
        }
        
        // 3. Prepare Final Prompt
        $finalPrompt = str_replace('{{content}}', $context, $promptTemplate->content);
        
        if ($request->filled('custom_instructions')) {
            $finalPrompt .= "\n\nAdditional Instructions:\n" . $request->custom_instructions;
        }

        // 4. Send to AI
        try {
            // Reusing generateText method we added to OllamaService
            $response = $this->ollama->generateText($finalPrompt);
            
            return response()->json([
                'success' => true,
                'content' => $response
            ]);

        } catch (\Exception $e) {
            \Log::error("Generation Failed: " . $e->getMessage());
            return response()->json(['error' => 'AI Generation failed'], 500);
        }
    }
}
