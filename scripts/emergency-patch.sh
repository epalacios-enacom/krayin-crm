#!/bin/bash
set -euo pipefail

# Uso: ./scripts/emergency-patch.sh [/opt/krayin-crm]
ROOT_DIR=${1:-/opt/krayin-crm}
SRC_DIR="$ROOT_DIR/src"
PKG_SOURCE="$ROOT_DIR/packages/Webkul/EnacomLeadOrg/src"

echo "== ENACOM Emergency Patch (Direct Core Modification) =="
echo "ROOT_DIR: $ROOT_DIR"

if [ ! -d "$SRC_DIR/packages/Webkul/Admin" ]; then
  echo "[ERROR] No encuentro el paquete Webkul/Admin en $SRC_DIR. ¿Está instalada la app?"
  exit 1
fi

echo "- Actualizando repo raíz..."
cd "$ROOT_DIR"
git pull || true

echo "- Aplicando parches directo al Core..."

# 1. Reemplazar el DataGrid (Agrega la columna)
# Ubicación original: packages/Webkul/Admin/src/DataGrids/Lead/LeadDataGrid.php
# Nota: Krayin a veces cambia la estructura, verificamos:
TARGET_GRID="$SRC_DIR/packages/Webkul/Admin/src/DataGrids/Lead/LeadDataGrid.php"
if [ -f "$TARGET_GRID" ]; then
    echo "  -> Parcheando LeadDataGrid.php..."
    cp "$PKG_SOURCE/DataGrids/LeadOrgDataGrid.php" "$TARGET_GRID" 
    # Ajustamos el namespace en el archivo copiado para que coincida con el original
    sed -i 's/namespace Webkul\\EnacomLeadOrg\\DataGrids;/namespace Webkul\\Admin\\DataGrids\\Lead;/' "$TARGET_GRID"
    sed -i 's/class LeadOrgDataGrid/class LeadDataGrid/' "$TARGET_GRID"
else
    echo "  [WARN] No encontré LeadDataGrid.php en la ruta esperada."
fi

# 2. Reemplazar la Vista (Agrega el filtro y botones)
# Ubicación original: packages/Webkul/Admin/src/Resources/views/leads/index.blade.php
TARGET_VIEW="$SRC_DIR/packages/Webkul/Admin/src/Resources/views/leads/index.blade.php"
if [ -f "$TARGET_VIEW" ]; then
    echo "  -> Parcheando index.blade.php..."
    cp "$PKG_SOURCE/Resources/views/admin/leads/index.blade.php" "$TARGET_VIEW"
    # Ajustamos la ruta del grid en la vista para que apunte a la ruta original (ya que parchamos el grid original)
    sed -i "s/route('admin.leads.enacom_grid')/route('admin.leads.get')/g" "$TARGET_VIEW"
else
     echo "  [WARN] No encontré index.blade.php en la ruta esperada."
fi

# 3. Reemplazar el Controlador (Para manejar el export y el filtro extra)
# Ubicación original: packages/Webkul/Admin/src/Http/Controllers/Lead/LeadController.php
TARGET_CTRL="$SRC_DIR/packages/Webkul/Admin/src/Http/Controllers/Lead/LeadController.php"
if [ -f "$TARGET_CTRL" ]; then
    echo "  -> Parcheando LeadController.php..."
    cp "$PKG_SOURCE/Http/Controllers/Admin/LeadOrgController.php" "$TARGET_CTRL"
    # Ajustamos namespace y nombre de clase
    sed -i 's/namespace Webkul\\EnacomLeadOrg\\Http\\Controllers\\Admin;/namespace Webkul\\Admin\\Http\\Controllers\\Lead;/' "$TARGET_CTRL"
    sed -i 's/class LeadOrgController/class LeadController/' "$TARGET_CTRL"
    # Ajustamos referencias internas
    sed -i 's/use Webkul\\EnacomLeadOrg\\DataGrids\\LeadOrgDataGrid;/use Webkul\\Admin\\DataGrids\\Lead\\LeadDataGrid;/' "$TARGET_CTRL"
    sed -i 's/new LeadOrgDataGrid/new LeadDataGrid/g' "$TARGET_CTRL"
    sed -i "s/view('enacomleadorg::admin.leads.board'/view('admin::leads.board'/g" "$TARGET_CTRL"
    sed -i "s/view('enacomleadorg::admin.leads.index'/view('admin::leads.index'/g" "$TARGET_CTRL"
else
     echo "  [WARN] No encontré LeadController.php en la ruta esperada."
fi

echo "- Limpiando cachés..."
cd "$SRC_DIR"
php artisan view:clear
php artisan cache:clear

echo "Listo. Has modificado el núcleo directamente."
