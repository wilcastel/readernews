<?php

use App\Models\Feed;
use App\Jobs\FetchFeedArticles;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Fix Banca y Negocios
$banca = Feed::where('name', 'Banca y Negocios')->first();
if ($banca) {
    echo "Updating Banca y Negocios to RSS...\n";
    $banca->update([
        'is_rss' => true,
        'url' => 'https://www.bancaynegocios.com/feed/' // Ensure correct RSS URL
    ]);
    dispatch(new FetchFeedArticles($banca));
}

// 2. Fix El País (Try /rss/)
$elpais = Feed::where('name', 'Diario El País')->first();
if ($elpais) {
    echo "Updating Diario El País to RSS (trying /rss/)...\n";
    $elpais->update([
        'is_rss' => true,
        'url' => 'https://www.elpais.com.co/rss/'
    ]);
    dispatch(new FetchFeedArticles($elpais));
}

// 3. Kickstart others
$others = ['Vanguardia', 'Noticias al Día y a la Hora', 'elsiglocomve', 'EL NACIONAL'];
foreach ($others as $name) {
    $feed = Feed::where('name', $name)->first();
    if ($feed) {
        echo "Dispatching fetch for $name...\n";
        dispatch(new FetchFeedArticles($feed));
    }
}

echo "Done dispatching updates.\n";
