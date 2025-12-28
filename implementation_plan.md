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
  - Soporte "Smart Scrape" para webs sin RSS (usando la IA).
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
    - Servicio para comunicarse con Ollama.
    - Prompt para extraer estructura de noticias de HTML crudo.
    - Integración en el Job de actualización.
