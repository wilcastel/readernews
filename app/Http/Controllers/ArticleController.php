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
        // Mark as read immediately when viewed
        if (!$article->is_read) {
            $article->update(['is_read' => true]);
        }

        // Logic for reading flow (Newest first)
        // Previous = Newer article (above in list)
        $previous = Article::where('published_at', '>', $article->published_at)
            ->orderBy('published_at', 'asc') // Create closest newer date
            ->first();

        // Next = Older article (below in list)
        $next = Article::where('published_at', '<', $article->published_at)
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
        $article->update(['is_saved' => !$article->is_saved]);
        return response()->json(['success' => true, 'is_saved' => $article->is_saved]);
    }

    public function toggleFavorite(Article $article)
    {
        $article->update(['is_favorite' => !$article->is_favorite]);
        return response()->json(['success' => true, 'is_favorite' => $article->is_favorite]);
    }

    public function markRead(Article $article)
    {
        $article->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }
}
