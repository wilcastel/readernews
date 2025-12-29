<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Feed;
use App\Jobs\FetchFeedArticles;
use App\Services\OllamaService;

class ForceFetchFeed extends Command
{
    protected $signature = 'feed:force-fetch {id}';
    protected $description = 'Manually fetch a feed';

    public function handle()
    {
        $id = $this->argument('id');
        $feed = Feed::find($id);

        if (!$feed) {
            $this->error("Feed $id not found");
            return;
        }

        $this->info("Fetching feed: {$feed->name} ({$feed->url}) [RSS: " . ($feed->is_rss ? 'Yes' : 'No') . "]");

        try {
            $job = new FetchFeedArticles($feed);
            
            // We need to resolve OllamaService manually since we aren't dispatching via queue
            $ollama = app(OllamaService::class);
            
            $job->handle($ollama);
            
            $this->info("Success! Last scraped: " . $feed->fresh()->last_scraped_at);
            $this->info("Articles count: " . $feed->articles()->count());
            
        } catch (\Throwable $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}
