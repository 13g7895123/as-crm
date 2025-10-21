#!/bin/bash

###############################################################################
# System Health Check Script
#
# Performs comprehensive health checks on the CRM RBAC system
# Usage: ./health-check.sh [options]
#
# Options:
#   --verbose     - Show detailed output
#   --json        - Output results in JSON format
#   --endpoint    - Check specific API endpoint health
#   --all         - Run all health checks (default)
#   --help        - Show this help message
#
# Exit Codes:
#   0 - All checks passed
#   1 - One or more checks failed
#   2 - Critical failure (system not operational)
#
# Features:
#   - Database connectivity check
#   - Backend API health check
#   - Frontend availability check
#   - File permissions check
#   - Dependencies check
#   - Disk space check
###############################################################################

set -e  # Exit on error (will be trapped)

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKEND_DIR="$PROJECT_ROOT/backend"
FRONTEND_DIR="$PROJECT_ROOT/frontend"
VERBOSE=false
JSON_OUTPUT=false
CHECK_ALL=true

# Health status
TOTAL_CHECKS=0
PASSED_CHECKS=0
FAILED_CHECKS=0
WARNINGS=0

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    if [ "$JSON_OUTPUT" = false ]; then
        echo -e "${GREEN}✓${NC} $1"
    fi
}

error() {
    if [ "$JSON_OUTPUT" = false ]; then
        echo -e "${RED}✗${NC} $1"
    fi
}

warning() {
    if [ "$JSON_OUTPUT" = false ]; then
        echo -e "${YELLOW}⚠${NC} $1"
    fi
}

info() {
    if [ "$JSON_OUTPUT" = false ] && [ "$VERBOSE" = true ]; then
        echo -e "${BLUE}ℹ${NC} $1"
    fi
}

# Show help
show_help() {
    cat << EOF
System Health Check

Usage: ./health-check.sh [options]

Options:
  --verbose        Show detailed output
  --json           Output results in JSON format
  --endpoint URL   Check specific API endpoint health
  --all            Run all health checks (default)
  --help           Show this help message

Examples:
  ./health-check.sh                           # Run all checks
  ./health-check.sh --verbose                 # Run with detailed output
  ./health-check.sh --json                    # Output as JSON
  ./health-check.sh --endpoint /api/v1/roles  # Check specific endpoint

EOF
}

# Parse arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --verbose)
                VERBOSE=true
                shift
                ;;
            --json)
                JSON_OUTPUT=true
                shift
                ;;
            --endpoint)
                ENDPOINT_URL="$2"
                shift 2
                ;;
            --all)
                CHECK_ALL=true
                shift
                ;;
            --help)
                show_help
                exit 0
                ;;
            *)
                error "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
}

# Record check result
record_check() {
    local check_name="$1"
    local status="$2"
    local message="$3"

    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))

    if [ "$status" = "pass" ]; then
        PASSED_CHECKS=$((PASSED_CHECKS + 1))
        log "$check_name: $message"
    elif [ "$status" = "warn" ]; then
        WARNINGS=$((WARNINGS + 1))
        warning "$check_name: $message"
    else
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
        error "$check_name: $message"
    fi

    if [ "$JSON_OUTPUT" = true ]; then
        # Store for JSON output later
        CHECKS_JSON+="{\"name\":\"$check_name\",\"status\":\"$status\",\"message\":\"$message\"},"
    fi
}

# Check PHP availability and version
check_php() {
    info "Checking PHP..."

    if ! command -v php &> /dev/null; then
        record_check "PHP" "fail" "PHP not found"
        return 1
    fi

    local php_version=$(php -v | head -n 1 | awk '{print $2}')
    local required_version="8.1"

    if [ "$(printf '%s\n' "$required_version" "$php_version" | sort -V | head -n1)" = "$required_version" ]; then
        record_check "PHP" "pass" "Version $php_version (>= $required_version required)"
        return 0
    else
        record_check "PHP" "fail" "Version $php_version (>= $required_version required)"
        return 1
    fi
}

