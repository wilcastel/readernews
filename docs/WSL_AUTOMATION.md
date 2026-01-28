# Automatización en WSL (Ubuntu)

Esta guía explica cómo configurar el **Scheduler** y el **Queue Worker** de Laravel para que funcionen automáticamente en segundo plano en un entorno WSL2 (Windows Subsystem for Linux), permitiendo que la aplicación actualice los feeds y procese tareas sin necesidad de mantener terminales abiertas.

## 1. El Scheduler (Cron)

El Scheduler es el "latido" de Laravel. Se encarga de ejecutar tareas programadas (como `feeds:fetch`).

### Configuración en el Código
En `routes/console.php`, definimos la frecuencia:
```php
Schedule::command('feeds:fetch')->everyTenMinutes();
```

### Configuración en WSL
Para que Linux detecte estas tareas, debemos agregarlas al `crontab` del usuario:

1. Abre el editor de crontab:
   ```bash
   crontab -e
   ```
2. Agrega la siguiente línea al final del archivo (asegúrate de usar la ruta correcta a tu proyecto):
   ```cron
   * * * * * cd /home/wil/web/readernews && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
   ```

## 2. Queue Worker (Systemd)

Para procesar tareas pesadas (como descargar y parsear artículos) de forma asíncrona, necesitamos que `php artisan queue:work` esté siempre corriendo.

### Requisito: Systemd en WSL
Asegúrate de que tu WSL tenga `systemd` activo. Debes tener un archivo `/etc/wsl.conf` con:
```ini
[boot]
systemd=true
```

### Crear el servicio
1. Crea un archivo de servicio: `sudo nano /etc/systemd/system/readernews-worker.service`
2. Pega el siguiente contenido:
   ```ini
   [Unit]
   Description=Laravel Queue Worker (readernews)
   After=network.target

   [Service]
   User=wil
   Group=wil
   Restart=always
   ExecStart=/usr/bin/php /home/wil/web/readernews/artisan queue:work --tries=3 --timeout=90
   WorkingDirectory=/home/wil/web/readernews

   [Install]
   WantedBy=multi-user.target
   ```
3. Activa y arranca el servicio:
   ```bash
   sudo systemctl daemon-reload
   sudo systemctl enable readernews-worker.service
   sudo systemctl start readernews-worker.service
   ```

## 3. Monitoreo y Mantenimiento

- **Ver estado del worker**: `sudo systemctl status readernews-worker.service`
- **Ver logs del sistema**: `journalctl -u readernews-worker.service -f`
- **Reiniciar tras cambios de código**: Si cambias la lógica de los Jobs, debes reiniciar el worker:
  ```bash
  sudo systemctl restart readernews-worker.service
  ```

---

## Preguntas Frecuentes

### ¿Esto afecta a mis servidores en la nube (producción)?
**No de forma negativa.** 
- La configuración de **código** (`routes/console.php`) es universal. Si despliegas este cambio a un servidor en la nube, ese servidor intentará ejecutar la tarea cada 10 minutos.
- La configuración de **infraestructura** (`systemd` y `cron`) es local a la máquina. Tus servidores en la nube ya deberían tener sus propios servicios configurados de forma similar. Esta guía asegura que tu entorno de desarrollo local (WSL) se comporte de la misma manera profesional que un servidor real.

### ¿Qué pasa si apago Windows?
Al apagar Windows o cerrar WSL, los servicios se detienen. Sin embargo, al volver a abrir tu distribución de Ubuntu en WSL, **systemd** iniciará automáticamente el worker y el cron retomará sus tareas, por lo que no tendrás que ejecutar nada manualmente.
