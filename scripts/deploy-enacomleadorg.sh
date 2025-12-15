#!/bin/bash
set -euo pipefail

# Configuración
KRAYIN_ROOT_CONTAINER="/var/www/html" # Ruta DENTRO del contenedor
CONTAINER=${1:-krayin-app} # Nombre del contenedor
HOST_PACKAGES_DIR="./packages/Webkul/EnacomLeadOrg" # Ruta local del paquete en el host (relativa a donde se corre el script)

echo "=== DESPLIEGUE ENACOM (V2.2 - Scripts PHP dedicados) ==="

# 1. Copiar archivos al contenedor
echo "1. Copiando archivos al contenedor..."
if [ -d "$HOST_PACKAGES_DIR" ]; then
    docker exec "$CONTAINER" mkdir -p "$KRAYIN_ROOT_CONTAINER/packages/Webkul"
    docker exec "$CONTAINER" rm -rf "$KRAYIN_ROOT_CONTAINER/packages/Webkul/EnacomLeadOrg"
    docker cp "$HOST_PACKAGES_DIR" "$CONTAINER:$KRAYIN_ROOT_CONTAINER/packages/Webkul/"
else
    echo "(!) Error: No encuentro la carpeta local '$HOST_PACKAGES_DIR'."
    exit 1
fi

# Crear directorio de scripts si no existe
docker exec "$CONTAINER" mkdir -p "$KRAYIN_ROOT_CONTAINER/scripts"

# 2. Configurar Autoload PSR-4 (usando script PHP dedicado)
echo "2. Configurando Autoload PSR-4 (usando script PHP dedicado)..."
docker cp ./scripts/configure_autoload.php "$CONTAINER:$KRAYIN_ROOT_CONTAINER/scripts/configure_autoload.php"
docker exec "$CONTAINER" php "$KRAYIN_ROOT_CONTAINER/scripts/configure_autoload.php" "$KRAYIN_ROOT_CONTAINER"

# 3. Configurar Provider (usando script PHP dedicado)
echo "3. Configurando Provider (usando script PHP dedicado)..."
docker cp ./scripts/add_provider.php "$CONTAINER:$KRAYIN_ROOT_CONTAINER/scripts/add_provider.php"
docker exec "$CONTAINER" php "$KRAYIN_ROOT_CONTAINER/scripts/add_provider.php" "$KRAYIN_ROOT_CONTAINER"

# 4. Regenerar Autoload y Cache
echo "4. Regenerando Autoload y Cache..."
docker exec "$CONTAINER" bash -c "cd $KRAYIN_ROOT_CONTAINER && composer dump-autoload && php artisan optimize:clear"

# 5. Verificación
echo "=== VERIFICACION ==="
echo "Verificando si el provider está registrado:"
docker exec "$CONTAINER" grep "EnacomLeadOrgServiceProvider" "$KRAYIN_ROOT_CONTAINER/config/app.php" || echo "  ERROR: El provider NO aparece en config/app.php"

echo ""
echo "Controlador activo para 'admin/leads':"
docker exec "$CONTAINER" bash -c "cd $KRAYIN_ROOT_CONTAINER && php artisan route:list | grep 'admin/leads' | head -n 1"
echo ""
echo "Si ves 'Webkul\EnacomLeadOrg...' arriba, la instalación fue EXITOSA."
