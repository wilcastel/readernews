<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Feed;
use App\Models\Article;
use App\Models\Folder;
use App\Services\FeedDiscoveryService;

class FeedController extends Controller
{
    public function index()
    {
        $pageTitle = "All Articles";
        $feeds = Feed::all();
        $articles = Article::with('feed')->latest('published_at')->simplePaginate(30);

        return view('dashboard', compact('feeds', 'articles', 'pageTitle'));
    }

    public function saved()
    {
        $pageTitle = "Saved for Later";
        $feeds = Feed::all(); // Layout needs feeds
        $articles = Article::with('feed')->where('is_saved', true)->latest('published_at')->simplePaginate(30);
        return view('dashboard', compact('feeds', 'articles', 'pageTitle'));
    }

    public function favorites()
    {
        $pageTitle = "Favorites";
        $feeds = Feed::all(); // Layout needs feeds
        $articles = Article::with('feed')->where('is_favorite', true)->latest('published_at')->simplePaginate(30);
        return view('dashboard', compact('feeds', 'articles', 'pageTitle'));
    }

    public function folder(Folder $folder)
    {
        $pageTitle = $folder->name;
        $feeds = Feed::all();
        $articles = Article::with('feed')
            ->whereHas('feed', fn($q) => $q->where('folder_id', $folder->id))
            ->latest('published_at')
            ->simplePaginate(30);
            
        return view('dashboard', compact('feeds', 'articles', 'pageTitle'));
    }

    public function feed(Feed $feed)
    {
        $pageTitle = $feed->name;
        $feeds = Feed::all();
        $articles = $feed->articles()->with('feed')->latest('published_at')->simplePaginate(30);
        return view('dashboard', compact('feeds', 'articles', 'pageTitle'));
    }

    public function store(\Illuminate\Http\Request $request, \App\Services\FeedDiscoveryService $discovery)
    {
        $request->validate(['url' => 'required|url']);
        
        $info = $discovery->discover($request->url);

        if ($info['type'] === 'error') {
            return back()->withErrors(['url' => $info['message']]);
        }
        
        // Prevent duplicates
        $existing = \App\Models\Feed::where('url', $info['feed_url'])->first();
        if ($existing) {
             return back()->with('message', 'You are already following this source.');
        }

        $feed = \App\Models\Feed::create([
            'url' => $info['feed_url'],
            'name' => $info['title'],
            'website_url' => $info['site_url'],
            'favicon' => $info['favicon'],
            'is_rss' => $info['type'] === 'rss',
        ]);

        // Fetch articles immediately
        \App\Jobs\FetchFeedArticles::dispatch($feed);

        return back()->with('success', 'Added ' . $feed->name . ' to your library.');
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Feed $feed)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'folder_id' => 'nullable|exists:folders,id',
        ]);

        $feed->update($validated);

        return back()->with('success', 'Feed updated.');
    }

    public function destroy(\App\Models\Feed $feed)
    {
        $feed->delete();
        return back()->with('success', 'Feed removed.');
    }
}
