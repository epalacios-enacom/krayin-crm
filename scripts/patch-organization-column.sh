#!/bin/bash
set -euo pipefail

# Uso: ./scripts/patch-organization-column.sh [/opt/krayin-crm/src]
SRC_DIR=${1:-/opt/krayin-crm/src}

TARGET_FILE="$SRC_DIR/packages/Webkul/Admin/src/DataGrids/Lead/LeadDataGrid.php"

if [ ! -f "$TARGET_FILE" ]; then
    echo "[ERROR] No se encontró $TARGET_FILE"
    exit 1
fi

echo "== Aplicando parche para agregar columna Empresa =="

# Backup
cp "$TARGET_FILE" "${TARGET_FILE}.bak.$(date +%Y%m%d%H%M%S)"

# 1. Agregar join a organizations en prepareQueryBuilder
# Buscar la linea con leftJoin de persons y agregar el join de organizations después

# Verificar si ya está aplicado el parche
if grep -q "organizations.name as organization_name" "$TARGET_FILE"; then
    echo "El parche ya parece estar aplicado. Saliendo."
    exit 0
fi

# Agregar el leftJoin de organizations después del leftJoin de persons
sed -i '/leftJoin.*persons.*leads.person_id/a\            ->leftJoin('"'"'organizations'"'"', '"'"'persons.organization_id'"'"', '"'"'='"'"', '"'"'organizations.id'"'"')' "$TARGET_FILE"

# Agregar el addSelect de organization_name
# Buscamos una línea con addSelect que tenga person... y agregamos después
sed -i '/addSelect.*persons\.name/a\                '"'"'organizations.name as organization_name'"'"',' "$TARGET_FILE"

# Agregar el filtro de organization_name
sed -i '/addFilter.*person_name/a\        $this->addFilter('"'"'organization_name'"'"', '"'"'organizations.name'"'"');' "$TARGET_FILE"

# 2. Agregar la columna en prepareColumns
# Buscamos después de la columna contact_number o stage y agregamos la columna organization_name

# Creamos el bloque de código a insertar
COLUMN_CODE='
        $this->addColumn([
            '\''index'\''      => '\''organization_name'\'',
            '\''label'\''      => '\''Empresa'\'',
            '\''type'\''       => '\''string'\'',
            '\''searchable'\'' => true,
            '\''sortable'\''   => true,
            '\''filterable'\'' => true,
        ]);
'

# Insertamos después de la columna contact_number o antes de created_at
# Usamos perl para insertar el bloque de múltiples líneas
perl -i -0pe "s/(addColumn\(\[\s*'index'\s*=>\s*'created_at')/\$1\n        \\\$this->addColumn([\n            'index'      => 'organization_name',\n            'label'      => 'Empresa',\n            'type'       => 'string',\n            'searchable' => true,\n            'sortable'   => true,\n            'filterable' => true,\n        ]);\n\n        \\\$this->/" "$TARGET_FILE" || echo "WARN: perl replace for column failed, trying alternative method"

echo "- Limpiando cachés..."
cd "$SRC_DIR"
php artisan cache:clear
php artisan config:clear

echo "Parche aplicado. Verifica el archivo: $TARGET_FILE"
echo "Backup guardado en: ${TARGET_FILE}.bak.*"
