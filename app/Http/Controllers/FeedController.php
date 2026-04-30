<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Feed;
use App\Models\Article;
use App\Models\Folder;
use App\Services\FeedDiscoveryService;
use Illuminate\Support\Facades\Http;

class FeedController extends Controller
{
    public function index()
    {
        $pageTitle = "Unread Articles";
        
        $articles = Article::whereHas('feed', function($q) {
                $q->where('user_id', auth()->id());
            })
            // Exclude read articles
            ->whereDoesntHave('users', function($q) {
                $q->where('user_id', auth()->id())->where('is_read', true);
            })
            ->with(['feed', 'users' => function($q) {
                $q->where('user_id', auth()->id());
            }])
            ->latest('published_at')
            ->simplePaginate(30);

        $feeds = auth()->user()->feeds; 

        $context = ['source' => 'dashboard', 'id' => null];
        return view('dashboard', compact('feeds', 'articles', 'pageTitle', 'context'));
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
            
        $context = ['source' => 'saved', 'id' => null];
        return view('dashboard', compact('feeds', 'articles', 'pageTitle', 'context'));
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
            
        $context = ['source' => 'favorites', 'id' => null];
        return view('dashboard', compact('feeds', 'articles', 'pageTitle', 'context'));
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
            
        $context = ['source' => 'folder', 'id' => $folder->id];
        return view('dashboard', compact('feeds', 'articles', 'pageTitle', 'context', 'folder'));
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
            
        $nextFeed = $this->getNextFeed($feed);
        $context = ['source' => 'feed', 'id' => $feed->id];
        
        return view('dashboard', compact('feeds', 'articles', 'pageTitle', 'feed', 'context', 'nextFeed'));
    }

    public function refresh(Feed $feed)
    {
        abort_if($feed->user_id !== auth()->id(), 403);
        
        \App\Jobs\FetchFeedArticles::dispatch($feed);
        
        if (app()->environment('local') && config('queue.default') === 'database') {
            $artisan = base_path('artisan');
            exec("php {$artisan} queue:work --stop-when-empty > /dev/null 2>&1 &");
        }
        
        // Determine where to redirect based on the referer
        $referer = request()->headers->get('referer');
        if ($referer && str_contains($referer, '/feed/')) {
            return redirect()->route('feed.show', $feed)->with('success', 'Refreshing ' . $feed->name . '...');
        }
        
        return redirect()->route('dashboard')->with('success', 'Refreshing ' . $feed->name . '...');
    }

    public function markAllRead(Feed $feed)
    {
        abort_if($feed->user_id !== auth()->id(), 403);

        $articles = $feed->articles()->pluck('id');
        
        // Efficiently sync/update pivot table for these articles
        auth()->user()->articles()->syncWithPivotValues($articles, ['is_read' => true], false);

        $nextFeed = $this->getNextFeed($feed);

        if ($nextFeed) {
            return redirect()->route('feed.show', $nextFeed)->with('success', 'Marked ' . $feed->name . ' as read. Moving to ' . $nextFeed->name);
        }

        return redirect()->route('feed.show', $feed)->with('success', 'All articles from ' . $feed->name . ' marked as read.');
    }

    public function clear(Feed $feed)
    {
        abort_if($feed->user_id !== auth()->id(), 403);

        $articlesToKeep = Article::where('feed_id', $feed->id)
            ->whereHas('users', function($q) {
                $q->where('user_id', auth()->id())
                  ->where(function($sq) {
                      $sq->where('is_saved', true)->orWhere('is_favorite', true);
                  });
            })
            ->pluck('id');

        Article::where('feed_id', $feed->id)
            ->whereNotIn('id', $articlesToKeep)
            ->delete();

        return redirect()->route('feed.show', $feed)->with('success', 'Feed cleared (except saved/favorites).');
    }

    public function markAllReadGlobal()
    {
        // Get all articles from all feeds of this user
        $articles = Article::whereHas('feed', fn($q) => $q->where('user_id', auth()->id()))
            ->pluck('id');
        
        // Efficiently sync/update pivot table for these articles
        auth()->user()->articles()->syncWithPivotValues($articles, ['is_read' => true], false);

        return redirect()->route('dashboard')->with('success', 'All articles marked as read.');
    }

