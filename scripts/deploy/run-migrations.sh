#!/bin/bash

###############################################################################
# Database Migration Runner Script
#
# Safely runs database migrations with backup and rollback capability
# Usage: ./run-migrations.sh [options]
#
# Options:
#   --rollback    - Rollback last migration batch
#   --refresh     - Rollback all and re-run all migrations (DESTRUCTIVE)
#   --status      - Show migration status
#   --backup      - Create backup before running migrations (default: true)
#   --no-backup   - Skip backup before migrations
#   --help        - Show this help message
#
# Features:
#   - Automatic database backup before migrations
#   - Migration status checking
#   - Rollback capability
#   - Error handling
#   - Detailed logging
###############################################################################

set -e  # Exit on error

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKEND_DIR="$PROJECT_ROOT/backend"
LOG_DIR="$PROJECT_ROOT/logs"
LOG_FILE="$LOG_DIR/migrations.log"
CREATE_BACKUP=true
OPERATION="migrate"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    local message="$1"
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $message" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${BLUE}[INFO] $1${NC}" | tee -a "$LOG_FILE"
}

# Show help
show_help() {
    cat << EOF
Database Migration Runner

Usage: ./run-migrations.sh [options]

Options:
  --rollback       Rollback last migration batch
  --refresh        Rollback all and re-run all migrations (DESTRUCTIVE)
  --status         Show migration status
  --backup         Create backup before running migrations (default)
  --no-backup      Skip backup before migrations
  --help           Show this help message

Examples:
  ./run-migrations.sh                    # Run pending migrations with backup
  ./run-migrations.sh --status           # Show migration status
  ./run-migrations.sh --rollback         # Rollback last migration batch
  ./run-migrations.sh --no-backup        # Run migrations without backup

EOF
}

# Parse arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --rollback)
                OPERATION="rollback"
                shift
                ;;
            --refresh)
                OPERATION="refresh"
                shift
                ;;
            --status)
                OPERATION="status"
                shift
                ;;
            --backup)
                CREATE_BACKUP=true
                shift
                ;;
            --no-backup)
                CREATE_BACKUP=false
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

# Create log directory
create_log_dir() {
    if [ ! -d "$LOG_DIR" ]; then
        log "Creating log directory: $LOG_DIR"
        mkdir -p "$LOG_DIR"
    fi
}

# Check if CodeIgniter is available
check_codeigniter() {
    if [ ! -f "$BACKEND_DIR/spark" ]; then
        error "CodeIgniter spark file not found at $BACKEND_DIR/spark"
        exit 1
    fi

    if [ ! -x "$BACKEND_DIR/spark" ]; then
        log "Making spark executable..."
        chmod +x "$BACKEND_DIR/spark"
    fi

    return 0
}

# Check if PHP is available
check_php() {
    if ! command -v php &> /dev/null; then
        error "PHP command not found. Please install PHP."
        exit 1
    fi

    local php_version=$(php -v | head -n 1)
    log "PHP version: $php_version"

    return 0
}

# Create database backup before migrations
create_backup() {
    if [ "$CREATE_BACKUP" = false ]; then
        warning "Skipping database backup (--no-backup flag used)"
        return 0
    fi

    log "Creating database backup before migrations..."

    local backup_script="$SCRIPT_DIR/backup-database.sh"

    if [ ! -f "$backup_script" ]; then
        warning "Backup script not found at $backup_script"
        warning "Skipping backup..."
        return 0
    fi

    if bash "$backup_script"; then
        log "✓ Database backup completed"
        return 0
    else
        error "✗ Database backup failed"
        error "Aborting migrations for safety"
        exit 1
    fi
}

# Show migration status
show_migration_status() {
    log "Checking migration status..."

    cd "$BACKEND_DIR"
    php spark migrate:status

    return 0
}

# Run migrations
run_migrations() {
    log "Running database migrations..."

    cd "$BACKEND_DIR"

    if php spark migrate 2>&1 | tee -a "$LOG_FILE"; then
        log "✓ Migrations completed successfully"
        return 0
    else
        error "✗ Migrations failed"
        return 1
    fi
}

# Rollback migrations
rollback_migrations() {
    log "Rolling back last migration batch..."

    cd "$BACKEND_DIR"

    # Ask for confirmation
    read -p "Are you sure you want to rollback the last migration batch? (yes/no): " confirm
    if [ "$confirm" != "yes" ]; then
        warning "Rollback cancelled"
        exit 0
    fi

    if php spark migrate:rollback 2>&1 | tee -a "$LOG_FILE"; then
        log "✓ Rollback completed successfully"
        return 0
    else
        error "✗ Rollback failed"
        return 1
    fi
}

# Refresh migrations (DESTRUCTIVE)
refresh_migrations() {
    warning "========================================"
    warning "WARNING: DESTRUCTIVE OPERATION"
    warning "This will rollback ALL migrations and"
    warning "re-run them from scratch."
    warning "ALL DATA WILL BE LOST!"
    warning "========================================"

    read -p "Type 'CONFIRM' to proceed: " confirm
    if [ "$confirm" != "CONFIRM" ]; then
        warning "Refresh cancelled"
        exit 0
    fi

    log "Refreshing all migrations..."

    cd "$BACKEND_DIR"

    # Rollback all
    log "Rolling back all migrations..."
    if php spark migrate:rollback -all 2>&1 | tee -a "$LOG_FILE"; then
        log "✓ All migrations rolled back"
    else
        error "✗ Rollback failed"
        return 1
    fi

    # Re-run all migrations
    log "Re-running all migrations..."
    if php spark migrate 2>&1 | tee -a "$LOG_FILE"; then
        log "✓ Migrations completed successfully"
        return 0
    else
        error "✗ Migrations failed"
        return 1
    fi
}

# Check for pending migrations
check_pending_migrations() {
    cd "$BACKEND_DIR"

    local status_output=$(php spark migrate:status 2>&1)

    if echo "$status_output" | grep -q "No migrations found"; then
        info "No migrations to run"
        return 1
    fi

    if echo "$status_output" | grep -q "All migrations have been run"; then
        info "All migrations are up to date"
        return 1
    fi

    return 0
}

# Main execution
main() {
    log "========================================="
    log "Database Migration Runner"
    log "Operation: $OPERATION"
    log "========================================="

    # Check prerequisites
    create_log_dir
    check_php
    check_codeigniter

    # Perform operation
    case $OPERATION in
        status)
            show_migration_status
            ;;

        migrate)
            # Check if there are pending migrations
            if ! check_pending_migrations; then
                log "========================================="
                exit 0
            fi

            # Create backup
            create_backup

            # Run migrations
            if run_migrations; then
                log "✓ Migration operation completed successfully"
                show_migration_status
                log "========================================="
                exit 0
            else
                error "✗ Migration operation failed"
                log "========================================="
                exit 1
            fi
            ;;

        rollback)
            # Create backup before rollback
            create_backup

            # Rollback migrations
            if rollback_migrations; then
                log "✓ Rollback operation completed successfully"
                show_migration_status
                log "========================================="
                exit 0
            else
                error "✗ Rollback operation failed"
                log "========================================="
                exit 1
            fi
            ;;

        refresh)
            # Create backup before refresh
            create_backup

            # Refresh migrations
            if refresh_migrations; then
                log "✓ Refresh operation completed successfully"
                show_migration_status
                log "========================================="
                exit 0
            else
                error "✗ Refresh operation failed"
                log "========================================="
                exit 1
            fi
            ;;

        *)
            error "Unknown operation: $OPERATION"
            exit 1
            ;;
    esac
}

# Parse arguments and run
parse_arguments "$@"
main
