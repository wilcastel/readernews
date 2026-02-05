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

        // Diverse list to catch any working model
        $models = [
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
            'gemini-2.0-flash-lite-preview-02-05',
            'gemini-2.0-flash-lite-001',
            'gemini-2.0-flash'
        ];

        $requestErrors = [];

        foreach ($models as $model) {
            // Pause between retries
            if (!empty($requestErrors)) sleep(1);

            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            
            // Re-define prompt here to ensure clarity in loop scope
             $prompt = <<<EOT
Analyza este video de YouTube: {$url}

Tu tarea es actuar como un servicio de transcripción exacto.
IMPORTANTE: Para el campo "transcript", necesito TODOS los subtítulos o el audio hablado PALABRA POR PALABRA. 
NO describas lo que se ve en pantalla. Solo transcribe lo que se DICE.

Devuelve ÚNICAMENTE un objeto JSON válido con la siguiente estructura:
{
    "title": "El título del video",
    "summary": "Un resumen ejecutivo detallado.",
    "transcript": "TRANSCRIPCIÓN LITERAL DEL AUDIO."
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
                    'tools' => [
                        [
                            'google_search' => (object)[] 
                        ]
                    ], 
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'responseMimeType' => 'application/json' 
                    ]
                ]);

                if ($response->successful()) {
                     $data = $response->json();
                     $responseText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                     
                     if ($responseText) {
                         $parsed = json_decode($responseText, true);
                         if (json_last_error() !== JSON_ERROR_NONE) {
                             $cleanText = preg_replace('/^```json\s*|\s*```$/', '', $responseText);
                             $parsed = json_decode($cleanText, true);
                         }

                         if ($parsed) {
                            $videoId = $this->extractVideoId($url);
                            return [
                                'video_id' => $videoId,
                                'title' => $parsed['title'] ?? 'Video Summary (Gemini)',
                                'transcript' => $parsed['transcript'] ?? 'No transcript generated.',
                                'summary' => $parsed['summary'] ?? 'No summary generated.',
                                'url' => $url
                            ];
                         }
                     }
                } else {
                    $errorBody = $response->body();
                    $status = $response->status();
                    $requestErrors[] = "Model {$model} ({$status}): {$errorBody}";
                    
                    Log::warning("Gemini model {$model} failed: {$errorBody}");
                    
                    if ($status === 429) {
                         return ['error' => "Rate Limit Exceeded (429) on {$model}. Please wait. Details: " . implode(" | ", $requestErrors)];
                    }
                }

            } catch (\Exception $e) {
                $requestErrors[] = "Model {$model} Exception: " . $e->getMessage();
            }
        }
        
        // If we reach here, all models failed
        return ['error' => 'All Gemini models failed. Trace: ' . implode(" || ", $requestErrors)];
    }

    protected function extractVideoId(string $url): ?string
    {
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
        return $matches[1] ?? null;
    }

    protected function fetchTranscript(string $videoId): string
    {
        $scriptPath = base_path('scripts/fetch_transcript.py');
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
        
        if (method_exists($this->ollama, 'generateText')) {
             return $this->ollama->generateText($prompt);
        }
        
        return "Summary generation not available (Method missing in OllamaService).";
    }
}
