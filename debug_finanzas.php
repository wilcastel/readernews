<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Mock Laravel app minimal
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$url = 'https://finanzasdigital.com/'; 

echo "Testing connection to: $url\n";

try {
    $response = Http::withoutVerifying()->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    ])->get($url);

    echo "Status: " . $response->status() . "\n";
    echo "Body length: " . strlen($response->body()) . "\n";
    
    if ($response->status() !== 200) {
        echo "Headers: \n";
        print_r($response->headers());
        echo "Body Preview: \n" . substr($response->body(), 0, 500) . "\n";
    }

} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
