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
        \App\Models\Prompt::firstOrCreate(
            ['name' => 'Journalist News Article'],
            [
                'content' => "Actúa como un periodista, y redacta una noticia en Español que utilice la pirámide invertida con un estilo bastante fluido y sin repeticiones que además esté optimizado para SEO, a ser posible entre 300 y 750 palabras.
                
Contexto:
{{content}}",
                'is_active' => true
            ]
        );
    }
}
