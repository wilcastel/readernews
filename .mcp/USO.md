# 🚀 Instrucciones de Uso - Sistema MCP Git Workflow Profesional

## 📋 TABLA DE CONTENIDOS
- [🚀 Instrucciones de Uso - Sistema MCP Git Workflow Profesional](#-instrucciones-de-uso---sistema-mcp-git-workflow-profesional)
  - [📋 Tabla de Contenidos](#-tabla-de-contenidos)
  - [✅ Resumen Rápido](#-resumen-rápido)
  - [🎯 Flujo de Trabajo Completo](#-flujo-de-trabajo-completo)
  - [🔧 Comandos Principales](#-comandos-principales)
  - [📖 Guía Detallada por Comando](#-guía-detallada-por-comando)
  - [⚙️ Configuración Personalizada](#️-configuración-personalizada)
  - [🔍 Solución de Problemas](#-solución-de-problemas)
  - [💡 Mejores Prácticas](#-mejores-prácticas)
  - [🔗 Integración con MCP/OpenCode](#-integración-con-mcpopencode)

## ✅ RESUMEN RÁPIDO

**¿Qué hace este sistema?**
- Gestiona flujo Git profesional con rama `trycode` para pruebas
- Ofrece control por roles: **revisión por líder** vs **merge directo**
- Detecta automáticamente errores y conflictos
- Interfaz interactiva y segura

**Comandos esenciales:**
```bash
# Probar en servidor (reinicia trycode)
bash .mcp/git-workflow.sh test-in-server

# Finalizar característica (interactivo)
bash .mcp/git-workflow.sh finish-feature

# Ver estado
bash .mcp/git-workflow.sh status
```

## 🎯 FLUJO DE TRABAJO COMPLETO

```mermaid
graph TD
    A[Developer en feature/HU-xxx] --> B[Trabajo normal]
    B --> C{¿Quiero probar en servidor?}
    C -->|Sí| D[bash test-in-server]
    C -->|No| B
    D --> E[Confirmar sobrescritura]
    E --> F[Reiniciar trycode]
    F --> G[Servidor actualiza automáticamente]
    G --> H[Developer prueba en servidor]
    H --> I{¿Funciona correctamente?}
    I -->|No| J[Corregir código]
    J --> D
    I -->|Sí| K[bash finish-feature]
    K --> L{¿Revisión por líder?}
    L -->|Sí| M[Enviar solo feature a origin]
    L -->|No| N[Merge directo a develop]
    M --> O[Líder revisa y hace PR]
    N --> P[Fin del flujo]
    O --> P
```

## 🔧 COMANDOS PRINCIPALES

### **1. `test-in-server` - Probar en Servidor**
Reinicia completamente la rama `trycode` con tu código actual para pruebas en servidor.

```bash
bash .mcp/git-workflow.sh test-in-server
```

**Qué hace:**
- ✅ Detecta tu rama feature actual
- ✅ Verifica que no haya conflictos
- ✅ Confirma antes de sobrescribir trycode
- ✅ Reinicia trycode con tu código
- ✅ Hace force push al servidor
- ✅ Servidor se actualiza automáticamente

**Cuándo usarlo:**
- Cuando quieras verificar que tu código funciona en el servidor real
- Antes de considerar tu trabajo como "terminado"
- Para validar cambios importantes

### **2. `finish-feature` - Finalizar Característica**
Interactivo - te pregunta si quieres enviar a revisión por líder o hacer merge directo.

```bash
bash .mcp/git-workflow.sh finish-feature
```

**Opciones interactivas:**
1. **Enviar a revisión por líder** - Solo sube la rama, el líder hará PR después
2. **Merge directo a develop** - Merge inmediato si tienes permisos
3. **Mostrar cambios detallados** - Ver commits y archivos modificados
4. **Cancelar** - Salir sin hacer nada

**Qué hace:**
- ✅ Verifica que estás en una rama feature válida
- ✅ Confirma que no hay cambios sin commitear
- ✅ Pregunta interactivamente tu decisión
- ✅ Ejecuta la opción seleccionada

### **3. `status` - Ver Estado**
Muestra información completa del estado actual del flujo.

```bash
bash .mcp/git-workflow.sh status
```

**Muestra:**
- Rama actual y su tipo
- Estado de develop (actualizada/desactualizada)
- Estado de trycode
- Resumen de cambios en tu feature
- Sugerencias según el estado actual

## 📖 GUÍA DETALLADA POR COMANDO

### **GUÍA COMPLETA: `test-in-server`**

**Paso 1:** Ejecutar el comando
```bash
cd /home/wil/web/readernews
bash .mcp/git-workflow.sh test-in-server
```

**Paso 2:** El sistema verificará automáticamente:
- ✅ Si estás en una rama feature válida
- ✅ Si hay conflictos sin resolver
- ✅ Si hay cambios sin commitear

**Paso 3:** Verás un resumen como este:
```
[INFO] Rama detectada: feature/HU-123-mi-caracteristica
[SUCCESS] Rama feature válida detectada
[INFO] Verificando estado del repositorio...
[SUCCESS] Verificación de estado completada

=== RESUMEN DE CAMBIOS A PROBAR ===
[INFO] Cambios en rama feature/HU-123-mi-caracteristica:
Commits adelantados: 3
f1a2b3c Nueva funcionalidad de login
4d5e6f7 Corrección de estilos
7g8h9i0 Actualización de documentación
Archivos modificados: 5
```

**Paso 4:** Confirmación de seguridad:
```
⚠️  IMPORTANTE: La rama trycode será completamente sobrescrita
Esta acción no se puede deshacer

¿Estás seguro de que deseas reiniciar trycode con el código actual? (s/N):
```

**Paso 5:** Proceso de reinicio:
```
[INFO] Reiniciando rama trycode...
[SUCCESS] Rama trycode creada/reiniciada exitosamente
[INFO] Subiendo trycode a origin (force push)...
[SUCCESS] ¡Push forzado completado exitosamente!
[SUCCESS] ¡Proceso de prueba en servidor completado!

=== RESUMEN DE LA OPERACIÓN ===
[INFO] Rama trycode actualizada con: feature/HU-123-mi-caracteristica
[INFO] El servidor debería actualizarse automáticamente
[INFO] URL de prueba: https://readernews.test

¡Listo para probar tus cambios en el servidor!
```

### **GUÍA COMPLETA: `finish-feature`**

**Paso 1:** Ejecutar el comando
```bash
bash .mcp/git-workflow.sh finish-feature
```

**Paso 2:** Verificación inicial
```
[INFO] Finalizando característica: feature/HU-123-mi-caracteristica
[SUCCESS] Verificación de estado completada

=== RESUMEN DE LA CARACTERÍSTICA ===
[INFO] Cambios en rama feature/HU-123-mi-caracteristica:
Commits adelantados: 3
Archivos modificados: 5
```

**Paso 3:** Decisión interactiva
```
=== DECISIÓN DE FINALIZACIÓN ===
Rama: feature/HU-123-mi-caracteristica

¿Qué deseas hacer con esta característica?

[1] Enviar a revisión por líder técnico (recomendado)
[2] Merge directo a develop (si tienes permisos)
[3] Mostrar cambios detallados
[4] Cancelar operación

Selección [1-4]:
```

**Opción 1: Enviar a revisión**
```
=== ENVIANDO PARA REVISIÓN ===
[INFO] Preparando envío para revisión por líder técnico...

Información de la operación:
Rama: feature/HU-123-mi-caracteristica
Destino: origin/feature/HU-123-mi-caracteristica
Líder técnico: lider@empresa.com
Próximo paso: El líder revisará y creará PR

¿Confirmas que deseas enviar esta característica para revisión? (s/N): s

[INFO] Subiendo/actualizando rama en origin...
[SUCCESS] ¡Rama subida/actualizada exitosamente!
[SUCCESS] ¡Característica enviada para revisión exitosamente!

=== RESUMEN DE LA OPERACIÓN ===
[INFO] Rama en origin: feature/HU-123-mi-caracteristica
[INFO] El líder técnico deberá:
[INFO] • Revisar el código
[INFO] • Crear un Pull Request si todo está bien
[INFO] • Aprobar y mergear a develop

Sugerencias:
• Comunícate con tu líder técnico para informarle sobre el envío
• Estar atento a posibles comentarios o solicitudes de cambios
• Puedes seguir trabajando en otras características mientras tanto
```

**Opción 2: Merge directo**
```
=== MERGE DIRECTO A DEVELOP ===
[INFO] Preparando merge directo a develop...

Información de la operación:
Desde: feature/HU-123-mi-caracteristica
Hacia: develop
Tipo: Merge con commit (no fast-forward)

¿Confirmas que deseas realizar este merge directo? (s/N): s

[INFO] Verificando estado de develop...
[SUCCESS] Develop está actualizada
[INFO] Realizando merge...
[SUCCESS] Merge realizado exitosamente
[INFO] Subiendo merge a origin...
[SUCCESS] Merge subido exitosamente a origin

=== RESUMEN DEL MERGE ===
[INFO] feature/HU-123-mi-caracteristica ha sido mergeada a develop
[INFO] El merge está disponible en origin para todo el equipo

Próximos pasos:
• La característica está ahora en develop
• Puedes continuar con el siguiente paso de tu flujo de trabajo
• Considera eliminar la rama feature si ya no es necesaria
```

## ⚙️ CONFIGURACIÓN PERSONALIZADA

Edita el archivo `.mcp/config.env`:

```bash
# Editor de preferencia
nano .mcp/config.env

# O usando VS Code
code .mcp/config.env
```

**Variables principales:**
- `MCP_GIT_DEVELOP_BRANCH` - Rama principal (default: develop)
- `MCP_GIT_TRYCODE_BRANCH` - Rama de pruebas (default: trycode)
- `MCP_GIT_LEADER_EMAIL` - Email del líder técnico
- `MCP_GIT_LOG_LEVEL` - Nivel de detalle (normal/detailed/debug)
- `MCP_GIT_CONFIRM_OVERWRITE` - Siempre pedir confirmación (true/false)

## 🔍 SOLUCIÓN DE PROBLEMAS

### **Problema: "No se pudo detectar la rama actual"**
**Causa:** No estás en un repositorio Git válido
**Solución:**
```bash
git status  # Verifica que estás en un repo Git válido
cd /ruta/a/tu/proyecto  # Ve al directorio correcto
```

### **Problema: "Debes estar en una rama feature"**
**Causa:** No estás en una rama que siga el patrón feature/* o HU-*
**Solución:**
```bash
# Crear o cambiar a una rama feature
git checkout -b feature/mi-nueva-caracteristica
# O
git checkout -b HU-123-descripcion
```

### **Problema: "Hay cambios sin commitear"**
**Causa:** Tienes cambios en working directory sin commit
**Solución:**
```bash
git add .
git commit -m "Descripción de los cambios"
# Luego vuelve a ejecutar el comando
```

### **Problema: "Hay conflictos sin resolver"**
**Causa:** Hay conflictos de merge pendientes
**Solución:**
```bash
git status  # Ver conflictos
git mergetool  # O resolver manualmente
git add .
git commit -m "Resolución de conflictos"
```

### **Problema: "Falló el push"**
**Causa:** Problemas de conexión o permisos
**Solución:**
```bash
# Verificar conexión
git remote -v
git ls-remote origin HEAD

# Verificar permisos (si es necesario)
git remote set-url origin https://usuario:token@github.com/repo.git
```

## 💡 MEJORES PRÁCTICAS

### **1. Commit Frecuente**
```bash
# Hacer commits pequeños y frecuentes
git add .
git commit -m "feat: agregada validación de formulario"
# No esperes hasta el final para commitear
```

### **2. Pruebas Regulares**
```bash
# Probar cada vez que completes una funcionalidad
bash .mcp/git-workflow.sh test-in-server

# No esperes hasta tener todo terminado
```

### **3. Mensajes de Commit Claros**
```bash
# Usa formato convencional:
git commit -m "feat: agregado login con Google"
git commit -m "fix: corregido error en validación"
git commit -m "docs: actualizada documentación de API"
```

### **4. Comunicación con Equipo**
- **Si envías a revisión:** Notifica al líder técnico
- **Si haces merge:** Informa al equipo del nuevo código en develop
- **Documenta cambios importantes** en el PR o commit

### **5. Limpieza de Ramas**
```bash
# Después de que se mergee, elimina la rama feature
git checkout develop
git branch -d feature/HU-123-mi-trabajo
git push origin --delete feature/HU-123-mi-trabajo
```

## 🔗 INTEGRACIÓN CON MCP/OPENCODE

### **Configuración para OpenCode**

1. **Crear archivo de configuración MCP:**
```bash
# En tu directorio de configuración de OpenCode
cat > mcp-config.json << 'EOF'
{
    "mcpServers": {
        "git-workflow": {
            "command": "bash",
            "args": ["/home/wil/web/readernews/.mcp/git-workflow.sh"],
            "env": {}
        }
    }
}
EOF
```

2. **Comandos desde OpenCode:**
```bash
# Desde cualquier lugar en tu proyecto
mcp git-workflow test-in-server
mcp git-workflow finish-feature
mcp git-workflow status
```

### **Configuración para AntiGravity**

1. **Agregar a tu configuración de AntiGravity:**
```json
{
    "gitWorkflow": {
        "enabled": true,
        "scriptPath": "/home/wil/web/readernews/.mcp/git-workflow.sh"
    }
}
```

2. **Uso desde AntiGravity:**
```bash
# Comandos disponibles
antigravity git-workflow test-in-server
antigravity git-workflow finish-feature
```

---

## 🎉 ¡SISTEMA LISTO PARA USO!

**Estado:** ✅ Sistema completamente funcional e implementado
**Próximo paso:** ¡Comenzar a usarlo en tu flujo de trabajo diario!

**¿Preguntas o problemas?** El sistema incluye ayuda contextual y detección inteligente de errores para guiarte en cada paso.