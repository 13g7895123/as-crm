#!/bin/bash
set -e

echo "=========================================="
echo "CRM Backend Container Starting..."
echo "=========================================="

# Ensure writable directories have correct permissions
echo "Setting up writable directories..."
mkdir -p writable/{cache,logs,session,uploads}
mkdir -p public/uploads

# Set permissions (fix potential permission issues from host)
chmod -R 777 writable public/uploads 2>/dev/null || {
    echo "Warning: Some permission settings failed, but continuing..."
}

echo "Permissions set successfully"

# Execute the main command (from docker-compose or CMD)
echo "Starting application: $@"
exec "$@"
