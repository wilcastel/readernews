#!/bin/bash

# Módulo para reiniciar trycode con código actual de feature
# Parte del sistema MCP Git Workflow Profesional

# Cargar utilidades
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/utils.sh"

test_in_server() {
    log "info" "Iniciando prueba en servidor..."
    log "highlight" "=== SISTEMA DE PRUEBAS EN SERVIDOR ==="
    
    # Detectar rama actual
    local current_branch=$(get_current_branch)
    
    if [[ -z "$current_branch" ]]; then
        log "error" "No se pudo detectar la rama actual"
        handle_error 1 "No se pudo determinar la rama de Git"
        return 1
    fi
    
    log "info" "Rama detectada: $current_branch"
    
    # Verificar que sea feature
    if ! is_feature_branch "$current_branch"; then
        log "error" "Debes estar en una rama feature (feature/* o HU-*)"
        log "info" "Rama actual: $current_branch"
        handle_error 1 "Rama no es una feature válida"
        return 1
    fi
    
    log "success" "Rama feature válida detectada"
    
    # Detectar problemas antes de proceder
    log "info" "Verificando estado del repositorio..."
    
    if has_conflicts; then
        log "error" "Hay conflictos sin resolver en el repositorio"
        handle_error 1 "Conflictos pendientes de resolución"
        return 1
    fi
    
    if has_uncommitted_changes; then
        log "warning" "Se detectaron cambios sin commitear"
        show_changes_summary "$current_branch"
        
        if ! confirm_action "Hay cambios sin commitear. ¿Deseas continuar (se perderán si no están guardados)"; then
            log "info" "Operación cancelada por el usuario"
            return 0
        fi
    fi
    
    log "success" "Verificación de estado completada"
    
    # Mostrar resumen de lo que se va a probar
    log "info" "Preparando prueba de rama: $current_branch"
    echo -e "\n${CYAN}=== RESUMEN DE CAMBIOS A PROBAR ===${NC}"
    show_changes_summary "$current_branch"
    
    # Context help
    show_context_help "test-server"
    
    # Confirmación de seguridad
    echo -e "\n${YELLOW}⚠️  IMPORTANTE:${NC} La rama trycode será completamente sobrescrita"
    echo -e "${YELLOW}Esta acción no se puede deshacer${NC}"
    
    if ! confirm_action "¿Estás seguro de que deseas reiniciar trycode con el código actual"; then
        log "info" "Operación cancelada por el usuario"
        return 0
    fi
    
    # Proceso de reinicio de trycode
    log "info" "Reiniciando rama trycode..."
    
    # Guardar cambios actuales si los hay (opcional pero recomendado)
    local stash_name=""
    if has_uncommitted_changes; then
        stash_name="mcp-trycode-$(date +%s)"
        log "info" "Guardando cambios actuales temporalmente..."
        git stash push -m "$stash_name" 2>/dev/null || {
            log "warning" "No se pudieron guardar cambios temporalmente"
        }
    fi
    
    # Crear o reiniciar rama trycode
    log "info" "Creando rama trycode desde $current_branch..."
    
    # Crear rama trycode desde la actual (force create/reset)
    if git checkout -B "$MCP_GIT_TRYCODE_BRANCH" 2>/dev/null; then
        log "success" "Rama trycode creada/reiniciada exitosamente"
    else
        log "error" "No se pudo crear/reiniciar la rama trycode"
        handle_error 1 "Error al crear rama trycode"
        
        # Restaurar cambios si los hubimos guardado
        if [[ -n "$stash_name" ]]; then
            git stash pop 2>/dev/null || true
        fi
        
        return 1
    fi
    
    # Verificar que el contenido se copió correctamente
    log "info" "Verificando contenido de trycode..."
    local trycode_commit=$(git rev-parse HEAD 2>/dev/null)
    local feature_commit=$(git rev-parse "$current_branch" 2>/dev/null)
    
    if [[ "$trycode_commit" == "$feature_commit" ]]; then
        log "success" "Contenido verificado: trycode == feature"
    else
        log "warning" "Diferencia detectada entre trycode y feature"
    fi
    
    # Preparar push forzado
    log "info" "Preparando envío forzado a servidor..."
    
    # Verificar conectividad con remoto
    if ! check_remote_connectivity; then
        log "error" "No hay conectividad con el repositorio remoto"
        handle_error 128 "Sin conexión al repositorio remoto"
        
        # Volver a rama original
        git checkout "$current_branch" 2>/dev/null
        return 1
    fi
    
    # Realizar force push
    log "info" "Subiendo trycode a origin (force push)..."
    
    if git push --force-with-lease origin "$MCP_GIT_TRYCODE_BRANCH" 2>/dev/null; then
        log "success" "¡Push forzado completado exitosamente!"
    else
        log "error" "Falló el push forzado al servidor"
        handle_error 1 "Error en push forzado"
        
        # Volver a rama original
        git checkout "$current_branch" 2>/dev/null
        return 1
    fi
    
    # Volver a rama original de trabajo
    log "info" "Volviendo a rama de trabajo original..."
    if git checkout "$current_branch" 2>/dev/null; then
        log "success" "Regresado a $current_branch"
    else
        log "warning" "No se pudo volver automáticamente a $current_branch"
        log "info" "Por favor, cambia manualmente a tu rama de trabajo"
    fi
    
    # Restaurar cambios stash si los hubimos guardado
    if [[ -n "$stash_name" ]]; then
        log "info" "Restaurando cambios temporales..."
        git stash pop 2>/dev/null || {
            log "warning" "No se pudieron restaurar cambios temporales"
        }
    fi
    
    # Mensaje final de éxito
    log "success" "¡Proceso de prueba en servidor completado!"
    log "highlight" "=== RESUMEN DE LA OPERACIÓN ==="
    log "info" "Rama trycode actualizada con: $current_branch"
    log "info" "El servidor debería actualizarse automáticamente"
    log "info" "URL de prueba: $MCP_GIT_SERVER_URL"
    log "highlight" "¡Listo para probar tus cambios en el servidor!"
    
    # Sugerencias adicionales
    echo -e "\n${CYAN}Sugerencias:${NC}"
    echo -e "• Verifica que el servidor haya actualizado correctamente"
    echo -e "• Prueba todas las funcionalidades de tu característica"
    echo -e "• Si encuentras problemas, puedes corregir y volver a ejecutar este comando"
    echo -e "• Cuando estés satisfecho, usa: ${GREEN}mcp git-workflow finish-feature${NC}"
    
    return 0
}

# Si se ejecuta directamente
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    # Cargar configuración si existe
    source "$(dirname "${BASH_SOURCE[0]}")/../config.env" 2>/dev/null || {
        echo "Error: No se encontró config.env"
        exit 1
    }
    
    log "info" "Módulo test-server cargado"
    test_in_server "$@"
fi