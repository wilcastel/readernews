<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $query = \App\Models\Note::where('user_id', auth()->id())
                ->with(['tags', 'article.feed']);

            if ($request->filled('q')) {
                $search = $request->q;
                $query->where(function($q) use ($search) {
                    $q->where('quote', 'like', "%{$search}%")
                      ->orWhere('annotation', 'like', "%{$search}%")
                      ->orWhereHas('tags', function($t) use ($search) {
                          $t->where('name', 'like', "%{$search}%");
                      });
                });
            }

            return response()->json([
                'notes' => $query->latest()->get()
            ]);
        }

        return view('notes.index');
    }

    public function store(Request $request, \App\Models\Article $article)
    {
        $request->validate([
            'quote' => 'required|string',
            'annotation' => 'nullable|string',
            'tag_name' => 'nullable|string'
        ]);

        $note = \App\Models\Note::create([
            'user_id' => auth()->id(),
            'article_id' => $article->id,
            'quote' => $request->quote,
            'annotation' => $request->annotation,
            'color' => 'yellow' // Default for now
        ]);

        if ($request->filled('tag_name')) {
            $tagName = $request->tag_name;
            // Simple Slug logic
            $slug = \Illuminate\Support\Str::slug($tagName);
            
            $tag = \App\Models\Tag::firstOrCreate(
                ['name' => $tagName, 'user_id' => auth()->id()],
                ['slug' => $slug]
            );

            $note->tags()->attach($tag->id);
        }

        return response()->json([
            'success' => true,
            'note' => $note->load('tags')
        ]);
    }

    public function destroy(\App\Models\Note $note)
    {
        if ($note->user_id !== auth()->id()) {
            abort(403);
        }
        
        $note->delete();
        
        return response()->json(['success' => true]);
    }
}