# Check database connectivity
check_database() {
    info "Checking database connectivity..."

    local env_file="$BACKEND_DIR/.env"

    if [ ! -f "$env_file" ]; then
        record_check "Database" "fail" ".env file not found"
        return 1
    fi

    # Load database config
    DB_HOSTNAME=$(grep "^database.default.hostname" "$env_file" | cut -d '=' -f 2 | tr -d ' ')
    DB_DATABASE=$(grep "^database.default.database" "$env_file" | cut -d '=' -f 2 | tr -d ' ')
    DB_USERNAME=$(grep "^database.default.username" "$env_file" | cut -d '=' -f 2 | tr -d ' ')
    DB_PASSWORD=$(grep "^database.default.password" "$env_file" | cut -d '=' -f 2 | tr -d ' ')
    DB_PORT=$(grep "^database.default.port" "$env_file" | cut -d '=' -f 2 | tr -d ' ')

    DB_HOSTNAME="${DB_HOSTNAME:-localhost}"
    DB_PORT="${DB_PORT:-3306}"

    # Try to connect
    if [ -z "$DB_PASSWORD" ]; then
        mysql -h "$DB_HOSTNAME" -P "$DB_PORT" -u "$DB_USERNAME" -e "SELECT 1" "$DB_DATABASE" &>/dev/null
    else
        mysql -h "$DB_HOSTNAME" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" "$DB_DATABASE" &>/dev/null
    fi

    if [ $? -eq 0 ]; then
        record_check "Database" "pass" "Connected to $DB_DATABASE@$DB_HOSTNAME:$DB_PORT"
        return 0
    else
        record_check "Database" "fail" "Cannot connect to database"
        return 1
    fi
}

# Check backend API
check_backend_api() {
    info "Checking backend API..."

    # Check if backend directory exists
    if [ ! -d "$BACKEND_DIR" ]; then
        record_check "Backend API" "fail" "Backend directory not found"
        return 1
    fi

    # Check if .env exists
    if [ ! -f "$BACKEND_DIR/.env" ]; then
        record_check "Backend API" "warn" ".env file not found (using .env.example?)"
    fi

    # Try to reach API health endpoint (if running)
    local api_url="http://localhost:8080/api/v1/auth/login"

    if command -v curl &> /dev/null; then
        local response=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 "$api_url" 2>/dev/null || echo "000")

        if [ "$response" = "000" ]; then
            record_check "Backend API" "warn" "API not responding (server may not be running)"
        elif [ "$response" = "405" ] || [ "$response" = "401" ] || [ "$response" = "200" ]; then
            # 405 Method Not Allowed or 401 Unauthorized is actually good - means API is up
            record_check "Backend API" "pass" "API responding (HTTP $response)"
        else
            record_check "Backend API" "warn" "API responded with HTTP $response"
        fi
    else
        record_check "Backend API" "warn" "curl not available, cannot check API"
    fi

    return 0
}

# Check frontend
check_frontend() {
    info "Checking frontend..."

    if [ ! -d "$FRONTEND_DIR" ]; then
        record_check "Frontend" "fail" "Frontend directory not found"
        return 1
    fi

    # Check if package.json exists
    if [ ! -f "$FRONTEND_DIR/package.json" ]; then
        record_check "Frontend" "fail" "package.json not found"
        return 1
    fi

    # Check if node_modules exists
    if [ ! -d "$FRONTEND_DIR/node_modules" ]; then
        record_check "Frontend" "warn" "node_modules not found (run npm install)"
    else
        record_check "Frontend" "pass" "Dependencies installed"
    fi

    # Try to reach frontend (if running)
    local frontend_url="http://localhost:3000"

    if command -v curl &> /dev/null; then
        local response=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 "$frontend_url" 2>/dev/null || echo "000")

        if [ "$response" = "000" ]; then
            record_check "Frontend Server" "warn" "Server not responding (may not be running)"
        elif [ "$response" = "200" ]; then
            record_check "Frontend Server" "pass" "Server responding (HTTP $response)"
        else
            record_check "Frontend Server" "warn" "Server responded with HTTP $response"
        fi
    fi

    return 0
}

