# Sistema MCP Git Workflow Profesional
# Documentación completa de uso

## 🚀 SISTEMA IMPLEMENTADO EXITOSAMENTE

### 📋 DESCRIPCIÓN GENERAL
Sistema profesional de flujo Git con:
- ✅ **Rama trycode** para pruebas en servidor
- ✅ **Control por roles** (revisión líder vs merge directo)
- ✅ **Detección inteligente** de errores
- ✅ **Interfaz interactiva** profesional
- ✅ **Multi-proyecto** compatible

### 🎯 COMANDOS DISPONIBLES

```bash
# Prueba de sistema completo
./mcp/git-workflow.sh help
./mcp/git-workflow.sh status

# Probar en servidor (reinicia trycode)
./mcp/git-workflow.sh test-in-server

# Finalizar característica (interactivo)
./mcp/git-workflow.sh finish-feature
```

### 🔧 INTEGRACIÓN CON MCP/OPENCODE

Para usar desde opencode/antigravity, agrega a tu configuración MCP:

```json
{
    "mcpServers": {
        "git-workflow": {
            "command": "bash",
            "args": ["/home/wil/web/readernews/.mcp/git-workflow.sh"],
            "env": {}
        }
    }
}
```

**Comandos desde MCP:**
- `mcp git-workflow test-in-server`
- `mcp git-workflow finish-feature`
- `mcp git-workflow status`

### 🧪 PRUEBAS RECOMENDADAS

**1. Verificar instalación:**
```bash
cd /home/wil/web/readernews
./mcp/git-workflow.sh help
./mcp/git-workflow.sh status
```

**2. Probar trycode:**
```bash
# Asegúrate de estar en una rama feature
git checkout feature/tu-caracteristica
./mcp/git-workflow.sh test-in-server
```

**3. Probar finalización:**
```bash
./mcp/git-workflow.sh finish-feature
# Sigue las instrucciones interactivas
```

### ⚙️ CONFIGURACIÓN

Edita `.mcp/config.env` para personalizar:
- Rama develop
- Rama trycode
- Email del líder técnico
- Nivel de logs
- Comportamiento de seguridad

### 🚨 SEGURIDAD IMPLEMENTADA

- ✅ Confirmación antes de sobrescribir trycode
- ✅ Detección de conflictos sin resolver
- ✅ Detección de cambios sin commitear
- ✅ Force push con --force-with-lease
- ✅ Stash temporal de cambios no commiteados

### 📊 NIVEL DE MENSAJES

El sistema usa niveles de logging:
- `[INFO]` - Información general
- `[SUCCESS]` - Operaciones exitosas
- `[WARNING]` - Advertencias importantes
- `[ERROR]` - Errores críticos
- `[HIGHLIGHT]` - Resúmenes y resultados

### 🔄 FLUJO DE TRABAJO COMPLETO

```
Developer en feature/HU-xxx
    ↓
./mcp/git-workflow test-in-server
    ↓ [confirmación + detección de errores]
reinicia trycode con código actual
    ↓
servidor actualiza automáticamente
    ↓ [desarrollador prueba]
./mcp/git-workflow finish-feature
    ↓ [interactivo: review vs merge]
OPCIÓN 1: enviar a review    OPCIÓN 2: merge directo
    ↓                           ↓
solo subir feature          merge feature → develop
    ↓                           ↓
líder revisa y hace PR      fin del flujo
```

## 🎉 ¡SISTEMA LISTO PARA PRUEBAS!

**Estado:** ✅ IMPLEMENTADO COMPLETAMENTE
**Próximo paso:** Realizar pruebas de funcionamiento