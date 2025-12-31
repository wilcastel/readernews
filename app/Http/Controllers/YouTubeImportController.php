<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\YouTubeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class YouTubeImportController extends Controller
{
    public function __construct(protected YouTubeService $youtube)
    {
    }

    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        $url = $request->input('url');

        // Check if already exists
        $existing = Article::where('url', $url)->first();
        if ($existing) {
            return redirect()->route('articles.show', $existing)
                ->with('status', 'Video already imported.');
        }

        // Process
        try {
            $data = $this->youtube->processVideo($url);

            if (isset($data['error'])) {
                return back()->withErrors(['url' => $data['error']]);
            }

            // Create Article
            // We need a 'feed_id'. Since this is ad-hoc, we might need a nullable feed_id or a system feed.
            // For now, let's assume feed_id is nullable or we create/use a "Direct Imports" feed.
            // Let's make feed_id nullable in migration if not already, or find a dummy feed.
            // Checking migration later. For now, let's try to assume we can create without feed_id or use a default.
            
            // To be safe, let's find or create a "YouTube Imports" feed for the user.
            $feed = \App\Models\Feed::firstOrCreate(
                ['url' => 'https://youtube.com', 'user_id' => Auth::id()], // Scope to user
                ['name' => 'YouTube Imports', 'is_rss' => false]
            );

            $article = Article::create([
                'feed_id' => $feed->id,
                'title' => $data['title'] ?? 'YouTube Video Summary', // We might want to fetch real title later
                'url' => $data['url'],
                'image_url' => "https://img.youtube.com/vi/{$data['video_id']}/hqdefault.jpg",
                'summary' => "AI Generated Summary of YouTube Video",
                'content' => $this->formatContent($data['summary'], $data['transcript']),
                'published_at' => now(),
                'status' => 'unread' // assuming column exists, or default
            ]);

            return redirect()->route('articles.show', $article)
                ->with('success', 'Video transcript processed and summarized!');

        } catch (\Exception $e) {
            return back()->withErrors(['url' => 'System Error: ' . $e->getMessage()]);
        }
    }

    public function summarize(Article $article)
    {
        try {
            $data = $this->youtube->processVideo($article->url);

            if (isset($data['error'])) {
                return response()->json(['error' => $data['error']], 422);
            }

            $content = $this->formatContent($data['summary'], $data['transcript']);
            
            $article->update([
                'content' => $content,
                'summary' => $data['summary'] // Optional: update short summary too?
            ]);

            return response()->json([
                'success' => true,
                'content' => $content
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    protected function formatContent($summary, $transcript)
    {
        // Format as Markdown or HTML
        return "## AI Summary\n\n" . $summary . "\n\n<hr>\n\n<details><summary>Full Transcript</summary>\n\n" . nl2br(e($transcript)) . "\n\n</details>";
    }
}
