#!/bin/bash

# Sistema MCP Git Workflow Profesional
# Sistema interactivo para gestión de flujo Git con trycode y control por roles
# Autor: Sistema MCP - WSLaragon Integration

set -euo pipefail

# Configuración y rutas
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$SCRIPT_DIR/config.env"

# Función para cargar configuración
check_and_load_config() {
    if [[ -f "$CONFIG_FILE" ]]; then
        source "$CONFIG_FILE"
        return 0
    else
        echo "Error: Archivo de configuración no encontrado: $CONFIG_FILE"
        echo "Por favor, ejecuta: mcp git-workflow setup"
        return 1
    fi
}

# Logo del sistema
show_logo() {
    echo -e "${CYAN}"
    echo "╔══════════════════════════════════════════════════════════════╗"
    echo "║                    MCP GIT WORKFLOW                        ║"
    echo "║              Sistema Profesional de Git                    ║"
    echo "║                  WSLaragon Integration                     ║"
    echo "╚══════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

# Cargar módulos
load_modules() {
    local modules_dir="$SCRIPT_DIR/modules"
    
    if [[ -d "$modules_dir" ]]; then
        source "$modules_dir/utils.sh"
        source "$modules_dir/test-server.sh"
        source "$modules_dir/finish-feature.sh"
    else
        echo "Error: Directorio de módulos no encontrado: $modules_dir"
        return 1
    fi
}

# Función de ayuda completa
show_help() {
    show_logo
    cat << EOF
${WHITE}SISTEMA DE GESTIÓN DE FLUJO GIT CON MCP${NC}

${CYAN}DESCRIPCIÓN:${NC}
Sistema interactivo para gestionar flujo de trabajo Git con:
• Rama trycode para pruebas en servidor
• Control por roles (revisión líder vs merge directo)
• Detección inteligente de errores
• Interfaz profesional y segura

${CYAN}USO:${NC}
    mcp git-workflow <comando> [opciones]

${CYAN}COMANDOS PRINCIPALES:${NC}
    ${GREEN}test-in-server${NC}      Reinicia trycode con código actual para pruebas
    ${GREEN}finish-feature${NC}      Finaliza característica (review o merge directo)
    ${GREEN}status${NC}             Muestra estado completo del flujo
    ${GREEN}help${NC}               Muestra esta ayuda detallada

${CYAN}EJEMPLOS DE USO:${NC}
    ${YELLOW}# Probar cambios en servidor${NC}
    mcp git-workflow test-in-server
    
    ${YELLOW}# Finalizar característica (interactivo)${NC}
    mcp git-workflow finish-feature
    
    ${YELLOW}# Ver estado general${NC}
    mcp git-workflow status

${CYAN}FLUJO DE TRABAJO:${NC}
    1. Trabajar en feature/HU-xxx
    2. ${GREEN}test-in-server${NC} → Probar en servidor (reinicia trycode)
    3. Corregir si es necesario
    4. ${GREEN}finish-feature${NC} → Enviar para review o merge directo

${CYAN}CONFIGURACIÓN:${NC}
    Archivo: $CONFIG_FILE
    Variables: MCP_GIT_* (ver config.env para detalles)

${CYAN}SEGURIDAD:${NC}
    • Siempre confirma antes de sobrescribir trycode
    • Detecta conflictos y cambios sin commitear
    • Usa force-with-lease para mayor seguridad
    • Respaldos temporales de cambios no commiteados

${CYAN}PARA MCP/OPENCODE:${NC}
    Este script está diseñado para integrarse con sistemas MCP
    como opencode o antigravity mediante comandos remotos.

EOF
}

# Función de estado mejorada
show_status() {
    show_logo
    log "info" "Analizando estado del sistema MCP Git Workflow..."
    
    local current_branch=$(get_current_branch)
    local develop_status="✅ Actualizada"
    local trycode_status="❌ No existe"
    local repo_info=""
    
    # Información del repositorio
    echo -e "\n${CYAN}📊 INFORMACIÓN DEL REPOSITORIO${NC}"
    get_repo_info
    
    # Estado de develop
    echo -e "\n${CYAN}🌿 ESTADO DE RAMAS${NC}"
    if [[ -n "$current_branch" ]]; then
        echo -e "${BLUE}Rama actual:${NC} $current_branch"
        
        if is_feature_branch "$current_branch"; then
            echo -e "${GREEN}✓${NC} Rama feature válida"
        else
            echo -e "${YELLOW}⚠${NC} No es una rama feature (debería ser feature/* o HU-*)"
        fi
    else
        echo -e "${RED}✗${NC} No se pudo detectar la rama actual"
    fi
    
    # Verificar develop
    if git rev-parse --verify "$MCP_GIT_DEVELOP_BRANCH" >/dev/null 2>&1; then
        git fetch origin "$MCP_GIT_DEVELOP_BRANCH" >/dev/null 2>&1 || true
        local develop_local=$(git rev-parse "$MCP_GIT_DEVELOP_BRANCH" 2>/dev/null || echo "")
        local develop_remote=$(git rev-parse "origin/$MCP_GIT_DEVELOP_BRANCH" 2>/dev/null || echo "")
        
        if [[ "$develop_local" == "$develop_remote" ]]; then
            echo -e "${GREEN}✓${NC} Develop está sincronizada"
        else
            echo -e "${YELLOW}⚠${NC} Develop está desactualizada"
            echo -e "   Local: ${develop_local:0:8}"
            echo -e "   Remoto: ${develop_remote:0:8}"
        fi
    else
        echo -e "${RED}✗${NC} Rama develop no encontrada"
    fi
    
    # Estado de trycode
    if git rev-parse --verify "$MCP_GIT_TRYCODE_BRANCH" >/dev/null 2>&1; then
        trycode_status="✅ Existe"
        local trycode_last_commit=$(git log -1 --format="%cr" "$MCP_GIT_TRYCODE_BRANCH" 2>/dev/null || echo "desconocido")
        echo -e "${GREEN}✓${NC} Trycode existe (última actualización: $trycode_last_commit)"
    else
        echo -e "${YELLOW}ℹ${NC} Trycode no existe (se creará cuando se use test-in-server)"
    fi
    
    # Estado actual si es feature
    if is_feature_branch "$current_branch"; then
        echo -e "\n${CYAN}📈 ESTADO DE LA CARACTERÍSTICA${NC}"
        show_changes_summary "$current_branch"
        
        # Sugerencias según el estado
        echo -e "\n${CYAN}💡 SUGERENCIAS:${NC}"
        if has_uncommitted_changes; then
            echo -e "• ${YELLOW}Hay cambios sin commitear${NC} - Considera hacer commit antes de probar"
        else
            echo -e "• ${GREEN}Todo está commiteado${NC} - Listo para probar o finalizar"
        fi
        
        if [[ "$develop_status" == "⚠️ Desactualizada" ]]; then
            echo -e "• ${YELLOW}Develop está desactualizada${NC} - Considera actualizar antes de finalizar"
        fi
    fi
    
    # Configuración actual
    echo -e "\n${CYAN}⚙️ CONFIGURACIÓN ACTUAL${NC}"
    echo -e "${BLUE}Rama develop:${NC} $MCP_GIT_DEVELOP_BRANCH"
    echo -e "${BLUE}Rama trycode:${NC} $MCP_GIT_TRYCODE_BRANCH"
    echo -e "${BLUE}Revisión por líder:${NC} $MCP_GIT_REVIEW_REQUIRED_ASK"
    echo -e "${BLUE}Notificar líder:${NC} $MCP_GIT_NOTIFY_LEADER"
    
    # Comandos sugeridos
    echo -e "\n${CYAN}🚀 COMANDOS SUGERIDOS:${NC}"
    if is_feature_branch "$current_branch"; then
        if has_uncommitted_changes; then
            echo -e "• ${YELLOW}Primero:${NC} git add . && git commit -m \"mensaje\""
        else
            echo -e "• ${GREEN}Probar:${NC} mcp git-workflow test-in-server"
            echo -e "• ${GREEN}Finalizar:${NC} mcp git-workflow finish-feature"
        fi
    fi
    
    echo -e "\n${GREEN}✅ Análisis completado${NC}"
}

# Función principal mejorada
main() {
    local command="${1:-help}"
    
    # Cargar configuración
    if ! check_and_load_config; then
        exit 1
    fi
    
    # Cargar módulos
    if ! load_modules; then
        exit 1
    fi
    
    # Logo solo para comandos principales
    case "$command" in
        test-in-server|finish-feature|status)
            show_logo
            ;;
    esac
    
    # Ejecutar comando
    case "$command" in
        test-in-server)
            log "info" "Ejecutando: test-in-server"
            test_in_server
            ;;
        finish-feature)
            log "info" "Ejecutando: finish-feature"
            finish_feature
            ;;
        status)
            log "info" "Ejecutando: status"
            show_status
            ;;
        help|--help|-h)
            show_help
            ;;
        setup)
            log "info" "Configurando sistema..."
            # Aquí podría ir lógica de setup si es necesario
            log "success" "Sistema configurado. Usa 'mcp git-workflow help' para ver opciones."
            ;;
        *)
            echo "Error: Comando no reconocido: $command"
            echo "Usa 'mcp git-workflow help' para ver la ayuda"
            exit 1
            ;;
    esac
}

# Si se ejecuta directamente
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi