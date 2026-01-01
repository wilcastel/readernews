<?php
$html = file_get_contents('https://www.wradio.com.co');
// Extract the first 'news' section
$start = strpos($html, '<body');
$sample = substr($html, $start, 15000); // 15kb
echo $sample;
