<?php

require __DIR__.'/vendor/autoload.php';

$candidates = [
    'Vanguardia' => 'https://www.vanguardia.com/rss.xml',
    'El Pais' => 'https://www.elpais.com.co/rss.xml',
    'Banca' => 'https://www.bancaynegocios.com/feed/',
    'Noticias' => 'https://www.noticiasaldiayalahora.co/feed/', // Common guess
];

foreach ($candidates as $name => $url) {
    echo "Testing $name ($url)...\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true); // Head request
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 ... Chrome/91...');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    echo "  -> Status: $code, Type: $type\n";
}
