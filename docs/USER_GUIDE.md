# Guía de Usuario: ReaderNews (v1.0)

Bienvenido a **ReaderNews**, tu plataforma personal para agregar, leer y sintetizar noticias utilizando Inteligencia Artificial.

## 1. Primeros Pasos

### Añadir Fuentes (Feeds)
Para empezar a recibir noticias, necesitas "seguir" sitios web o canales.
1. Haz clic en el botón **"Add Source"** (o el icono `+`) en la barra superior.
2. Introduce una URL:
   - **Sitio Web**: Ej. `https://techcrunch.com`. El sistema detectará automáticamente el feed RSS. Si no tiene, intentará usar sus agentes inteligentes para extraer noticias.
   - **YouTube**: Ej. `https://youtube.com/@Veritasium`. El sistema importará los últimos videos y podrá generar resúmenes de texto.

### Carpetas y Organización
Mantén tu lectura ordenada:
- **Carpetas**: Crea carpetas (ej. "Tecnología", "Finanzas") desde el menú lateral. Arrastra o edita tus feeds para moverlos dentro.
- **Etiquetas (Tags)**: Dentro de cualquier artículo, puedes añadir etiquetas personalizadas (ej. `#ProyectoAlpha`) para referencia futura.

## 2. Experiencia de Lectura

### Modos de Visualización
- **Grid "Magazine"**: Vista por defecto con tarjetas visuales.
- **Modo Oscuro**: Actívalo con el icono de la luna 🌙 en la esquina superior derecha. Ideal para lectura nocturna.

### Navegación Inteligente
Al entrar en una carpeta o feed específico, la aplicación recuerda tu contexto.
- Usa los botones de **Anterior/Siguiente** en la barra superior del artículo para saltar entre noticias de esa misma categoría sin tener que volver al índice.

## 3. Funcionalidades de IA (Agentes)

ReaderNews integra potentes modelos de lenguaje (como Llama3, Mixtral o GPT-4) para ayudarte a procesar información.

### Reescritura y Análisis (Artículo Individual)
Dentro de un artículo, encontrarás botones mágicos ✨:
- **Rewrite / Re-escribir**: Pide a la IA que reescriba la noticia con otro tono (ej. "Explícamelo como si tuviera 5 años", "Resume en bullet points").
- **Summarize Video**: Si la fuente es YouTube, este botón descargará la transcripción (subtítulos) y generará un artículo de lectura completo.

### Síntesis (Múltiples Artículos)
¿Quieres un resumen de todo lo que pasó hoy en Tecnología?
1. En el Dashboard, **marca las casillas** (checkbox) de varios artículos.
2. Aparecerá una barra flotante inferior. Haz clic en **"AI Rewrite"** (icono chispas).
3. Selecciona un "Prompt" (ej. "Boletín de Noticias") y el motor de IA.
4. El sistema leerá todos los artículos seleccionados y escribirá un nuevo informe consolidado para ti.

### Notas y Destacados
- Selecciona cualquier texto dentro de un artículo con el ratón.
- Aparecerá un menú emergente para **"Guardar Highlight"**.
- Puedes añadir una nota personal y etiquetas. Estas notas se guardan en la sección **"My Notes"** del menú lateral.

## 4. Configuración Personal

### Gestión de Modelos de IA
Ve a **Settings (Configuración)** en el menú superior derecho:
- Aquí puedes añadir tus propias claves API (Providers) para Groq, OpenRouter u OpenAI.
- **Round Robin**: Es una función especial que permite rotar entre varias claves gratuitas para que nunca te quedes sin servicio por límites de uso.

### Perfil
En **Profile**, puedes cambiar tu contraseña, nombre y preferencias de cuenta.

### Actualizar feeds
Para actualizar los feeds hay que correr php artisan queue:work