<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\Tag;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function show(Tag $tag)
    {
        abort_if($tag->user_id !== auth()->id(), 403);

        $pageTitle = "# " . $tag->name;
        // Sidebar feeds
        $feeds = auth()->user()->feeds; 
        
        $articles = $tag->articles()
            ->with(['feed', 'users' => function($q) {
                $q->where('user_id', auth()->id());
            }])
            ->latest('published_at')
            ->simplePaginate(30);

        return view('dashboard', compact('feeds', 'articles', 'pageTitle'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50'
        ]);

        $tag = Tag::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'slug' => Str::slug($validated['name'])
            ],
            [
                'name' => $validated['name']
            ]
        );

        return response()->json([
            'success' => true,
            'tag' => $tag
        ]);
    }

    public function toggle(Request $request, Article $article)
    {
        $validated = $request->validate([
            'tag_name' => 'required|string'
        ]);

        $tagName = $validated['tag_name'];
        $slug = Str::slug($tagName);

        // Find or create the tag for this user
        $tag = Tag::firstOrCreate(
            ['user_id' => auth()->id(), 'slug' => $slug],
            ['name' => $tagName]
        );

        // Toggle attachment
        $attached = $article->tags()->toggle($tag->id);

        return response()->json([
            'success' => true,
            'attached' => count($attached['attached']) > 0,
            'tag' => $tag
        ]);
    }
    
    public function destroy(Tag $tag)
    {
        abort_if($tag->user_id !== auth()->id(), 403);
        $tag->delete();
        return back()->with('success', 'Tag deleted.');
    }
}
