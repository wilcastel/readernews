<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prompts = [
            [
                'name' => 'Journalist News Article',
                'content' => "Actúa como un periodista, y redacta una noticia en Español que utilice la pirámide invertida con un estilo bastante fluido y sin repeticiones que además esté optimizado para SEO, a ser posible entre 300 y 750 palabras.\n\nContexto:\n{{content}}"
            ],
            [
                'name' => 'Executive Summary',
                'content' => "Actúa como un analista experto. Crea un resumen ejecutivo conciso (bullet points) de los puntos clave del siguiente contenido. Enfócate en hechos, cifras y conclusiones principales.\n\nContexto:\n{{content}}"
            ],
            [
                'name' => 'Data Extraction',
                'content' => "Extrae únicamente los datos duros (fechas, nombres, estadísticas, precios, ubicaciones) del siguiente texto. Preséntalos en formato de tabla Markdown.\n\nContexto:\n{{content}}"
            ],
            [
                'name' => 'Twitter Thread via Article',
                'content' => "Transforma el siguiente contenido en un hilo de Twitter viral. Usa un tono enganchador, emojis, y divide las ideas principales en tweets cortos (menos de 280 caracteres).\n\nContexto:\n{{content}}"
            ]
        ];

        foreach ($prompts as $p) {
            \App\Models\Prompt::firstOrCreate(
                ['name' => $p['name']],
                ['content' => $p['content'], 'is_active' => true]
            );
        }
    }
}
