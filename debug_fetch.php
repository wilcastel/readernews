<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$url = 'https://primicias.com.ve';

echo "Fetching $url ...\n";

try {
    $response = Http::withoutVerifying()->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ])->get($url);

    echo "Status: " . $response->status() . "\n";
    $body = $response->body();
    echo "Body Length: " . strlen($body) . "\n";
    echo "Title Check: ";
    preg_match('/<title>(.*?)<\/title>/', $body, $matches);
    print_r($matches);
    
    echo "\nStart of Body:\n" . substr($body, 0, 500) . "\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
