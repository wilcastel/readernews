<?php
require __DIR__.'/vendor/autoload.php';

$targets = [
    'ElPais' => 'https://www.elpais.com.co/rss/',
    'Banca' => 'https://www.bancaynegocios.com/feed/',
    'Noticias' => 'https://www.noticiasaldiayalahora.co/feed/',
    'Vanguardia' => 'https://www.vanguardia.com', // AI scrape target
];

echo "--- DEBUG CONTENT CHECKS ---\n";

foreach ($targets as $name => $url) {
    echo "Checking $name ($url)...\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    // SSL ignore
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $data = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    echo "  Status: " . $info['http_code'] . "\n";
    echo "  Size: " . strlen($data) . " bytes\n";
    echo "  Preview: " . htmlspecialchars(substr($data, 0, 100)) . "\n";
    
    if (str_contains($data, '<?xml') || str_contains($data, '<rss') || str_contains($data, '<atom')) {
        echo "  [Looks like XML/RSS]\n";
    } else {
        echo "  [Looks like HTML or other]\n";
    }
    echo "--------------------------\n";
}
