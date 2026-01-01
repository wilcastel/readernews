#!/bin/bash

# Script para desplegar el estado actual en la rama 'trycode' sin afectar la rama actual.
# Uso: ./scripts/deploy_trycode.sh

# Asegurar que estamos en la raíz (opcional, pero buena práctica si se llama desde otros lados)
# cd "$(dirname "$0")/.."

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

echo "---------------------------------------------------------"
echo "🚀 Iniciando despliegue a rama 'trycode'"
echo "📂 Rama actual: $CURRENT_BRANCH"
echo "---------------------------------------------------------"

# 1. Agregar todos los cambios actuales al área de preparación (staging)
# Esto es necesario para que write-tree capture los archivos nuevos o modificados
git add .

if [ $? -ne 0 ]; then
    echo "❌ Error al agregar archivos. Verifica el estado de git."
    exit 1
fi

# 2. Crear un objeto 'tree' con el contenido actual del staging
TREE=$(git write-tree)

# 3. Crear un commit huérfano (sin padre) a partir de ese árbol
# Esto crea un "estado puro" del proyecto tal como está ahora
TIMESTAMP=$(date "+%Y-%m-%d %H:%M:%S")
COMMIT_MSG="Trycode deploy: Snapshot desde $CURRENT_BRANCH el $TIMESTAMP"
COMMIT_ID=$(echo "$COMMIT_MSG" | git commit-tree $TREE)

echo "📸 Snapshot creado: $COMMIT_ID"

# 4. Actualizar la referencia de la rama local 'trycode' para que apunte a este nuevo commit
# Si la rama no existe, se crea. Si existe, se sobrescribe (efecto de rama huérfana/reset)
git update-ref refs/heads/trycode $COMMIT_ID

# 5. Forzar el push a la rama 'trycode' en el remoto
echo "☁️  Subiendo cambios a origin/trycode (Force Push)..."
git push -f origin trycode

if [ $? -eq 0 ]; then
    echo "---------------------------------------------------------"
    echo "✅ ÉXITO: Tu código actual ha sido desplegado en 'origin/trycode'."
    echo "👉 Tus cambios locales siguen aquí (en staging), listos para hacer commit en '$CURRENT_BRANCH'."
    echo "---------------------------------------------------------"
else
    echo "❌ ERROR: Falló la subida a origin/trycode."
    exit 1
fi
