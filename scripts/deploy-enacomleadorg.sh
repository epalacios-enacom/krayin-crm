#!/bin/bash
set -euo pipefail

KRAYIN_ROOT=${1:-/var/www/html}
CONTAINER=${2:-krayin-app}
TMP_DIR=$(mktemp -d)

echo "=== DESPLIEGUE ENACOM ==="
echo "1. Copiando archivos..."

# Simular clonado o copia local si ya existe en server
if [ -d "./packages/Webkul/EnacomLeadOrg" ]; then
    mkdir -p "$KRAYIN_ROOT/packages/Webkul"
    rm -rf "$KRAYIN_ROOT/packages/Webkul/EnacomLeadOrg"
    cp -r "./packages/Webkul/EnacomLeadOrg" "$KRAYIN_ROOT/packages/Webkul/"
else
    echo "(!) No encuentro la carpeta packages/Webkul/EnacomLeadOrg en el directorio actual."
    echo "    Asegúrate de subir la carpeta 'packages' junto con este script."
    exit 1
fi

echo "2. Configurando Autoload PSR-4..."
docker exec "$CONTAINER" php -r "
\$j = json_decode(file_get_contents('$KRAYIN_ROOT/composer.json'), true);
\$j['autoload']['psr-4']['Webkul\\\\EnacomLeadOrg\\\\'] = 'packages/Webkul/EnacomLeadOrg/src';
file_put_contents('$KRAYIN_ROOT/composer.json', json_encode(\$j, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
"

echo "3. Configurando Provider (Prioridad Alta)..."
docker exec "$CONTAINER" php -r "
\$f = '$KRAYIN_ROOT/config/app.php';
\$c = file_get_contents(\$f);
\$p = 'Webkul\\\\EnacomLeadOrg\\\\Providers\\\\EnacomLeadOrgServiceProvider::class';

// 1. Limpiar cualquier referencia existente
\$c = preg_replace('/\\s*'.preg_quote(\$p, '/').',/', '', \$c);

// 2. Insertar al PRINCIPIO del array 'providers'
\$c = preg_replace('/(\047providers\047\\s*=>\\s*\\[)/', \"\$1\\n        \$p,\", \$c, 1);

file_put_contents(\$f, \$c);
"

echo "4. Regenerando Autoload y Cache..."
docker exec "$CONTAINER" bash -c "cd $KRAYIN_ROOT && composer dump-autoload && php artisan optimize:clear"

echo "=== VERIFICACION ==="
echo "Ruta de prueba (debe decir ENACOM PACKAGE IS ACTIVE):"
echo "  http://crm-enacom.onak.cl/admin/enacom-test"
echo ""
echo "Rutas 'admin/leads' actuales:"
docker exec "$CONTAINER" bash -c "cd $KRAYIN_ROOT && php artisan route:list | grep 'admin/leads' | head -n 1"
echo ""
echo "Si ves 'Webkul\EnacomLeadOrg...' arriba, la instalación fue EXITOSA."
