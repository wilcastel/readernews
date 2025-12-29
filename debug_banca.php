<?php

require __DIR__.'/vendor/autoload.php';

use SimplePie\SimplePie;

$url = 'https://www.bancaynegocios.com/feed/';

echo "DEBUGGING FEED: $url\n\n";

// TEST 1: SimplePie Native Fetch
echo "--- TEST 1: SimplePie Native Fetch ---\n";
$pie = new SimplePie();
$pie->set_feed_url($url);
$pie->enable_cache(false);
$pie->set_useragent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
// Force curl options if possible or just see what happens
$pie->init();
$pie->handle_content_type();

if ($pie->error()) {
    echo "SimplePie Error: " . $pie->error() . "\n";
} else {
    echo "Item Count: " . $pie->get_item_quantity() . "\n";
}
echo "\n";

// TEST 2: Validating Response Body via cURL
echo "--- TEST 2: cURL Download + SimplePie Parse ---\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$content = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Content Length: " . strlen($content) . "\n";

if ($content) {
    $pie2 = new SimplePie();
    $pie2->set_raw_data($content);
    $pie2->enable_cache(false);
    $pie2->init();
    
    if ($pie2->error()) {
        echo "SimplePie Raw Parse Error: " . $pie2->error() . "\n";
        echo "First 500 chars of content:\n" . substr($content, 0, 500) . "\n";
    } else {
        echo "Item Count (Raw): " . $pie2->get_item_quantity() . "\n";
        if ($pie2->get_item_quantity() > 0) {
            echo "First Item Title: " . $pie2->get_item(0)->get_title() . "\n";
        }
    }
} else {
    echo "cURL failed to get content.\n";
}
