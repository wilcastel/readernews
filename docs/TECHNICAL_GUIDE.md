# Manual Técnico de ReaderNews (v1.0)

Este documento detalla los requisitos técnicos, instalación y configuración de despliegue para la aplicación ReaderNews.

## 1. Requisitos del Sistema
- **PHP**: Versión 8.2 o superior.
- **Base de Datos**: MySQL 8.0, MariaDB 10.x, o PostgreSQL.
- **Node.js**: v18+ (para compilar assets).
- **Extensiones PHP**: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pcre`, `pdo`, `tokenizer`, `xml`.
- **Composer**: Gestor de dependencias PHP.

## 2. Instalación Local (Desarrollo)
1. Clonar el repositorio:
   ```bash
   git clone <repo-url>
   cd readernews
   ```
2. Instalar dependencias PHP:
   ```bash
   composer install
   ```
3. Configurar entorno:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Edita el archivo `.env` con tus credenciales de base de datos.*
4. Migrar base de datos:
   ```bash
   php artisan migrate
   ```
5. Instalar dependencias Frontend y compilar:
   ```bash
   npm install && npm run build
   ```
6. Enlace simbólico para almacenamiento:
   ```bash
   php artisan storage:link
   ```

## 3. Despliegue en Producción (CloudPanel / VPS / Shared)

ReaderNews depende críticamente de **procesos en segundo plano** para:
- Actualizar Feeds RSS periódicamente.
- Procesar tareas de IA (que pueden tardar 10-30 segundos).
- Importar datos de YouTube.

### Configuración de Cron Jobs (Obligatorio)
Si utilizas **CloudPanel** o un hosting que no permite gestores de procesos como Supervisor, debes configurar los siguientes Cron Jobs.

#### A. El Programador (Scheduler)
Se encarga de disparar tareas programadas (como buscar nuevos feeds cada hora).
- **Frecuencia**: `* * * * *` (Cada minuto)
- **Comando**:
  ```bash
  php /ruta/absoluta/a/tu/proyecto/artisan schedule:run >> /dev/null 2>&1
  ```

#### B. El Trabajador de Colas (Queue Worker)
Procesa las tareas "pesadas" (IA, Scrapers) a medida que llegan. Usamos la opción `--stop-when-empty` para que el proceso no se quede colgado eternamente consumiendo RAM en entornos compartidos, sino que procese lo que hay y termine, reiniciándose al minuto siguiente.
- **Frecuencia**: `* * * * *` (Cada minuto)
- **Comando**:
  ```bash
  php /ruta/absoluta/a/tu/proyecto/artisan queue:work --stop-when-empty --tries=3 --timeout=120 >> /dev/null 2>&1
  ```
  *(Nota: Ajusta `/ruta/absoluta/a/tu/proyecto/` según tu servidor, ej: `/home/usuario/htdocs/dominio.com/`)*.

### Configuración con Supervisor (Recomendado para VPS Root)
Si tienes acceso root y puedes instalar Supervisor, es más eficiente usar esta configuración en `/etc/supervisor/conf.d/readernews-worker.conf`:

```ini
[program:readernews-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/a/tu/proyecto/artisan queue:work --tries=3 --timeout=120
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/ruta/a/tu/proyecto/storage/logs/worker.log
stopwaitsecs=3600
```

## 4. Arquitectura de IA
La aplicación gestiona múltiples proveedores de IA a través de la tabla `ai_configs`.
- **API Keys**: Se almacenan encriptadas en la base de datos (no en `.env` para permitir multitenancy o rotación dinámica).
- **Round Robin**: El sistema soporta una configuración especial (`provider: round-robin`) que rota aleatoriamente entre claves configuradas como "Free Tier" para evitar límites de tasa (Rate Limits) en servicios como Groq.

## 5. Solución de Problemas (Troubleshooting)

| Síntoma | Causa Probable | Solución |
|---------|----------------|----------|
| **Feeds no actualizan** | Cron Job no configurado | Verifica que `schedule:run` se ejecute cada minuto. |
| **Error 500 en IA** | Timeout o API Key inválida | Revisa `storage/logs/laravel.log`. Aumenta `timeout` en `config/database.php` o verifica créditos de API. |
| **Imágenes rotas** | Enlace simbólico faltante | Ejecuta `php artisan storage:link`. |
| **Estilos CSS rotos** | Assets no compilados | Ejecuta `npm run build` en el servidor o sube la carpeta `public/build` desde local. |
