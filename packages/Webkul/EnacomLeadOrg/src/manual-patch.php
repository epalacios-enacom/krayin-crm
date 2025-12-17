<?php
/**
 * INSTRUCCIONES DE PARCHE MANUAL PARA AGREGAR COLUMNA "EMPRESA" AL LEAD GRID
 * 
 * Ejecute este script desde el directorio src del servidor:
 * php /opt/krayin-crm/packages/Webkul/EnacomLeadOrg/src/manual-patch.php
 * 
 * O aplique manualmente las siguientes modificaciones:
 */

$srcDir = '/opt/krayin-crm/src';
$targetFile = $srcDir . '/packages/Webkul/Admin/src/DataGrids/Lead/LeadDataGrid.php';

if (!file_exists($targetFile)) {
    echo "[ERROR] No se encontró el archivo: $targetFile\n";
    exit(1);
}

$content = file_get_contents($targetFile);

// Verificar si ya está aplicado
if (strpos($content, 'organization_name') !== false) {
    echo "El parche ya está aplicado.\n";
    exit(0);
}

// Backup
copy($targetFile, $targetFile . '.bak.' . date('YmdHis'));

echo "=== INSTRUCCIONES MANUALES ===\n\n";

echo "1. En el método prepareQueryBuilder(), agregar después del leftJoin de 'persons':\n";
echo "   ->leftJoin('organizations', 'persons.organization_id', '=', 'organizations.id')\n\n";

echo "2. En el ->addSelect() del mismo método, agregar:\n";
echo "   'organizations.name as organization_name',\n\n";

echo "3. Después de los addFilter existentes, agregar:\n";
echo "   \$this->addFilter('organization_name', 'organizations.name');\n\n";

echo "4. En el método prepareColumns(), agregar una nueva columna (antes de created_at):\n";
echo <<<'EOD'
        $this->addColumn([
            'index'      => 'organization_name',
            'label'      => 'Empresa',
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);
EOD;

echo "\n\n5. Limpiar cachés:\n";
echo "   php artisan cache:clear && php artisan config:clear\n\n";

echo "=== FIN DE INSTRUCCIONES ===\n";
