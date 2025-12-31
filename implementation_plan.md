# Plan de Implementación: ReaderNews (Laravel Edition)

## Estado Actual: ✅ Fase 1 Completada (Setup & UI Basics)

## 1. Stack Tecnológico
Siguiendo tus preferencias, descartamos React y utilizaremos un stack robusto y moderno basado en PHP:
- **Framework Principal**: Laravel 11.
- **Frontend**: Laravel Blade + Alpine.js + TailwindCSS v4.
- **Base de Datos**: SQLite (Local) / MySQL.
- **Cola de Trabajos**: Database driver.

## 2. Arquitectura de "IA Local" y Scraping
Para lograr el objetivo de seguir sitios sin feeds RSS:
- **Scraper Service**: Implementaremos un servicio que utilice `spatie/browsershot` (Puppeteer headless) o `Guzzle` para obtener el HTML.
- **AI Content Processor**: Un servicio diseñado para conectarse a una IA local (ej. Ollama ejecutando Llama 3 o Mistral).
  - *Flujo*:
    1. El usuario añade una URL.
    2. El sistema descarga el contenido HTML.
    3. Se envía el texto "sucio" a la IA Local con un prompt: "Extrae el artículo principal, título, autor y resumen de este HTML".
    4. La IA devuelve un JSON estructurado.
    5. Se guarda en la base de datos como un artículo normal.

## 3. Funcionalidades Core (Pocket/Inoreader Clone)
- **Gestión de Feeds/Fuentes**:
  - Soporte RSS tradicional (usando `feed storage` libraries).
  - ✅ Soporte "Smart Scrape" para webs sin RSS (usando Browsershot + IA).
- **Organización**:
  - Carpetas (Folders) y Etiquetas (Tags).
  - Guardar para después (estilo Pocket).
  - Favoritos / Archivar.
- **Interfaz de Usuario (UI) - "Premium Feel"**:
  - Panel izquierdo de navegación (glassmorphism).
  - Grid de artículos tipo "Magazine" o lista compacta.
  - Modo Lectura: Limpieza visual del artículo (sin ads, tipografía cuidada).
  - Modo Oscuro nativo.

## 4. Opciones de Herramientas Externas (MCPs / APIs)
- Mencionaste MCPs (Model Context Protocol). Podemos simular esto creando "Connectors" en Laravel que estandaricen cómo obtener datos de diferentes fuentes (Twitter, Reddit, Webs estáticas).

## Pasos Inmediatos (En Progreso)
1. ✅ Instalar Laravel 11 en este directorio.
2. ✅ Configurar TailwindCSS para el diseño (Rich Aesthetics & Premium Feel).
3. ✅ Crear la estructura de base de datos (Migrations) para `feeds`, `articles`, `categories`.
4. ✅ **Implementar lógica de Feeds**:
    - ✅ CRUD de Feeds.
    - ✅ Importación vía RSS/Atom.
    - ✅ Vista de Lectura con modo inmersivo ("Readability").
    - ✅ Embeds de Video y Redes Sociales.
5. 🚧 **Implementar AI Scraper (Ollama/LocalLLM)**:
    - ✅ Servicio para comunicarse con Ollama.
    - Prompt para extraer estructura de noticias de HTML crudo.
    - Integración en el Job de actualización.

## Futuras Mejoras (V2)

### Transcripción IA para YouTube
- Implementar un servicio que extraiga los subtítulos (transcripts) de los videos de YouTube.
- Utilizar herramientas como `yt-dlp` o APIs de terceros.
- Procesar el texto extraído con la IA Local para generar un "Resumen de lectura" del video, permitiendo "leer" el contenido del video sin verlo completo.

### Búsqueda Avanzada y Alertas (IA)
- **Buscador Profundo**: Indexar contenido completo de artículos para búsquedas precisas (Full-Text Search).
- **Sistema de Alertas Inteligentes**:
  - Definir "Watchlists" con términos de búsqueda específicos (en titulares, texto, fechas).
  - Acciones automáticas basadas en propiedades (ej: "Si menciona 'Bitcoin' y es de 'ayer', enviar a Favoritos").
- **Configuración de IA**:
  - Selector de proveedor (Local/Ollama vs API/OpenRouter/OpenAI/Claude).
  - Configuración de Prompts personalizados para tareas específicas.

### Sistema de Gestión de Conocimiento (PKM)
- **Anotaciones y Referencias**: Poder seleccionar texto de un artículo y guardar una nota/referencia.
- **Archivo Permanente**: Un sistema para "congelar" y guardar la versión actual de un artículo (snapshot) para evitar link rot.
- **Generación de Contenido (Redacción)**:
  - Usar artículos seleccionados como "Contexto" para que la IA redacte nuevos contenidos (ej: "Escribe un boletín semanal resumiendo estos 5 artículos").
