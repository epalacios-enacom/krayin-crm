#!/bin/bash
set -euo pipefail

# Uso: ./scripts/sync-enacomleadorg.sh [/opt/krayin-crm]
ROOT_DIR=${1:-/opt/krayin-crm}
SRC_DIR="$ROOT_DIR/src"
PKG_NAME="Webkul/EnacomLeadOrg"

echo "== ENACOM LeadOrg Sync =="
echo "ROOT_DIR: $ROOT_DIR"
echo "SRC_DIR : $SRC_DIR"

if [ ! -d "$ROOT_DIR/packages/$PKG_NAME" ]; then
  echo "[ERROR] Paquete no existe en $ROOT_DIR/packages/$PKG_NAME"
  exit 1
fi

if [ ! -d "$SRC_DIR" ]; then
  echo "[ERROR] Directorio src no existe: $SRC_DIR"
  exit 1
fi


# Modo DEV: Si se pasa "dev" como segundo argumento, sube cambios primero
MODE=${2:-deploy}

if [ "$MODE" == "dev" ]; then
    echo "== MODO DEV: Subiendo cambios al repo =="
    cd "$ROOT_DIR"
    git add .
    git commit -m "wip: auto sync from script" || true
    git push origin HEAD
fi

echo "- Actualizando repo raíz"
cd "$ROOT_DIR"
git pull || true

echo "- Copiando paquete al proyecto en ejecución"
rm -rf "$SRC_DIR/packages/$PKG_NAME"
mkdir -p "$SRC_DIR/packages/Webkul"
cp -r "$ROOT_DIR/packages/$PKG_NAME" "$SRC_DIR/packages/$PKG_NAME"

echo "- Reordenando ServiceProvider en config/app.php"
php "$SRC_DIR/scripts/add_provider.php" "$SRC_DIR" || true

echo "- Limpiando cachés y optimizando"
cd "$SRC_DIR"
php artisan route:clear
php artisan view:clear
php artisan cache:clear
composer dump-autoload
php artisan optimize:clear

echo "- Verificación rápida"
echo "  Visita: /admin/override-check"
echo "  Esperado: LeadOrgController en index y get"
echo "Listo"

