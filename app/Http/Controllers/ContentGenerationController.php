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
            'article_id' => 'required_without:note_id', 
            'note_id' => 'required_without:article_id',
            'prompt_id' => 'required|exists:prompts,id',
            'custom_instructions' => 'nullable|string'
        ]);

        // 1. Get Prompt
        $promptTemplate = \App\Models\Prompt::find($request->prompt_id);
        if (!$promptTemplate) {
            return response()->json(['error' => 'Prompt not found'], 404);
        }

        $context = "";

        // 2a. Handle Articles
        if ($request->filled('article_id')) {
            $articleIds = is_array($request->article_id) ? $request->article_id : [$request->article_id];
            $articles = \App\Models\Article::whereIn('id', $articleIds)->get();
            foreach ($articles as $article) {
                $text = strip_tags($article->content ?? $article->summary);
                $context .= "Source Article: {$article->title}\nContent: " . substr($text, 0, 10000) . "\n\n";
            }
        }

        // 2b. Handle Notes
        if ($request->filled('note_id')) {
            $noteIds = is_array($request->note_id) ? $request->note_id : [$request->note_id];
            $notes = \App\Models\Note::whereIn('id', $noteIds)->with('article')->get();
            foreach ($notes as $note) {
                $context .= "Source Note (from {$note->article->title}):\nQuote: \"{$note->quote}\"\nUser Annotation: {$note->annotation}\n\n";
            }
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
