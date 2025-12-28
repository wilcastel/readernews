<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Support\Str;
use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;

class ArticleController extends Controller
{
    public function show(Article $article)
    {
        // Mark as read for this user
        $user = auth()->user();
        $pivot = $article->users()->where('user_id', $user->id)->first();
        if (!$pivot) {
            $article->users()->attach($user->id, ['is_read' => true]);
        } elseif (!$pivot->pivot->is_read) {
            $article->users()->updateExistingPivot($user->id, ['is_read' => true]);
        }

        // Reload relation to have fresh state in view
        $article->load(['users' => fn($q) => $q->where('user_id', $user->id)]);

        // Logic for reading flow (Newest first) - Scoped to User's Feeds
        // Previous = Newer article (above in list)
        $previous = Article::whereHas('feed', fn($q) => $q->where('user_id', $user->id))
            ->where('published_at', '>', $article->published_at)
            ->orderBy('published_at', 'asc') // Create closest newer date
            ->first();

        // Next = Older article (below in list)
        $next = Article::whereHas('feed', fn($q) => $q->where('user_id', $user->id))
            ->where('published_at', '<', $article->published_at)
            ->orderBy('published_at', 'desc') // Closest older date
            ->first();

        return view('articles.show', compact('article', 'previous', 'next'));
    }

    public function fetchContent(Article $article)
    {
        try {
            $configuration = new Configuration();
            $configuration->setFixRelativeURLs(true);
            $configuration->setOriginalURL($article->url);
            
            // 1. Attempt efficient HTTP Request
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get($article->url);
            
            $html = $response->body();
            
            // 2. Check if we got blocked or failed (Sucuri/Cloudflare often return 403 or 503, or 200 with a captcha page)
            // Keywords often found in WAF block pages
            $isBlocked = $response->failed() || 
                         Str::contains($html, ['Checking your browser', 'security check', 'Sucuri CloudProxy', 'Just a moment...']);

            if ($isBlocked) {
                // Fallback: Use Browsershot (Puppeteer)
                \Log::info("WAF detected for {$article->url}, switching to Browsershot.");
                
                // Configure Browsershot
                $browsershot = \Spatie\Browsershot\Browsershot::url($article->url)
                    ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox'])
                    ->windowSize(1920, 1080)
                    ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36')
                    ->dismissDialogs()
                    ->ignoreHttpsErrors()
                    ->timeout(60); 

                 // Try to explicitly set node path if we can guess it, otherwise rely on PATH
                 // $browsershot->setNodeBinary('C:\\Program Files\\nodejs\\node.exe'); // Common Windows path
                 // $browsershot->setNpmBinary('C:\\Program Files\\nodejs\\npm.cmd');

                 $html = $browsershot->bodyHtml();
            }
            
            if (empty($html)) {
                 return response()->json(['error' => 'Could not download article HTML via any method.'], 422);
            }

            if (!$html) {
                 return response()->json(['error' => 'Could not download article HTML'], 422);
            }

            $readability = new Readability($configuration);
            $readability->parse($html);

            $content = $readability->getContent();
            
            // Validation: If content is too short or empty, it might be a JS-rendered site that the simple HTTP request missed.
            // In that case, force the Browsershot fallback if we haven't already used it.
            $isContentTooShort = !($content) || strlen(strip_tags($content)) < 200;

            if (!$isBlocked && $isContentTooShort) {
                 \Log::info("Content too short for {$article->url}, retrying with Browsershot.");
                 
                 $browsershot = \Spatie\Browsershot\Browsershot::url($article->url)
                    ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox'])
                    ->windowSize(1920, 1080)
                    ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36')
                    ->dismissDialogs()
                    ->ignoreHttpsErrors()
                    ->waitUntilNetworkIdle() // Wait for JS to finish
                    ->timeout(60); 

                 $html = $browsershot->bodyHtml();
                 
                 // Re-parse with new HTML
                 $readability->parse($html);
                 $content = $readability->getContent();
            }

            $title = $readability->getTitle();

            // Update article with full content
            $article->update([
                'content' => $content,
            ]);

            return response()->json([
                'success' => true,
                'content' => $content
            ]);

        } catch (\Throwable $e) {
            \Log::error("Fetch Content Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['error' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    public function toggleSaved(Article $article)
    {
        $user = auth()->user();
        $pivot = $article->users()->where('user_id', $user->id)->first();
        
        $newState = $pivot ? !$pivot->pivot->is_saved : true;
        
        if (!$pivot) {
             $article->users()->attach($user->id, ['is_saved' => true]);
        } else {
             $article->users()->updateExistingPivot($user->id, ['is_saved' => $newState]);
        }
        
        return response()->json(['success' => true, 'is_saved' => $newState]);
    }

    public function toggleFavorite(Article $article)
    {
        $user = auth()->user();
        $pivot = $article->users()->where('user_id', $user->id)->first();
        
        $newState = $pivot ? !$pivot->pivot->is_favorite : true;
        
        if (!$pivot) {
             $article->users()->attach($user->id, ['is_favorite' => true]);
        } else {
             $article->users()->updateExistingPivot($user->id, ['is_favorite' => $newState]);
        }
        
        return response()->json(['success' => true, 'is_favorite' => $newState]);
    }

    public function markRead(Article $article)
    {
        $user = auth()->user();
        $pivot = $article->users()->where('user_id', $user->id)->first();
        
        if (!$pivot) {
             $article->users()->attach($user->id, ['is_read' => true]);
        } else {
             $article->users()->updateExistingPivot($user->id, ['is_read' => true]);
        }
        
        return response()->json(['success' => true]);
    }
}
