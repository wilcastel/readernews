#!/bin/bash

# Módulo para finalización con decisiones interactivas
# Parte del sistema MCP Git Workflow Profesional

# Cargar utilidades
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/utils.sh"

finish_feature() {
    log "info" "Iniciando finalización de característica..."
    log "highlight" "=== FINALIZACIÓN DE CARACTERÍSTICA ==="
    
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
    
    # Detectar problemas críticos antes de proceder
    log "info" "Verificando estado del repositorio..."
    
    if has_conflicts; then
        log "error" "Hay conflictos sin resolver en el repositorio"
        handle_error 1 "Conflictos pendientes de resolución"
        return 1
    fi
    
    if has_uncommitted_changes; then
        log "error" "Hay cambios sin commitear"
        log "info" "Por favor, commitea todos los cambios antes de finalizar la característica"
        show_changes_summary "$current_branch"
        
        echo -e "\n${YELLOW}Sugerencia:${NC} Puedes usar:"
        echo -e "  ${GREEN}git add .${NC}"
        echo -e "  ${GREEN}git commit -m \"Descripción de cambios\"${NC}"
        echo -e "  ${GREEN}mcp git-workflow finish-feature${NC}"
        
        return 1
    fi
    
    log "success" "Verificación de estado completada"
    
    # Mostrar resumen de la característica
    log "info" "Preparando finalización de característica: $current_branch"
    echo -e "\n${CYAN}=== RESUMEN DE LA CARACTERÍSTICA ===${NC}"
    show_changes_summary "$current_branch"
    
    # Contexto y ayuda
    show_context_help "finish-feature"
    
    # Pregunta interactiva principal
    echo -e "\n${BLUE}=== DECISIÓN DE FINALIZACIÓN ===${NC}"
    echo -e "Rama: ${GREEN}$current_branch${NC}"
    echo -e "\n¿Qué deseas hacer con esta característica?"
    
    while true; do
        echo -e "\n${YELLOW}[1]${NC} Enviar a revisión por líder técnico (recomendado)"
        echo -e "${YELLOW}[2]${NC} Merge directo a develop (si tienes permisos)"
        echo -e "${YELLOW}[3]${NC} Mostrar cambios detallados"
        echo -e "${YELLOW}[4]${NC} Cancelar operación"
        
        local choice
        echo -e "\nSelección [1-4]: "
        read -r choice
        
        case $choice in
            1) 
                send_for_review "$current_branch"
                break
                ;;
            2) 
                merge_directly "$current_branch"
                break
                ;;
            3) 
                show_detailed_changes "$current_branch"
                echo -e "\n${CYAN}Presiona Enter para continuar...${NC}"
                read -r
                ;;
            4) 
                log "info" "Operación cancelada por el usuario"
                return 0
                ;;
            *) 
                echo -e "${RED}Opción inválida.${NC} Por favor, selecciona 1-4."
                ;;
        esac
    done
}

send_for_review() {
    local feature_branch=$1
    local leader_email="${MCP_GIT_LEADER_EMAIL:-lider@empresa.com}"
    
    log "info" "Preparando envío para revisión por líder técnico..."
    log "highlight" "=== ENVIANDO PARA REVISIÓN ==="
    
    # Verificar si la rama ya existe en remoto
    if remote_branch_exists "$feature_branch"; then
        log "info" "La rama $feature_branch ya existe en remoto"
        log "info" "Se actualizará con los cambios más recientes"
    else
        log "info" "La rama $feature_branch no existe en remoto"
        log "info" "Se creará y subirá por primera vez"
    fi
    
    # Mostrar información de la operación
    echo -e "\n${CYAN}Información de la operación:${NC}"
    echo -e "${CYAN}Rama:${NC} $feature_branch"
    echo -e "${CYAN}Destino:${NC} origin/$feature_branch"
    echo -e "${CYAN}Líder técnico:${NC} $leader_email"
    echo -e "${CYAN}Próximo paso:${NC} El líder revisará y creará PR"
    
    if ! confirm_action "¿Confirmas que deseas enviar esta característica para revisión"; then
        log "info" "Operación cancelada"
        return 0
    fi
    
    # Actualizar/crear rama en remoto
    log "info" "Subiendo/actualizando $feature_branch en origin..."
    
    if git push origin "$feature_branch" 2>/dev/null; then
        log "success" "¡Rama subida/actualizada exitosamente!"
    else
        log "error" "Falló el envío de la rama"
        log "info" "Posibles causas:"
        log "info" "• Problemas de conexión con el servidor"
        log "info" "• Conflictos que deben resolverse primero"
        log "info" "• Permisos insuficientes en el repositorio"
        
        if confirm_action "¿Deseas intentar con --force-with-lease"; then
            log "info" "Intentando con force push seguro..."
            if git push --force-with-lease origin "$feature_branch" 2>/dev/null; then
                log "success" "¡Force push exitoso!"
            else
                log "error" "Force push también falló"
                return 1
            fi
        else
            return 1
        fi
    fi
    
    # Notificar al líder
    notify_leader "$feature_branch" "$leader_email"
    
    log "success" "¡Característica enviada para revisión exitosamente!"
    log "highlight" "=== RESUMEN DE LA OPERACIÓN ==="
    log "info" "Rama en origin: $feature_branch"
    log "info" "El líder técnico deberá:"
    log "info" "• Revisar el código"
    log "info" "• Crear un Pull Request si todo está bien"
    log "info" "• Aprobar y mergear a develop"
    
    echo -e "\n${CYAN}Sugerencias:${NC}"
    echo -e "• Comunícate con tu líder técnico para informarle sobre el envío"
    echo -e "• Estar atento a posibles comentarios o solicitudes de cambios"
    echo -e "• Puedes seguir trabajando en otras características mientras tanto"
    
    return 0
}

