#!/bin/bash

# Configuration
PROJECT_PATH="/home/wil/web/readernews"
USER_NAME="wil"
PHP_BIN="/usr/bin/php"

echo "Configurando servicios en segundo plano para ReaderNews..."

# 1. Configurar Cron para el Scheduler
CRON_JOB="* * * * * cd $PROJECT_PATH && $PHP_BIN artisan schedule:run >> /dev/null 2>&1"
(crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
echo "✅ Cron configurado para el Scheduler."

# 2. Crear el servicio de Systemd para el Queue Worker
SERVICE_FILE="/tmp/readernews-worker.service"

cat <<EOF > $SERVICE_FILE
[Unit]
Description=Laravel Queue Worker (readernews)
After=network.target

[Service]
User=$USER_NAME
Group=$USER_NAME
Restart=always
ExecStart=$PHP_BIN $PROJECT_PATH/artisan queue:work --tries=3 --timeout=90
WorkingDirectory=$PROJECT_PATH

[Install]
WantedBy=multi-user.target
EOF

echo "✅ Archivo de servicio pre-generado en $SERVICE_FILE"
echo ""
echo "Para completar la instalación, ejecuta los siguientes comandos:"
echo "------------------------------------------------------------"
echo "sudo mv $SERVICE_FILE /etc/systemd/system/readernews-worker.service"
echo "sudo systemctl daemon-reload"
echo "sudo systemctl enable readernews-worker.service"
echo "sudo systemctl start readernews-worker.service"
echo "------------------------------------------------------------"

echo "Nota: El scheduler de Laravel ya ha sido actualizado a cada 5 minutos en routes/console.php"
