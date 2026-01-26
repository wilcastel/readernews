#!/bin/bash

# Funciones de utilidad para el sistema MCP Git Workflow
# Este módulo proporciona funciones auxiliares para todos los scripts

# Colores para output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly PURPLE='\033[0;35m'
readonly CYAN='\033[0;36m'
readonly WHITE='\033[1;37m'
readonly NC='\033[0m' # No Color

# Función de logging mejorada
log() {
    local level=$1
    shift
    local message="$@"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    case $level in
        "info")  echo -e "${BLUE}[INFO]${NC} ${WHITE}$timestamp${NC}: $message" ;;
        "success") echo -e "${GREEN}[SUCCESS]${NC} ${WHITE}$timestamp${NC}: $message" ;;
        "warning") echo -e "${YELLOW}[WARNING]${NC} ${WHITE}$timestamp${NC}: $message" ;;
        "error") echo -e "${RED}[ERROR]${NC} ${WHITE}$timestamp${NC}: $message" ;;
        "debug") echo -e "${PURPLE}[DEBUG]${NC} ${WHITE}$timestamp${NC}: $message" ;;
        "highlight") echo -e "${CYAN}[HIGHLIGHT]${NC} ${WHITE}$timestamp${NC}: $message" ;;
    esac
}

# Detectar rama actual
get_current_branch() {
    git rev-parse --abbrev-ref HEAD 2>/dev/null || echo ""
}

# Verificar si es rama feature
is_feature_branch() {
    local branch=$1
    [[ "$branch" =~ ^feature/ ]] || [[ "$branch" =~ ^HU- ]] || [[ "$branch" =~ ^hu- ]]
}

# Detectar cambios sin commitear
has_uncommitted_changes() {
    ! git diff-index --quiet HEAD -- 2>/dev/null
}

# Detectar conflictos
has_conflicts() {
    git ls-files -u | grep -q .
}

# Verificar si rama existe en remoto
remote_branch_exists() {
    local branch=$1
    git ls-remote --heads origin "$branch" 2>/dev/null | grep -q "$branch"
}

# Confirmación interactiva mejorada
confirm_action() {
    local message=$1
    local response
    
    echo -e "\n${YELLOW}¿$message?${NC} (${GREEN}s${NC}/${RED}N${NC}): "
    read -r response
    
    [[ "$response" =~ ^[Ss]$ ]] || [[ "$response" =~ ^[Ss][Ii]$ ]] || [[ "$response" =~ ^[Yy]$ ]]
}

# Mostrar cambios resumidos
show_changes_summary() {
    local branch=$1
    
    log "info" "Cambios en rama $branch:"
    
    # Commits recientes
    local commit_count=$(git rev-list --count HEAD ^"$MCP_GIT_DEVELOP_BRANCH" 2>/dev/null || echo "0")
    if [[ $commit_count -gt 0 ]]; then
        echo -e "${CYAN}Commits adelantados:$NC $commit_count"
        git log --oneline -5 --pretty=format:"${CYAN}%h${NC} %s" 2>/dev/null | head -5
    fi
    
    # Archivos modificados
    if has_uncommitted_changes; then
        log "warning" "Hay cambios sin commitear:"
        git status --short | head -10
    fi
    
    # Tamaño de cambios
    local files_changed=$(git diff --name-only "origin/$MCP_GIT_DEVELOP_BRANCH" 2>/dev/null | wc -l || echo "0")
    if [[ $files_changed -gt 0 ]]; then
        echo -e "${CYAN}Archivos modificados:$NC $files_changed"
    fi
}

# Mostrar cambios detallados
show_detailed_changes() {
    local branch=$1
    
    log "info" "Cambios detallados en $branch:"
    echo ""
    
    # Historial gráfico
    echo -e "${CYAN}Historial de commits:${NC}"
    git log --oneline --graph --all -10 --pretty=format:"${CYAN}%h${NC} ${YELLOW}%d${NC} %s ${WHITE}(%an)${NC}" 2>/dev/null || echo "No hay historial disponible"
    
    echo ""
    
    # Estadísticas de cambios
    echo -e "${CYAN}Estadísticas de cambios:${NC}"
    git diff --stat "origin/$MCP_GIT_DEVELOP_BRANCH"..HEAD 2>/dev/null | tail -5 || echo "No hay cambios estadísticos disponibles"
    
    echo ""
    
    # Archivos modificados recientemente
    echo -e "${CYAN}Archivos modificados recientemente:${NC}"
    git diff --name-only HEAD~3..HEAD 2>/dev/null | head -10 || echo "No hay archivos modificados recientes"
}

