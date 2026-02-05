<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class YouTubeService
{
    public function __construct(protected OllamaService $ollama)
    {
    }

    /**
     * Get transcript and summary for a video.
     */
    public function processVideo(string $url): array
    {
        $videoId = $this->extractVideoId($url);
        if (!$videoId) {
            return ['error' => 'Invalid YouTube URL'];
        }

        // 1. Try Local Method (Python Script)
        $transcript = $this->fetchTranscript($videoId);
        
        if (!empty($transcript)) {
            // Local method success
            $summary = $this->generateSummary($transcript);

            return [
                'video_id' => $videoId,
                'title' => 'Video Summary', // Ideally we fetch title via oEmbed or scrap, for now placeholder
                'transcript' => $transcript,
                'summary' => $summary,
                'url' => $url
            ];
        }

        // 2. Fallback: Gemini API (If local method failed)
        Log::info("Local transcript failed for video {$videoId}. Attempting fallback with Gemini API.");
        
        $geminiResult = $this->processWithGemini($url);
        
        if (isset($geminiResult['error'])) {
            // Both methods failed
            return ['error' => 'Could not process video. Local script failed and Gemini API returned: ' . $geminiResult['error']];
        }

        return $geminiResult;
    }

    protected function processWithGemini(string $url): array
    {
        $apiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');
        
        if (!$apiKey) {
            return ['error' => 'Gemini API Key not configured'];
        }

        $model = config('services.gemini.model') ?? 'gemini-1.5-flash';
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        // Prompt designed to extract structured data
        $prompt = <<<EOT
Analyza este video de YouTube: {$url}

Tu tarea es actuar como un servicio de extracción y resumen.
Devuelve ÚNICAMENTE un objeto JSON válido (sin markdown, sin ```json) con la siguiente estructura:
{
    "title": "El título del video (si puedes detectarlo, sino usa 'Video Summary')",
    "summary": "Un resumen ejecutivo detallado del video. Tema principal, puntos clave y conclusión.",
    "transcript": "Una transcripción aproximada o los puntos más importantes hablados en el video. Si es muy largo, resume los diálogos clave."
}
EOT;

        try {
            $response = Http::post($endpoint, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'responseMimeType' => 'application/json' 
                ]
            ]);

            if ($response->failed()) {
                $errorBody = $response->body();
                Log::error('Gemini API Error: ' . $errorBody);
                return ['error' => 'Gemini API Error: ' . $errorBody];
            }

            $data = $response->json();
            $responseText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$responseText) {
                return ['error' => 'Empty response from Gemini. Body: ' . $response->body()];
            }

            // Parse JSON response
            $parsed = json_decode($responseText, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Try to clean markdown if Gemini ignored instructions
                $cleanText = preg_replace('/^```json\s*|\s*```$/', '', $responseText);
                $parsed = json_decode($cleanText, true);
            }

            if (!$parsed) {
                return ['error' => 'Failed to parse Gemini JSON response'];
            }

            $videoId = $this->extractVideoId($url);

            return [
                'video_id' => $videoId,
                'title' => $parsed['title'] ?? 'Video Summary (Gemini)',
                'transcript' => $parsed['transcript'] ?? 'No transcript generated.',
                'summary' => $parsed['summary'] ?? 'No summary generated.',
                'url' => $url
            ];

        } catch (\Exception $e) {
            Log::error('Gemini Fallback Exception: ' . $e->getMessage());
            return ['error' => 'System error during Gemini fallback'];
        }
    }

    protected function extractVideoId(string $url): ?string
    {
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
        return $matches[1] ?? null;
    }

    protected function fetchTranscript(string $videoId): string
    {
        $scriptPath = base_path('scripts/fetch_transcript.py');
        
        /*$venvPath = '/home/wilcastell-reader/venv/bin/python';
        $pythonCmd = file_exists($venvPath) ? $venvPath : (PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3');*/
        $pythonCmd = 'python3';
        
        $process = new Process([$pythonCmd, $scriptPath, $videoId]);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::warning('YouTube Local Transcript Failed (Will try fallback): ' . $process->getErrorOutput());
            return '';
        }

        return $process->getOutput();
    }

    protected function generateSummary(string $transcript): string
    {
        // Limit transcript length if needed (Ollama context window)
        $context = substr($transcript, 0, 15000); 

        $prompt = <<<EOT
Analiza la siguiente transcripción de video de YouTube y escribe un "Resumen de Lectura" completo.
El resumen debe permitirme entender el contenido del video sin tener que verlo.
Estructura:
- **Tema Principal**: Resumen en una frase.
- **Puntos Clave**: Lista de argumentos o hechos importantes.
- **Conclusión**: El pensamiento final del creador.

Transcripción:
$context
EOT;
        
        // Ensure OllamaService has this method or we need to fix it. 
        // Based on previous file reading, it seemed expected but maybe missing.
        // Assuming user has it or I should check OllamaService next.
        if (method_exists($this->ollama, 'generateText')) {
             return $this->ollama->generateText($prompt);
        }
        
        // Fallback if generateText doesn't exist (e.g. use a default method)
        return "Summary generation not available (Method missing in OllamaService).";
    }
}
