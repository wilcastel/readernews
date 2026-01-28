<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Support\Str;
use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;

use Illuminate\Database\UniqueConstraintViolationException;

class ArticleController extends Controller
{
    public function show(Article $article, \Illuminate\Http\Request $request)
    {
        // Mark as read for this user
        $user = auth()->user();
        
        try {
            $pivot = $article->users()->where('user_id', $user->id)->first();
            if (!$pivot) {
                $article->users()->attach($user->id, ['is_read' => true]);
            } elseif (!$pivot->pivot->is_read) {
                $article->users()->updateExistingPivot($user->id, ['is_read' => true]);
            }
        } catch (UniqueConstraintViolationException $e) {
            // Race condition: record created by another request, ensure it is updated
            $article->users()->updateExistingPivot($user->id, ['is_read' => true]);
        }

        // Reload relation to have fresh state in view
        $article->load(['users' => fn($q) => $q->where('user_id', $user->id)]);

        // Logic for filtered reading flow
        $source = $request->query('source'); 
        $sourceId = $request->query('source_id');

        // Base Query always scoped to user
        $baseQuery = Article::whereHas('feed', fn($q) => $q->where('user_id', $user->id));

        // Apply Context Filters
        if ($source === 'feed' && $sourceId) {
            $baseQuery->where('feed_id', $sourceId);
        } elseif ($source === 'folder' && $sourceId) {
             $baseQuery->whereHas('feed', fn($q) => $q->where('folder_id', $sourceId));
        } elseif ($source === 'favorites') {
             $baseQuery->whereHas('users', fn($q) => $q->where('user_id', $user->id)->where('is_favorite', true));
        } elseif ($source === 'saved') {
             $baseQuery->whereHas('users', fn($q) => $q->where('user_id', $user->id)->where('is_saved', true));
        }

        // Previous = Newer article (above in list)
        $previous = (clone $baseQuery)->where('published_at', '>', $article->published_at)
            ->orderBy('published_at', 'asc') // Create closest newer date
            ->first();

        // Next = Older article (below in list)
        $next = (clone $baseQuery)->where('published_at', '<', $article->published_at)
            ->orderBy('published_at', 'desc') // Closest older date
            ->first();

        // Determine Back URL
        $backUrl = route('dashboard');
        if ($source === 'feed' && $sourceId) {
            $backUrl = route('feed.show', $sourceId);
        } elseif ($source === 'folder' && $sourceId) {
            $backUrl = route('folder.show', $sourceId);
        } elseif ($source === 'favorites') {
            $backUrl = route('favorites');
        } elseif ($source === 'saved') {
            $backUrl = route('saved');
        }

        return view('articles.show', compact('article', 'previous', 'next', 'backUrl', 'source', 'sourceId'));
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
        
        try {
            $pivot = $article->users()->where('user_id', $user->id)->first();
            $newState = $pivot ? !$pivot->pivot->is_saved : true;
            
            if (!$pivot) {
                 $article->users()->attach($user->id, ['is_saved' => true]);
            } else {
                 $article->users()->updateExistingPivot($user->id, ['is_saved' => $newState]);
            }
        } catch (UniqueConstraintViolationException $e) {
             // If duplicate, it means it was just created (is_saved=true likely), so ensure it's saved?
             // Or flip it? Safer to assume user wanted it saved if they clicked save.
             $article->users()->updateExistingPivot($user->id, ['is_saved' => true]);
             $newState = true; // Feedback
        }
        
        return response()->json(['success' => true, 'is_saved' => $newState]);
    }

    public function bulkUnsave(\Illuminate\Http\Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        
        auth()->user()->articles()
            ->whereIn('article_id', $request->ids)
            ->update(['article_user.is_saved' => false]);

        return response()->json(['success' => true]);
    }

    public function unsaveAll()
    {
        auth()->user()->articles()
            ->wherePivot('is_saved', true)
            ->update(['article_user.is_saved' => false]);

        return redirect()->back()->with('success', 'All articles removed from Saved list.');
    }

    public function toggleFavorite(Article $article)
    {
        $user = auth()->user();
        
        try {
            $pivot = $article->users()->where('user_id', $user->id)->first();
            $newState = $pivot ? !$pivot->pivot->is_favorite : true;
            
            if (!$pivot) {
                 $article->users()->attach($user->id, ['is_favorite' => true]);
            } else {
                 $article->users()->updateExistingPivot($user->id, ['is_favorite' => $newState]);
            }
        } catch (UniqueConstraintViolationException $e) {
             $article->users()->updateExistingPivot($user->id, ['is_favorite' => true]);
             $newState = true;
        }
        
        return response()->json(['success' => true, 'is_favorite' => $newState]);
    }

    public function markRead(Article $article)
    {
        $user = auth()->user();
        
        try {
            $pivot = $article->users()->where('user_id', $user->id)->first();
            
            if (!$pivot) {
                 $article->users()->attach($user->id, ['is_read' => true]);
            } else {
                 $article->users()->updateExistingPivot($user->id, ['is_read' => true]);
            }
        } catch (UniqueConstraintViolationException $e) {
            $article->users()->updateExistingPivot($user->id, ['is_read' => true]);
        }
        
        return response()->json(['success' => true]);
    }
    public function destroy(Article $article)
    {
        // ... (existing destroy logic) ...
        $user = auth()->user();
        
        if ($article->feed->user_id !== $user->id) {
             abort(403, 'You can only delete articles from your personal feeds.');
        }

        // Determine destination before delete
        $redirectUrl = route('dashboard');
        $previousUrl = url()->previous();
        
        // If we are deleting from a list view (dashboard, feed, folder), go back there.
        // If we are deleting from the detail view, fallback to the feed page.
        if ($previousUrl && !str_contains($previousUrl, '/articles/' . $article->id)) {
            $redirectUrl = $previousUrl;
        } else {
             $redirectUrl = route('feed.show', $article->feed_id);
        }

        $article->delete();

        return redirect($redirectUrl)->with('success', 'Article deleted.');
    }

    public function fetchModalContent(Article $article)
    {
        // Reuse the logic from show() to get context if needed, 
        // but for a modal we mainly need the article and its relationships.
        $article->load(['feed', 'users' => function($q) {
            $q->where('user_id', auth()->id());
        }]);

        // We return a blade view that only contains the content part of the article page
        // We will create 'articles.modal-content' for this.
        return view('articles.modal-content', compact('article'));
    }
}
