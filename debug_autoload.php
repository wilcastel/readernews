<?php

require __DIR__ . '/vendor/autoload.php';

try {
    if (class_exists('FiveFilters\Readability\Configuration')) {
        echo "Class Configuration found!\n";
        $config = new FiveFilters\Readability\Configuration();
        echo "Instance created.\n";
    } else {
        echo "Class Configuration NOT found.\n";
        
        // Debug loaded classes matching FiveFilters
        $classes = array_filter(get_declared_classes(), function($name) {
            return stripos($name, 'FiveFilters') !== false;
        });
        print_r($classes);
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
