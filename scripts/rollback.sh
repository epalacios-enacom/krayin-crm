#!/bin/bash
set -u

echo "== ROLLING BACK CORE CHANGES =="
DIR="${1:-/opt/krayin-crm}"
cd "$DIR/src" || exit 1

echo "- Discarding changes in Webkul/Admin..."
# Restaruramos los archivos del núcleo que modificamos con el parche de emergencia
git checkout packages/Webkul/Admin/src/DataGrids/Lead/LeadDataGrid.php
git checkout packages/Webkul/Admin/src/Resources/views/leads/index.blade.php
git checkout packages/Webkul/Admin/src/Http/Controllers/Lead/LeadController.php

echo "- Clearing caches..."
php artisan view:clear
php artisan cache:clear

echo "Rollback finalizado. El CRM debería funcionar correctamente (sin la columna de empresa)."