    public function markAllReadFolder(Folder $folder)
    {
        abort_if($folder->user_id !== auth()->id(), 403);

        // Get all articles from feeds in this folder
        $articles = Article::whereHas('feed', fn($q) => $q->where('folder_id', $folder->id)->where('user_id', auth()->id()))
            ->pluck('id');
        
        // Efficiently sync/update pivot table for these articles
        auth()->user()->articles()->syncWithPivotValues($articles, ['is_read' => true], false);

        // For folders, we could advance to the first feed of the NEXT folder, but maybe just staying is safer.
        // Or if the user wants auto-flow, find the next item after the folder.
        
        return redirect()->route('folder.show', $folder)->with('success', 'All articles in ' . $folder->name . ' marked as read.');
    }

    private function getNextFeed(Feed $currentFeed)
    {
        $user = auth()->user();
        
        // Get all feeds in the order they appear in the sidebar
        $allFeeds = collect();
        
        // Load folders with feeds
        $folders = $user->folders()->with('feeds')->get();
        foreach ($folders as $folder) {
            foreach ($folder->feeds as $feed) {
                $allFeeds->push($feed);
            }
        }
        
        // Load uncategorized feeds
        $uncategorized = $user->feeds()->whereNull('folder_id')->get();
        foreach ($uncategorized as $feed) {
            $allFeeds->push($feed);
        }
        
        // Find current feed index
        $currentIndex = $allFeeds->search(fn($f) => $f->id === $currentFeed->id);
        
        if ($currentIndex !== false && $currentIndex < $allFeeds->count() - 1) {
            return $allFeeds->get($currentIndex + 1);
        }
        
        return null;
    }

    public function manage()
    {
        $feeds = auth()->user()->feeds()->latest()->get();
        return view('feeds.manage', compact('feeds'));
    }

    public function toggleMode(Feed $feed, Request $request)
    {
        abort_if($feed->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'is_rss' => 'required|boolean',
            'url' => 'required|url',
            'name' => 'required|string|max:255',
            'selector' => 'nullable|string|max:255'
        ]);

        $feed->update([
            'is_rss' => $validated['is_rss'],
            'url' => $validated['url'],
            'name' => $validated['name'],
            'selector' => $validated['selector'] ?? null,
            'last_scraped_at' => null // Reset scrape time to force update
        ]);