# Check file permissions
check_permissions() {
    info "Checking file permissions..."

    local writable_dirs=(
        "$BACKEND_DIR/writable"
        "$BACKEND_DIR/writable/cache"
        "$BACKEND_DIR/writable/logs"
        "$BACKEND_DIR/writable/session"
        "$BACKEND_DIR/writable/uploads"
    )

    local permission_issues=0

    for dir in "${writable_dirs[@]}"; do
        if [ ! -d "$dir" ]; then
            warning "Directory not found: $dir"
            permission_issues=$((permission_issues + 1))
            continue
        fi

        if [ ! -w "$dir" ]; then
            warning "Directory not writable: $dir"
            permission_issues=$((permission_issues + 1))
        fi
    done

    if [ $permission_issues -eq 0 ]; then
        record_check "Permissions" "pass" "All writable directories OK"
        return 0
    else
        record_check "Permissions" "warn" "$permission_issues permission issues found"
        return 1
    fi
}

# Check disk space
check_disk_space() {
    info "Checking disk space..."

    local disk_usage=$(df -h "$PROJECT_ROOT" | awk 'NR==2 {print $5}' | sed 's/%//')

    if [ "$disk_usage" -gt 90 ]; then
        record_check "Disk Space" "fail" "Critical: ${disk_usage}% used"
        return 1
    elif [ "$disk_usage" -gt 80 ]; then
        record_check "Disk Space" "warn" "Warning: ${disk_usage}% used"
        return 1
    else
        record_check "Disk Space" "pass" "${disk_usage}% used"
        return 0
    fi
}

# Check required extensions
check_php_extensions() {
    info "Checking PHP extensions..."

    local required_extensions=("mysqli" "json" "mbstring" "intl" "curl")
    local missing_extensions=()

    for ext in "${required_extensions[@]}"; do
        if ! php -m | grep -qi "^$ext$"; then
            missing_extensions+=("$ext")
        fi
    done

    if [ ${#missing_extensions[@]} -eq 0 ]; then
        record_check "PHP Extensions" "pass" "All required extensions installed"
        return 0
    else
        record_check "PHP Extensions" "fail" "Missing: ${missing_extensions[*]}"
        return 1
    fi
}

# Output JSON results
output_json() {
    local health_status="healthy"

    if [ $FAILED_CHECKS -gt 0 ]; then
        health_status="unhealthy"
    elif [ $WARNINGS -gt 0 ]; then
        health_status="degraded"
    fi

    cat << EOF
{
  "status": "$health_status",
  "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
  "checks": {
    "total": $TOTAL_CHECKS,
    "passed": $PASSED_CHECKS,
    "failed": $FAILED_CHECKS,
    "warnings": $WARNINGS
  },
  "details": [${CHECKS_JSON%,}]
}
EOF
}

# Main execution
main() {
    # Initialize
    CHECKS_JSON=""

    if [ "$JSON_OUTPUT" = false ]; then
        echo "========================================="
        echo "CRM RBAC System Health Check"
        echo "========================================="
        echo ""
    fi

    # Run checks
    check_php
    check_php_extensions
    check_database
    check_backend_api
    check_frontend
    check_permissions
    check_disk_space

    # Output results
    if [ "$JSON_OUTPUT" = true ]; then
        output_json
    else
        echo ""
        echo "========================================="
        echo "Health Check Summary"
        echo "========================================="
        echo "Total Checks: $TOTAL_CHECKS"
        echo -e "${GREEN}Passed: $PASSED_CHECKS${NC}"
        if [ $WARNINGS -gt 0 ]; then
            echo -e "${YELLOW}Warnings: $WARNINGS${NC}"
        fi
        if [ $FAILED_CHECKS -gt 0 ]; then
            echo -e "${RED}Failed: $FAILED_CHECKS${NC}"
        fi
        echo "========================================="
    fi

    # Determine exit code
    if [ $FAILED_CHECKS -gt 0 ]; then
        exit 1
    elif [ $WARNINGS -gt 0 ]; then
        exit 0  # Warnings don't cause failure
    else
        exit 0
    fi
}

# Parse arguments and run
parse_arguments "$@"
main
