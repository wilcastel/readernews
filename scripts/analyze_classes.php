<?php
$html = file_get_contents('https://www.wradio.com.co');
// Find article tags
preg_match_all('/<article[^>]*class="([^"]*)"/', $html, $matches);
if (!empty($matches[1])) {
    echo "ARTICLES CLASSES:\n";
    print_r(array_unique($matches[1]));
}

// Find divs that look like news
preg_match_all('/<div[^>]*class="([^"]*story[^"]*)"/', $html, $matches2);
if (!empty($matches2[1])) {
     echo "STORY CLASSES:\n";
     print_r(array_unique($matches2[1]));
}