merge_directly() {
    local feature_branch=$1
    
    log "info" "Preparando merge directo a develop..."
    log "highlight" "=== MERGE DIRECTO A DEVELOP ==="
    
    # Verificar que develop esté actualizada
    log "info" "Verificando estado de develop..."
    
    # Fetch de develop
    git fetch origin "$MCP_GIT_DEVELOP_BRANCH" 2>/dev/null || {
        log "error" "No se pudo obtener información de develop"
        return 1
    }
    
    # Comparar commits
    local develop_local=$(git rev-parse "$MCP_GIT_DEVELOP_BRANCH" 2>/dev/null || echo "")
    local develop_remote=$(git rev-parse "origin/$MCP_GIT_DEVELOP_BRANCH" 2>/dev/null || echo "")
    
    if [[ "$develop_local" != "$develop_remote" ]]; then
        log "warning" "La rama develop no está actualizada"
        log "info" "Local: ${develop_local:0:8}"
        log "info" "Remoto: ${develop_remote:0:8}"
        
        if ! confirm_action "¿Deseas actualizar develop antes del merge (recomendado)"; then
            log "warning" "Continuando con merge sin actualizar develop"
        else
            log "info" "Actualizando develop..."
            
            # Guardar posición actual
            local original_branch=$(get_current_branch)
            
            # Actualizar develop
            git checkout "$MCP_GIT_DEVELOP_BRANCH" 2>/dev/null || {
                log "error" "No se pudo cambiar a develop"
                return 1
            }
            
            if git pull origin "$MCP_GIT_DEVELOP_BRANCH" 2>/dev/null; then
                log "success" "Develop actualizada exitosamente"
            else
                log "error" "Falló la actualización de develop"
                git checkout "$original_branch" 2>/dev/null
                return 1
            fi
            
            # Volver a feature
            git checkout "$feature_branch" 2>/dev/null || {
                log "error" "No se pudo volver a la rama feature"
                return 1
            }
        fi
    else
        log "success" "Develop está actualizada"
    fi
    
    # Confirmación final del merge
    echo -e "\n${YELLOW}⚠️  IMPORTANTE:${NC} Se realizará merge de:"
    echo -e "${CYAN}Desde:${NC} $feature_branch"
    echo -e "${CYAN}Hacia:${NC} $MCP_GIT_DEVELOP_BRANCH"
    echo -e "${CYAN}Tipo:${NC} Merge con commit (no fast-forward)"
    
    if ! confirm_action "¿Confirmas que deseas realizar este merge directo"; then
        log "info" "Operación de merge cancelada"
        return 0
    fi
    
    # Realizar merge
    log "info" "Realizando merge de $feature_branch → $MCP_GIT_DEVELOP_BRANCH..."
    
    # Cambiar a develop
    if ! git checkout "$MCP_GIT_DEVELOP_BRANCH" 2>/dev/null; then
        log "error" "No se pudo cambiar a develop"
        return 1
    fi
    
    # Realizar merge con mensaje descriptivo
    local merge_message="Merge feature: $feature_branch\n\nCaracterística mergeada desde $feature_branch a develop usando MCP Git Workflow"
    
    if git merge --no-ff "$feature_branch" -m "$merge_message" 2>/dev/null; then
        log "success" "Merge realizado exitosamente"
    else
        log "error" "El merge falló"
        log "info" "Posibles causas:"
        log "info" "• Conflictos entre ramas que deben resolverse manualmente"
        log "info" "• Incompatibilidad de cambios"
        
        # Volver a feature para resolver
        git checkout "$feature_branch" 2>/dev/null || {
            log "error" "No se pudo volver a la rama feature"
            return 1
        }
        
        log "info" "Por favor, resuelve los conflictos manualmente y vuelve a intentar"
        return 1
    fi
    
    # Subir merge a origin
    log "info" "Subiendo merge a origin..."
    
    if git push origin "$MCP_GIT_DEVELOP_BRANCH" 2>/dev/null; then
        log "success" "Merge subido exitosamente a origin"
    else
        log "error" "Falló el push del merge"
        log "info" "El merge se realizó localmente pero no se pudo subir"
        git checkout "$feature_branch" 2>/dev/null
        return 1
    fi
    
    # Volver a feature (opcional pero recomendado)
    if git checkout "$feature_branch" 2>/dev/null; then
        log "info" "Regresado a $feature_branch"
    fi
    
    log "success" "¡Merge completado exitosamente!"
    log "highlight" "=== RESUMEN DEL MERGE ==="
    log "info" "$feature_branch ha sido mergeada a $MCP_GIT_DEVELOP_BRANCH"
    log "info" "El merge está disponible en origin para todo el equipo"
    
    echo -e "\n${CYAN}Próximos pasos:${NC}"
    echo -e "• La característica está ahora en develop"
    echo -e "• Puedes continuar con el siguiente paso de tu flujo de trabajo"
    echo -e "• Considera eliminar la rama feature si ya no es necesaria"
    
    return 0
}

# Si se ejecuta directamente
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    # Cargar configuración si existe
    source "$(dirname "${BASH_SOURCE[0]}")/../config.env" 2>/dev/null || {
        echo "Error: No se encontró config.env"
        exit 1
    }
    
    log "info" "Módulo finish-feature cargado"
    finish_feature "$@"
fi