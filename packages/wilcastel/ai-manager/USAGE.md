# Wilcastel AI Manager Package Usage Guide

Este paquete permite integrar capacidades de IA (Ollama, OpenAI, OpenRouter) con soporte de RoundRobin y manejo de fallos en tus aplicaciones Laravel. Incluye una interfaz de administración completa para gestionar las claves y modelos.

## 1. Instalación en un Nuevo Proyecto

### Paso 1: Configurar el Repositorio Local
Abre el archivo `composer.json` de tu proyecto y agrega la sección `repositories`. Asegúrate de que la ruta relativa (`url`) apunte correctamente a donde tienes guardado el paquete.

```json
"repositories": [
    {
        "type": "path",
        "url": "../readernews/packages/wilcastel/ai-manager",
        "options": {
            "symlink": true
        }
    }
],
```

### Paso 2: Requerir el Paquete
```bash
composer require wilcastel/ai-manager
```

### Paso 3: Publicar Recursos
Publica la configuración y los scripts necesarios:

```bash
# Publicar configuración (config/ai-manager.php)
php artisan vendor:publish --tag=ai-manager-config

# Publicar scripts (scripts/ollama-bridge.cjs)
php artisan vendor:publish --tag=ai-manager-scripts
```

### Paso 4: Migraciones
Crea la tabla necesaria para guardar las configuraciones:

```bash
php artisan migrate
```

---

## 2. Panel de Gestión (Dashboard)

El paquete incluye una interfaz gráfica para gestionar tus IAs (RoundRobin), agregar proveedores y cambiar modelos sin tocar el código.

### Acceso
El panel está disponible automáticamente en la ruta: **`/ai-manager`**

*Nota: Esta ruta está protegida por los middleware `web` y `auth`, por lo que debes iniciar sesión en tu aplicación para acceder.*

### Agregar al Menú
Puedes agregar el enlace en tu barra de navegación (ej. `resources/views/layouts/navigation.blade.php`):

```html
<x-nav-link :href="route('ai-manager.index')" :active="request()->routeIs('ai-manager.*')">
    {{ __('AI Manager') }}
</x-nav-link>
```

### Personalización (Opcional)
La vista usa TailwindCSS y asume que tu proyecto usa `<x-app-layout>` (Breeze/Jetstream). Si necesitas personalizar el diseño:

```bash
php artisan vendor:publish --tag=ai-manager-views
```
Esto copiará los archivos a `resources/views/vendor/ai-manager`.

---

## 3. Configuración (.env)

Define los valores por defecto para cuando no haya configuraciones en base de datos o para la conexión local:

```ini
# Proveedor por defecto (ollama, openai, openrouter)
AI_DEFAULT_PROVIDER=ollama

# Local Ollama
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=qwen3:4b

# OpenRouter / OpenAI (Opcional)
OPENROUTER_API_KEY=sk-or-....
OPENAI_BASE_URL=http://localhost:1234/v1/chat/completions
```

---

## 4. Uso Programático

Inyecta `Wilcastel\AiManager\Services\AiManagerService` en cualquier lugar de tu app.

### Ejemplo: Analizar Texto (RoundRobin Automático)

```php
use Wilcastel\AiManager\Services\AiManagerService;

class DemoController extends Controller
{
    public function demo(AiManagerService $ai)
    {
        // Usará el proveedor por defecto. Si falla o es local, 
        // buscará proveedores remotos "Activos" en la base de datos.
        $respuesta = $ai->generateText("Describe este proyecto en 3 palabras.");
        
        return $respuesta;
    }
}
```

### Ejemplo: Extracción Estructurada
Útil para scrappers o lectores de noticias.

```php
public function procesarHtml(AiManagerService $ai)
{
    $html = "<html>...</html>";
    // Extrae lista de artículos filtrados
    $articulos = $ai->extractArticlesFromHtml($html);
    
    return $articulos;
}
```
