<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Feed;
use App\Services\ResearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResearchController extends Controller
{
    protected $researcher;

    public function __construct(ResearchService $researcher)
    {
        $this->researcher = $researcher;
    }

    public function index()
    {
        return view('research.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|min:3',
            'limit' => 'integer|min:1|max:10',
            'urls' => 'nullable|string' // Manual URLs separated by newlines
        ]);

        $topic = $request->input('topic');
        $limit = $request->input('limit', 3);
        
        $manualUrls = [];
        if ($request->filled('urls')) {
            $manualUrls = array_filter(
                array_map('trim', explode("\n", $request->input('urls'))),
                fn($u) => filter_var($u, FILTER_VALIDATE_URL)
            );
        }

        // Run Research
        try {
            // Note: This might take time. Ideally, this should be a Job.
            // But for now, we run synchronously as requested (interactive tool).
            // We might want to increase timeout?
            set_time_limit(300); // 5 minutes

            $result = $this->researcher->searchAndResearch($topic, $limit, $manualUrls);

            // Save as Draft Article
            $article = $this->saveAsDraft($result['title'], $result['content']);

            return redirect()->route('articles.show', $article)
                ->with('success', 'Research completed and draft created.');

        } catch (\Exception $e) {
            return back()->with('error', 'Research failed: ' . $e->getMessage());
        }
    }

    protected function saveAsDraft(string $title, string $content)
    {
        $user = auth()->user();

        // Find or Create "Researcher" Feed for this user
        $feed = Feed::firstOrCreate(
            [
                'user_id' => $user->id,
                'url' => 'local://researcher', 
            ],
            [
                'name' => 'Researcher',
                'type' => 'local', 
                // 'selector' => null // assuming nullable
            ]
        );

        // Create Article
        $article = Article::create([
            'feed_id' => $feed->id,
            'title' => $title,
            'url' => 'research://' . Str::uuid(), // Unique pseudo-url
            'content' => $content,
            'published_at' => now(),
            // 'author' => 'AI Researcher'
        ]);
        
        // Associate with User (pivot)
        $article->users()->attach($user->id, [
            'is_read' => false,
            'is_saved' => true // It's a draft, so "saved" makes sense
        ]);

        return $article;
    }
}
