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
        // Feeds for sidebar are handled in View Composer or direct call? 
        // Let's pass them here if we want, but sidebar logic needs update.
        // For now, let's just make sure we get ARTICLES from user's feeds.
        
        $articles = Article::whereHas('feed', function($q) {
                $q->where('user_id', auth()->id());
            })
            ->with(['feed', 'users' => function($q) {
                $q->where('user_id', auth()->id());
            }])
            ->latest('published_at')
            ->simplePaginate(30);

        // We can pass $feeds for the mobile menu if needed, though layout handles it generally
        $feeds = auth()->user()->feeds; 

        return view('dashboard', compact('feeds', 'articles', 'pageTitle'));
    }

    public function saved()
    {
        $pageTitle = "Saved for Later";
        $feeds = auth()->user()->feeds;
        
        $articles = Article::whereHas('users', function($q) {
                $q->where('user_id', auth()->id())->where('is_saved', true);
            })
            ->with(['feed', 'users' => function($q) {
                $q->where('user_id', auth()->id());
            }])
            ->latest('published_at')
            ->simplePaginate(30);
            
        return view('dashboard', compact('feeds', 'articles', 'pageTitle'));
    }

    public function favorites()
    {
        $pageTitle = "Favorites";
        $feeds = auth()->user()->feeds;
        
        $articles = Article::whereHas('users', function($q) {
                $q->where('user_id', auth()->id())->where('is_favorite', true);
            })
            ->with(['feed', 'users' => function($q) {
                $q->where('user_id', auth()->id());
            }])
            ->latest('published_at')
            ->simplePaginate(30);
            
        return view('dashboard', compact('feeds', 'articles', 'pageTitle'));
    }

    public function folder(Folder $folder)
    {
        abort_if($folder->user_id !== auth()->id(), 403);
        
        $pageTitle = $folder->name;
        $feeds = auth()->user()->feeds;
        
        $articles = Article::with(['feed', 'users' => function($q) {
                 $q->where('user_id', auth()->id());
            }])
            ->whereHas('feed', fn($q) => $q->where('folder_id', $folder->id)->where('user_id', auth()->id()))
            ->latest('published_at')
            ->simplePaginate(30);
            
        return view('dashboard', compact('feeds', 'articles', 'pageTitle'));
    }

    public function feed(Feed $feed)
    {
        abort_if($feed->user_id !== auth()->id(), 403);
        
        $pageTitle = $feed->name;
        $feeds = auth()->user()->feeds;
        
        $articles = $feed->articles()
            ->with(['feed', 'users' => function($q) {
                $q->where('user_id', auth()->id());
            }])
            ->latest('published_at')
            ->simplePaginate(30);
            
        return view('dashboard', compact('feeds', 'articles', 'pageTitle', 'feed'));
    }

    public function refresh(Feed $feed)
    {
        abort_if($feed->user_id !== auth()->id(), 403);
        
        \App\Jobs\FetchFeedArticles::dispatch($feed);
        
        return back()->with('success', 'Refreshing ' . $feed->name . '...');
    }

    public function store(\Illuminate\Http\Request $request, \App\Services\FeedDiscoveryService $discovery)
    {
        $request->validate(['url' => 'required|url']);
        
        $info = $discovery->discover($request->url);

        if ($info['type'] === 'error') {
            return back()->withErrors(['url' => $info['message']]);
        }
        
        // Prevent duplicates for this user
        $existing = \App\Models\Feed::where('url', $info['feed_url'])
            ->where('user_id', auth()->id())
            ->first();
            
        if ($existing) {
             return back()->with('message', 'You are already following this source.');
        }

        $feed = \App\Models\Feed::create([
            'url' => $info['feed_url'],
            'name' => $info['title'],
            'website_url' => $info['site_url'],
            'favicon' => $info['favicon'],
            'is_rss' => $info['type'] === 'rss',
            'user_id' => auth()->id(),
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
        return redirect()->route('dashboard')->with('success', 'Feed removed.');
    }
}
