<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');

        if (empty($query)) {
            return redirect()->route('dashboard');
        }

        $user = auth()->user();

        // Full Text Search with Relevance
        // We select raw relevance score to order by it
        // We also eager load feed and user pivot
        $articles = \App\Models\Article::whereHas('feed', fn($q) => $q->where('user_id', $user->id))
            ->whereRaw("MATCH(title, summary, content) AGAINST(? IN BOOLEAN MODE)", [$query])
            ->select('*') // laravel select * by default, but we might want to append score for debugging
            ->with(['feed', 'users' => fn($u) => $u->where('user_id', $user->id)])
            // ->orderByRaw("MATCH(title, summary, content) AGAINST(? IN BOOLEAN MODE) DESC", [$query]) // Order by relevance
            ->paginate(20)
            ->withQueryString();

        return view('search.index', compact('articles', 'query'));
    }
}
