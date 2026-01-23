<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

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

        $transcript = $this->fetchTranscript($videoId);
        
        if (empty($transcript)) {
            return ['error' => 'No transcript available for this video'];
        }

        // Send to AI for summary
        $summary = $this->generateSummary($transcript);

        return [
            'video_id' => $videoId,
            'title' => 'Video Summary', // Ideally we fetch title via oEmbed or scrap, for now placeholder
            'transcript' => $transcript,
            'summary' => $summary,
            'url' => $url
        ];
    }

    protected function extractVideoId(string $url): ?string
    {
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
        return $matches[1] ?? null;
    }

    protected function fetchTranscript(string $videoId): string
    {
        $scriptPath = base_path('scripts/fetch_transcript.py');
        
<<<<<<< HEAD
        $process = new Process(['python', $scriptPath, $videoId]);
=======
        $venvPath = '/home/wilcastell-reader/venv/bin/python';
        $pythonCmd = file_exists($venvPath) ? $venvPath : (PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3');
        
        $process = new Process([$pythonCmd, $scriptPath, $videoId]);
>>>>>>> myNotesInvestigation
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error('YouTube Transcript Failed: ' . $process->getErrorOutput());
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

        // We use the existing OllamaService bridge
        // However, OllamaService::extractArticlesFromHtml is specific to arrays.
        // We might need a raw method in OllamaService or just reuse askOllamaBridge if it was public.
        // For now, I'll assume I need to extend OllamaService or make a method public.
        // Let's rely on a new method I'll add to OllamaService called 'generateText'.
        
        return $this->ollama->generateText($prompt);
    }
}
