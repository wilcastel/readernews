<?php
require __DIR__.'/vendor/autoload.php';

$urls = [
    'El Heraldo' => 'https://www.elheraldo.co/rss',
    'La FM' => 'https://www.lafm.com.co/rss',
    'La FM HTTPS' => 'https://www.lafm.com.co/feed',
];

foreach ($urls as $name => $url) {
    echo "--- Testing $name ($url) ---\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    // Ignore SSL errors for testing
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $data = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: " . $info['http_code'] . "\n";
    if ($error) echo "Curl Error: $error\n";
    echo "Content Preview: " . htmlspecialchars(substr($data, 0, 150)) . "...\n";
    echo "\n";
}