        return back()->with('success', 'Feed settings updated.');
    }

    public function refreshAll()
    {
        $feeds = auth()->user()->feeds;
        
        foreach ($feeds as $feed) {
            \App\Jobs\FetchFeedArticles::dispatch($feed);
        }
        
        // En local, iniciar el worker en segundo plano para procesar la cola de inmediato
        if (app()->environment('local') && config('queue.default') === 'database') {
            $artisan = base_path('artisan');
            exec("php {$artisan} queue:work --stop-when-empty > /dev/null 2>&1 &");
        }
        
        return redirect()->route('dashboard')->with('success', 'Refreshing all ' . $feeds->count() . ' feeds...');
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

        if (app()->environment('local') && config('queue.default') === 'database') {
            $artisan = base_path('artisan');
            exec("php {$artisan} queue:work --stop-when-empty > /dev/null 2>&1 &");
        }

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
        return redirect()->route('dashboard');
    }

    public function diagnose(Request $request, Feed $feed)
    {
        // This function mimics the extraction process but captures logs for the UI
        $logs = [];
        $logs[] = "1. Iniciando diagnóstico para: " . $feed->url;
        $logs[] = "   Selector CSS: " . $feed->selector;

        $startTime = microtime(true);

        try {
            // 1. Fetch content
            $logs[] = "2. Descargando HTML...";
            $html = Http::timeout(30)->get($feed->url)->body();
            $logs[] = "   HTML descargado. Longitud: " . strlen($html) . " caracteres.";

            // 2. Extract content based on selector
            $logs[] = "3. Extrayendo contenido con selector '{$feed->selector}'...";
            // Use same logic as OllamaService roughly
            if ($feed->selector === 'body') {
                $content = $html;
            } else {
                // Simple DOM extraction similar to Service
                $dom = new \DOMDocument();
                @$dom->loadHTML($html, LIBXML_NOERROR);
                $xpath = new \DOMXPath($dom);
                $nodes = $xpath->query("//" . $feed->selector); // simplistic approximation for diagnosis
                $content = '';
                if ($nodes->length > 0) {
                    foreach ($nodes as $node) {
                        $content .= $dom->saveHTML($node);
                    }
                } else {
                     // Fallback to simple tag extraction if xpath fails or is just a tag name
                     // The Service has robust 'cleanHtml' logic, let's instantiate the service to use it if possible
                     // but for raw visibility let's stick to basics or use the service functions if public.
                     $content = $html; // Fallback for visualization if extractor fails here
                     $logs[] = "   ⚠️ No se encontraron nodos con ese selector estricto. Usando HTML completo para prueba.";
                }
            }
            
            // Clean HTML (simulate service cleaning)
            $cleanContent = strip_tags($content, '<a><h1><h2><h3><h4><h5><h6><p><article>');
            // Limit content size
            $cleanContent = substr($cleanContent, 0, 35000); 
            $logs[] = "   Contenido limpio (primeros 500 chars): " . substr($cleanContent, 0, 500) . "...";
            $logs[] = "   Longitud final enviada a IA: " . strlen($cleanContent);

            // 3. Send to AI
            $logs[] = "4. Enviando a Modelo IA (Ollama/OpenRouter)... esto puede tardar.";
            
            // We use the actual service to get the extraction
            $service = app(\App\Services\OllamaService::class);
            $extracted = $service->extractArticlesFromHtml($cleanContent, $feed->url);
            
            $duration = round(microtime(true) - $startTime, 2);
            $logs[] = "5. Respuesta recibida en {$duration}s.";
            
            $articleCount = count($extracted);
            if ($articleCount > 0) {
                $logs[] = "✅ ÉXITO: Se encontraron {$articleCount} artículos.";
            } else {
                $logs[] = "❌ FALLO: No se extrajeron artículos válidos.";
                $logs[] = "   Posibles causas: El modelo 'alucinó' texto que no eran enlaces, o el formato de respuesta no fue 'Titulo | URL'.";
            }

            return response()->json([
                'success' => true,
                'logs' => $logs,
                'articles' => $extracted
            ]);

        } catch (\Exception $e) {
            $logs[] = "❌ ERROR CRÍTICO: " . $e->getMessage();
            return response()->json([
                'success' => false,
                'logs' => $logs,
                'error' => $e->getMessage()
            ]);
        }
    }
    public function restartQueues()
    {
        try {
            // 1. Clear config/cache to ensure fresh settings
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            
            // 2. Restart queue workers (Supervisor will pick them up)
            \Illuminate\Support\Facades\Artisan::call('queue:restart');
            
            // 3. Retry any failed jobs immediately
            \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => 'all']);
            
            return back()->with('success', 'System queues restarted forcefully. Configuration refreshed and failed jobs queued for retry.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to restart queues: ' . $e->getMessage());
        }
    }

    public function export()
    {
        $feeds = auth()->user()->feeds()->with('folder')->get()->map(function($feed) {
            return [
                'url' => $feed->url,
                'name' => $feed->name,
                'website_url' => $feed->website_url,
                'is_rss' => $feed->is_rss,
                'selector' => $feed->selector,
                'folder_name' => $feed->folder ? $feed->folder->name : null,
                // Add other transferable settings
            ];
        });

        $filename = 'feeds-export-' . date('Y-m-d') . '.json';
        
        return response()->streamDownload(function () use ($feeds) {
            echo $feeds->toJson(JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function import(Request $request) 
    {
        $request->validate([
            'file' => 'required|file|mimes:json',
        ]);

        try {
            $json = file_get_contents($request->file('file')->getRealPath());
            $data = json_decode($json, true);

            if (!is_array($data)) {
                return back()->with('error', 'Invalid JSON format.');
            }

            $count = 0;
            $user = auth()->user();

            foreach ($data as $item) {
                if (empty($item['url'])) continue;

                // Handle Folder
                $folderId = null;
                if (!empty($item['folder_name'])) {
                    $folder = $user->folders()->firstOrCreate([
                        'name' => $item['folder_name'],
                        'user_id' => $user->id // Ensure folder is scoped to user
                    ]);
                    $folderId = $folder->id;
                }

                // Create or Find Feed
                $feed = \App\Models\Feed::where('user_id', $user->id)
                            ->where('url', $item['url'])
                            ->first();

                if (!$feed) {
                    $feed = \App\Models\Feed::create([
                        'user_id' => $user->id,
                        'url' => $item['url'],
                        'name' => $item['name'] ?? 'Imported Feed',
                        'website_url' => $item['website_url'] ?? null,
                        'is_rss' => $item['is_rss'] ?? true, 
                        'selector' => $item['selector'] ?? null,
                        'folder_id' => $folderId
                    ]);
                    $count++;
                    
                    \App\Jobs\FetchFeedArticles::dispatch($feed);
                } else {
                    // Update: assign to folder if currently unassigned and export has a folder
                    if (!$feed->folder_id && $folderId) {
                        $feed->update(['folder_id' => $folderId]);
                    }
                }
            }

            return back()->with('success', "Imported {$count} new feeds.");

        } catch (\Exception $e) {
            return back()->with('error', 'Error parsing file: ' . $e->getMessage());
        }
    }
}
