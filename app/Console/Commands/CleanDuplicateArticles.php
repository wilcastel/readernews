<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanDuplicateArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-duplicate-articles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning duplicate articles...');

        // Strategy: Group by feed_id and url, identify duplicates, delete older ones
        $duplicates = \Illuminate\Support\Facades\DB::select('
            SELECT feed_id, url, COUNT(*) as count 
            FROM articles 
            GROUP BY feed_id, url 
            HAVING count > 1
        ');

        $totalDeleted = 0;

        foreach ($duplicates as $group) {
            $this->info("Processing duplicates for: {$group->url}");
            
            // Get all ids for this duplicate group, ordered by id desc (keep latest)
            $ids = \App\Models\Article::where('feed_id', $group->feed_id)
                ->where('url', $group->url)
                ->orderBy('id', 'desc')
                ->pluck('id')
                ->toArray();
            
            // Remove the first one (the one we keep)
            array_shift($ids);
            
            if (!empty($ids)) {
                $count = count($ids);
                \App\Models\Article::destroy($ids);
                $this->line(" - Deleted $count older versions.");
                $totalDeleted += $count;
            }
        }
        
        $this->info("Clean up complete. Deleted $totalDeleted duplicates.");
    }
}
