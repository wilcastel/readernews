<?php

use App\Models\Feed;
use App\Jobs\FetchFeedArticles;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Noticias al Día -> AI Scrape
$noticias = Feed::where('name', 'Noticias al Día y a la Hora')->first();
if ($noticias) {
    echo "Switching Noticias to AI Scraping...\n";
    $noticias->update(['is_rss' => false]);
    dispatch(new FetchFeedArticles($noticias));
}

// 2. Vanguardia -> AI Scrape (Ensure)
$vanguardia = Feed::where('name', 'Vanguardia')->first();
if ($vanguardia) {
    echo "Ensuring Vanguardia is AI Scraping...\n";
    $vanguardia->update(['is_rss' => false]);
    dispatch(new FetchFeedArticles($vanguardia));
}

// 3. Banca y Negocios -> RSS (Ensure)
$banca = Feed::where('name', 'Banca y Negocios')->first();
if ($banca) {
    echo "Ensuring Banca is RSS...\n";
    $banca->update([
        'is_rss' => true,
        'url' => 'https://www.bancaynegocios.com/feed/'
    ]);
    dispatch(new FetchFeedArticles($banca));
}

echo "Final fixes dispatched.\n";