# Notificar a líder (integración futura)
notify_leader() {
    local feature_branch=$1
    local leader_email=${2:-"lider@empresa.com"}
    
    log "info" "Notificando a líder técnico sobre $feature_branch"
    log "info" "Email: $leader_email"
    log "info" "Fecha: $(date)"
    log "info" "El líder deberá revisar y crear PR si es necesario"
    
    # Aquí se puede integrar con:
    # - Slack API
    # - Email service
    # - GitHub notifications
    # - Teams webhook
    # Por ahora es un log informativo
}

# Verificar conectividad con remoto
check_remote_connectivity() {
    git ls-remote --heads origin HEAD >/dev/null 2>&1
}

# Obtener información del repositorio
get_repo_info() {
    local remote_url=$(git config --get remote.origin.url 2>/dev/null || echo "No disponible")
    local last_commit=$(git log -1 --format="%H" 2>/dev/null | cut -c1-8 || echo "N/A")
    local commit_count=$(git rev-list --count HEAD 2>/dev/null || echo "0")
    
    echo -e "${CYAN}Repositorio:${NC} $remote_url"
    echo -e "${CYAN}Último commit:${NC} $last_commit"
    echo -e "${CYAN}Total commits:${NC} $commit_count"
}

# Función para manejar errores
handle_error() {
    local error_code=$1
    local error_message=$2
    
    log "error" "$error_message (código: $error_code)"
    
    case $error_code in
        1) log "info" "Tip: Verifica que estás en una rama válida" ;;
        128) log "info" "Tip: Verifica la conexión con el repositorio remoto" ;;
        130) log "info" "Tip: Operación cancelada por el usuario" ;;
        *) log "info" "Tip: Revisa el log de Git para más detalles" ;;
    esac
}

# Función de ayuda contextual
show_context_help() {
    local context=$1
    
    case $context in
        "test-server")
            echo -e "${CYAN}Ayuda:${NC} Este comando reinicia la rama trycode con tu código actual"
            echo -e "${CYAN}Uso:${NC} Perfecto para probar que tu código funciona en el servidor"
            echo -e "${CYAN}Nota:${NC} Trycode se sobrescribirá completamente"
            ;;
        "finish-feature")
            echo -e "${CYAN}Ayuda:${NC} Finaliza tu característica actual"
            echo -e "${CYAN}Opciones:${NC} Enviar a revisión o merge directo a develop"
            echo -e "${CYAN}Requisito:${NC} Todos los cambios deben estar commiteados"
            ;;
    esac
}

# Si se ejecuta directamente (para pruebas)
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    log "info" "Módulo de utilidades cargado correctamente"
    
    # Pruebas básicas si se ejecuta directamente
    if [[ "${1:-}" == "test" ]]; then
        log "info" "Ejecutando pruebas de utilidades..."
        
        echo -e "\n${CYAN}=== PRUEBAS DE UTILIDADES ===${NC}"
        
        # Test de funciones básicas
        local test_branch=$(get_current_branch)
        echo -e "${GREEN}✓${NC} Rama actual: $test_branch"
        
        local is_feature=$(is_feature_branch "$test_branch")
        echo -e "${GREEN}✓${NC} ¿Es feature? $is_feature"
        
        local has_changes=$(has_uncommitted_changes && echo "Sí" || echo "No")
        echo -e "${GREEN}✓${NC} ¿Cambios sin commit? $has_changes"
        
        local has_conf=$(has_conflicts && echo "Sí" || echo "No")
        echo -e "${GREEN}✓${NC} ¿Conflictos? $has_conf"
        
        log "success" "Pruebas de utilidades completadas"
    fi
fi