<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OllamaService
{
    protected string $baseUrl;
    protected string $model;

    public function __get($name)
    {
        if (in_array($name, ['baseUrl', 'model'])) {
            return $this->$name;
        }
        return null;
    }

    public function __construct()
    {
        // Defaults to localhost:11434 with llama3
        $this->baseUrl = config('services.ollama.base_url', 'http://localhost:11434');
        $this->model = config('services.ollama.model', 'llama3');
    }

    public function extractArticlesFromHtml(string $html, ?string $selector = null): array
    {
        // 1. Clean HTML to reduce token usage
        $cleanHtml = $this->cleanHtmlForContext($html, $selector);

        // 2. Construct Prompt
        // 2. Construct Prompt for robust text extraction
        // JSON is failing, so we use a simple delimited format.
        $prompt = <<<EOT
Extract news articles from the list below.
Output EXACTLY one article per line using this format:
Title ||| URL ||| ImageURL

Rules:
1. No Markdown. No introductory text.
2. Only valid news articles.
3. If no image, put "null" or leave empty.
4. Use "|||" as separator.

Input:
$cleanHtml
EOT;

        try {
            \Log::info("Sending request to Ollama via Node Bridge: {$this->model}");
            
            $payload = json_encode([
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'format' => '', // Disable JSON mode
                'options' => ['temperature' => 0.1]
            ], JSON_UNESCAPED_SLASHES);
            
            // ... process execution ...
            $process = new \Symfony\Component\Process\Process([
                'node', 
                base_path('ollama-bridge.cjs')
            ]);
            $process->setInput($payload);
            $process->setTimeout(600);
            $process->run();
            
            if (!$process->isSuccessful()) {
                 \Log::error('Ollama Bridge failed: ' . $process->getErrorOutput());
                 return [];
            }
            
            $output = $process->getOutput();
            $jsonResponse = json_decode($output, true);
            
            if (!$jsonResponse || !isset($jsonResponse['response'])) {
                \Log::error('Invalid response from Ollama Bridge');
                return [];
            }

            $responseText = $jsonResponse['response'];
            
            $articles = [];
            
            // STRATEGY 1: Parse as pipe-delimited text lines
            $lines = explode("\n", $responseText);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                if (str_contains($line, '|||')) {
                    $parts = explode('|||', $line);
                    if (count($parts) >= 2) {
                        $title = trim($parts[0]);
                        $url = trim($parts[1]);
                        $image = isset($parts[2]) ? trim($parts[2]) : null;
                        if ($image === 'null') $image = null;
                        
                        if (strlen($title) > 5 && !empty($url)) {
                            $articles[] = [
                                'title' => $title,
                                'url' => $url,
                                'image' => $image,
                                'summary' => ''
                            ];
                        }
                    }
                }
            }
            
            // STRATEGY 2: Fallback to JSON extraction if text parsing failed
            if (empty($articles)) {
                $start = strpos($responseText, '[');
                $end = strrpos($responseText, ']');
                
                if ($start !== false && $end !== false && $end > $start) {
                    $jsonCandidate = substr($responseText, $start, $end - $start + 1);
                    $data = json_decode($jsonCandidate, true);
                    
                    // Normalize extraction
                    // Sometimes it returns { "articles": [ ... ] } so we might have missed the outer {
                    // But if we looked for [, we found the array.
                    
                    if (is_array($data)) {
                        foreach ($data as $item) {
                            if (isset($item['title']) && isset($item['url'])) {
                                 $articles[] = [
                                    'title' => $item['title'],
                                    'url' => $item['url'],
                                    'image' => $item['image'] ?? null,
                                    'summary' => $item['summary'] ?? ''
                                ];
                            }
                        }
                    }
                }
            }
            
            return $articles;
            
        } catch (\Exception $e) {
            \Log::error('Ollama processing error: ' . $e->getMessage());
            return [];
        }
    }

    protected function cleanHtmlForContext(string $html, ?string $selector = null): string
    {
        // Strategy Change: Instead of cleaning HTML, we extract the structural data
        // of links and texts directly. This turns 1MB of HTML into ~5KB of text context.
        
        $dom = new \DOMDocument();
        $originalLibXmlError = libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_use_internal_errors($originalLibXmlError);

        $xpath = new \DOMXPath($dom);
        
        // Remove obviously non-content distinct areas to reduce noise if no selector overrides
        if (!$selector) {
            foreach ($xpath->query('//script|//style|//nav|//footer|//header|//svg|//form') as $node) {
                $node->parentNode->removeChild($node);
            }
        }

        $output = [];
        $uniqueLinks = [];

        // Extract all Links with significant text
        // If selector is provided, scoped to that validation
        $nodes = null;
        if ($selector) {
             // If selector targets container, find 'a' inside. If targets 'a', use directly.
             // We can use a simple query like "$selector//a | $selector[self::a]"
             // But CSS to XPath conversion is complex. Let's assume user provides XPath or simple tag/id.
             // For now, let's assume specific ID or Class.
             // To support "CSS Selectors" nicely in PHP DOM without libs is hard.
             // Let's rely on specific XPath for now if it starts with /, else custom logic.
             // Wait, user will type ".class". We need simpler "str_contains" logic or similar if we don't include a library.
             // LIMITATION: Use Symfony CssSelector if installed, or assume XPath.
             // Let's try to query strict XPath for now, or just assume the user puts a class name and we search for div with that class.
             
             // Simple hack for now: If user puts ".class", we convert to `//*[@class and contains(concat(' ', normalize-space(@class), ' '), ' class ')]`
             // If #id, `//*[@id='id']`
             // If simple tag `main`, `//main`
             
             $query = $selector; // Assume valid XPath if complex, or simple map:
             if (str_starts_with($selector, '.')) {
                 $class = substr($selector, 1);
                 $query = "//*[contains(concat(' ', normalize-space(@class), ' '), ' $class ')]//a";
             } elseif (str_starts_with($selector, '#')) {
                 $id = substr($selector, 1);
                 $query = "//*[@id='$id']//a";
             } elseif (!str_starts_with($selector, '/')) {
                 // Assume tag name
                 $query = "//{$selector}//a";
             }
             
             try {
                $nodes = $xpath->query($query);
             } catch(\Exception $e) {
                // Fallback
                $nodes = $xpath->query('//a'); 
             }
        } else {
             $nodes = $xpath->query('//a');
        }
        
        if (!$nodes || $nodes->length === 0) {
             // Fallback if selector yields nothing
             $nodes = $xpath->query('//a');
        }
        
        foreach ($nodes as $node) {
            $url = $node->getAttribute('href');
            $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
            
            // Basic filtering
            if (empty($url) || strlen($text) < 10 || str_starts_with($url, '#') || str_starts_with($url, 'javascript:')) {
                continue;
            }
            
            // Check for image inside (some sites use image as title link)
            $imgSrc = '';
            $imgs = $node->getElementsByTagName('img');
            if ($imgs->length > 0) {
                $imgSrc = $imgs->item(0)->getAttribute('src');
            }

            // Deduplication
            if (isset($uniqueLinks[$url])) continue;
            $uniqueLinks[$url] = true;

            $entry = "Link: \"$text\" | URL: $url";
            if ($imgSrc) $entry .= " | Image: $imgSrc";
            
            $output[] = $entry;
        }

        // Return a raw list of candidates. 
        // The LLM is smart enough to pick the "News" ones from this list.
        return implode("\n", array_slice($output, 0, 300)); // Limit to first 300 detected links to fit context
    }
}
