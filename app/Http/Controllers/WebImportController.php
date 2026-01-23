<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Feed;
use App\Models\Tag;
use App\Services\WebScraperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WebImportController extends Controller
{
    public function __construct(protected WebScraperService $scraper)
    {
    }

    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'tag_name' => 'nullable|string|max:50'
        ]);

        $url = $request->input('url');

        // Check if already exists
        $existing = Article::where('url', $url)->first();
        if ($existing) {
            // If tag is requested, add it
            if ($request->filled('tag_name')) {
                $this->attachTag($existing, $request->tag_name);
            }
            return redirect()->route('articles.show', $existing)
                ->with('status', 'Article already exists. Redirected to it.');
        }

        // Process
        $data = $this->scraper->scrape($url);

        if (isset($data['error'])) {
            return back()->withErrors(['url' => $data['error']]);
        }

        // Find or Create "Web Imports" feed
        $feed = Feed::firstOrCreate(
            ['url' => 'https://readernews.local/web-imports', 'user_id' => Auth::id()], // Virtual URL for grouping
            ['name' => 'Web Imports', 'is_rss' => false]
        );

        // Create Article
        $article = Article::create([
            'feed_id' => $feed->id,
            'title' => $data['title'] ?? 'Untitled Article',
            'url' => $url,
            'image_url' => $data['image'] ?? null,
            'summary' => Str::limit($data['excerpt'] ?? 'No summary available.', 250),
            'content' => $data['content'] ?? '',
            'published_at' => now(),
            'status' => 'unread'
        ]);

        // Attach User (so it shows up in their lists if needed, though Feed ownership might be enough depending on logic)
        // Usually, we attach the user via pivot if they are "saving" it, but here the feed is owned by them.
        // However, the `articles()` relationship on User often goes through `feed`.
        // Let's explicitly save it if we have a "saved" mechanic, or just rely on the Feed.
        // The dashboard shows articles from feeds you subscribe to. Since you own this feed, you should see it.

        // Attach Tag if requested
        if ($request->filled('tag_name')) {
            $this->attachTag($article, $request->tag_name);
        }

        return redirect()->route('articles.show', $article)
            ->with('success', 'Article imported successfully!');
    }

    protected function attachTag(Article $article, string $tagName)
    {
        $slug = Str::slug($tagName);
        $tag = Tag::firstOrCreate(
            ['name' => $tagName, 'user_id' => Auth::id()],
            ['slug' => $slug]
        );
        $article->tags()->syncWithoutDetaching([$tag->id]);
    }
}
