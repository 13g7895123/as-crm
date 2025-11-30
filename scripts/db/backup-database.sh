#!/bin/bash

###############################################################################
# Database Backup Script
#
# Creates timestamped database backups with compression
# Usage: ./backup-database.sh [environment]
#
# Arguments:
#   environment - Optional: development|staging|production (default: development)
#
# Features:
#   - Timestamped backup files
#   - Gzip compression
#   - Retention policy (keeps last N backups)
#   - Error handling and logging
#   - Email notifications (optional)
###############################################################################

set -e  # Exit on error

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKEND_DIR="$PROJECT_ROOT/backend"
BACKUP_DIR="$PROJECT_ROOT/backups"
LOG_FILE="$BACKUP_DIR/backup.log"
RETENTION_DAYS=30  # Keep backups for 30 days
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Environment (default to development)
ENVIRONMENT="${1:-development}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}" | tee -a "$LOG_FILE"
}

# Check if .env file exists
check_env_file() {
    local env_file="$BACKEND_DIR/.env"

    if [ ! -f "$env_file" ]; then
        error ".env file not found at $env_file"
        exit 1
    fi

    return 0
}

# Load database configuration from .env
load_db_config() {
    local env_file="$BACKEND_DIR/.env"

    # Read database configuration
    DB_HOSTNAME=$(grep "^database.default.hostname" "$env_file" | cut -d '=' -f 2 | tr -d ' ')
    DB_DATABASE=$(grep "^database.default.database" "$env_file" | cut -d '=' -f 2 | tr -d ' ')
    DB_USERNAME=$(grep "^database.default.username" "$env_file" | cut -d '=' -f 2 | tr -d ' ')
    DB_PASSWORD=$(grep "^database.default.password" "$env_file" | cut -d '=' -f 2 | tr -d ' ')
    DB_PORT=$(grep "^database.default.port" "$env_file" | cut -d '=' -f 2 | tr -d ' ')

    # Set defaults if not found
    DB_HOSTNAME="${DB_HOSTNAME:-localhost}"
    DB_PORT="${DB_PORT:-3306}"

    if [ -z "$DB_DATABASE" ]; then
        error "Database name not found in .env file"
        exit 1
    fi

    if [ -z "$DB_USERNAME" ]; then
        error "Database username not found in .env file"
        exit 1
    fi
}

# Create backup directory
create_backup_dir() {
    if [ ! -d "$BACKUP_DIR" ]; then
        log "Creating backup directory: $BACKUP_DIR"
        mkdir -p "$BACKUP_DIR"
    fi
}

# Perform database backup
backup_database() {
    local backup_file="${DB_DATABASE}_${ENVIRONMENT}_${TIMESTAMP}.sql"
    local backup_path="$BACKUP_DIR/$backup_file"
    local compressed_path="${backup_path}.gz"

    log "Starting database backup..."
    log "Database: $DB_DATABASE"
    log "Environment: $ENVIRONMENT"
    log "Backup file: $backup_file"

    # Check if mysqldump is available
    if ! command -v mysqldump &> /dev/null; then
        error "mysqldump command not found. Please install MySQL client tools."
        exit 1
    fi

    # Perform backup
    log "Running mysqldump..."

    if [ -z "$DB_PASSWORD" ]; then
        # No password
        mysqldump -h "$DB_HOSTNAME" \
                  -P "$DB_PORT" \
                  -u "$DB_USERNAME" \
                  --single-transaction \
                  --routines \
                  --triggers \
                  --events \
                  --add-drop-table \
                  "$DB_DATABASE" > "$backup_path" 2>> "$LOG_FILE"
    else
        # With password
        mysqldump -h "$DB_HOSTNAME" \
                  -P "$DB_PORT" \
                  -u "$DB_USERNAME" \
                  -p"$DB_PASSWORD" \
                  --single-transaction \
                  --routines \
                  --triggers \
                  --events \
                  --add-drop-table \
                  "$DB_DATABASE" > "$backup_path" 2>> "$LOG_FILE"
    fi

    if [ $? -eq 0 ]; then
        log "Backup completed successfully"

        # Compress backup
        log "Compressing backup..."
        gzip "$backup_path"

        if [ -f "$compressed_path" ]; then
            local file_size=$(du -h "$compressed_path" | cut -f1)
            log "Compressed backup created: $compressed_path ($file_size)"

            # Set proper permissions
            chmod 600 "$compressed_path"

            return 0
        else
            error "Failed to compress backup"
            return 1
        fi
    else
        error "Backup failed"
        return 1
    fi
}

# Clean old backups
cleanup_old_backups() {
    log "Cleaning up backups older than $RETENTION_DAYS days..."

    find "$BACKUP_DIR" -name "${DB_DATABASE}_*.sql.gz" -type f -mtime +$RETENTION_DAYS -delete

    local remaining=$(find "$BACKUP_DIR" -name "${DB_DATABASE}_*.sql.gz" -type f | wc -l)
    log "Remaining backups: $remaining"
}

# Display backup statistics
show_statistics() {
    log "=== Backup Statistics ==="

    local total_backups=$(find "$BACKUP_DIR" -name "${DB_DATABASE}_*.sql.gz" -type f | wc -l)
    local total_size=$(du -sh "$BACKUP_DIR" 2>/dev/null | cut -f1)

    log "Total backups: $total_backups"
    log "Total size: ${total_size:-0}"

    log "=== Recent Backups ==="
    find "$BACKUP_DIR" -name "${DB_DATABASE}_*.sql.gz" -type f -printf "%T@ %Tc %s %p\n" | sort -rn | head -5 | while read -r timestamp datetime size path; do
        local readable_size=$(echo "$size" | numfmt --to=iec 2>/dev/null || echo "$size bytes")
        log "  $(basename "$path") - $readable_size"
    done
}

# Send notification (optional)
send_notification() {
    local status="$1"
    local message="$2"

    # Email notification (configure SMTP settings in .env)
    # Uncomment and configure if you want email notifications

    # if [ -n "$NOTIFICATION_EMAIL" ]; then
    #     echo "$message" | mail -s "Database Backup $status - $ENVIRONMENT" "$NOTIFICATION_EMAIL"
    # fi

    return 0
}

# Main execution
main() {
    log "========================================="
    log "Database Backup Script"
    log "Environment: $ENVIRONMENT"
    log "========================================="

    # Check prerequisites
    check_env_file
    load_db_config
    create_backup_dir

    # Perform backup
    if backup_database; then
        log "✓ Backup operation completed successfully"
        cleanup_old_backups
        show_statistics
        send_notification "SUCCESS" "Database backup completed successfully"
        log "========================================="
        exit 0
    else
        error "✗ Backup operation failed"
        send_notification "FAILED" "Database backup failed"
        log "========================================="
        exit 1
    fi
}

# Run main function
main
