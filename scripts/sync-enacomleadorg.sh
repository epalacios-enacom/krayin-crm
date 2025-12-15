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

echo "- Actualizando repo raíz"
cd "$ROOT_DIR"
git pull || true

echo "- Copiando paquete al proyecto en ejecución"
rm -rf "$SRC_DIR/packages/$PKG_NAME"
mkdir -p "$SRC_DIR/packages/Webkul"
cp -r "$ROOT_DIR/packages/$PKG_NAME" "$SRC_DIR/packages/$PKG_NAME"

echo "- Reordenando ServiceProvider en config/app.php"
php "$SRC_DIR/scripts/add_provider.php" "$SRC_DIR" || true

echo "- Reconstruyendo autoload y limpiando cachés"
cd "$SRC_DIR"
composer dump-autoload
php artisan optimize:clear

echo "- Verificación rápida"
echo "  Visita: /admin/override-check"
echo "  Esperado: LeadOrgController en index y get"
echo "Listo"

