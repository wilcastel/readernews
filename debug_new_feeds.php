<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Feed;

$names = ['Diario El País', 'Vanguardia', 'Noticias al Día y a la Hora', 'elsiglocomve', 'EL NACIONAL', 'Banca y Negocios'];
$feeds = Feed::whereIn('name', $names)->get();

foreach ($feeds as $feed) {
    echo "ID: {$feed->id}\n";
    echo "Name: {$feed->name}\n";
    echo "URL: {$feed->url}\n";
    echo "Type: " . ($feed->is_rss ? 'RSS' : 'Scrape/AI') . "\n";
    echo "Last Scraped: " . ($feed->last_scraped_at ?? 'Never') . "\n";
    echo "Articles Count: " . $feed->articles()->count() . "\n";
    echo "--------------------------\n";
}
