<?php

namespace App\Console\Commands;

use App\Jobs\FetchFeedArticles;
use App\Models\Feed;
use Illuminate\Console\Command;

class FetchFeeds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feeds:fetch {feed? : The ID of a specific feed to update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch latest articles for all feeds or a specific feed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $feedId = $this->argument('feed');

        if ($feedId) {
            $feed = Feed::find($feedId);
            if (!$feed) {
                $this->error("Feed with ID {$feedId} not found.");
                return;
            }
            $this->info("Fetching articles for: {$feed->name}");
            FetchFeedArticles::dispatch($feed);
            $this->info("Job dispatched.");
            return;
        }

        $feeds = Feed::all();
        $this->info("Starting update for {$feeds->count()} feeds...");

        $bar = $this->output->createProgressBar($feeds->count());
        $bar->start();

        foreach ($feeds as $feed) {
            FetchFeedArticles::dispatch($feed);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("All jobs dispatched successfully!");
    }
}
