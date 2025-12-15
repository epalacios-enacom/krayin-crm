#!/bin/bash
set -euo pipefail

# Configuración
KRAYIN_ROOT_CONTAINER="/var/www/html" # Ruta DENTRO del contenedor
CONTAINER=${1:-krayin-app} # Nombre del contenedor
HOST_PACKAGES_DIR="./packages/Webkul/EnacomLeadOrg" # Ruta local del paquete en el host (relativa a donde se corre el script)

echo "=== DESPLIEGUE ENACOM (V2.1 - Docker Native) ==="

# 1. Copiar archivos al contenedor
echo "1. Copiando archivos al contenedor..."
if [ -d "$HOST_PACKAGES_DIR" ]; then
    # Crear directorio destino en contenedor
    docker exec "$CONTAINER" mkdir -p "$KRAYIN_ROOT_CONTAINER/packages/Webkul"
    
    # Limpiar instalación previa en contenedor
    docker exec "$CONTAINER" rm -rf "$KRAYIN_ROOT_CONTAINER/packages/Webkul/EnacomLeadOrg"
    
    # Copiar desde host al contenedor
    docker cp "$HOST_PACKAGES_DIR" "$CONTAINER:$KRAYIN_ROOT_CONTAINER/packages/Webkul/"
else
    echo "(!) Error: No encuentro la carpeta local '$HOST_PACKAGES_DIR'."
    echo "    Ejecuta este script desde la raíz del proyecto (donde está la carpeta 'packages')."
    exit 1
fi

# 2. Configurar Autoload PSR-4 (PHP dentro del contenedor)
echo "2. Configurando Autoload PSR-4..."
docker exec "$CONTAINER" php -r "
\$file = '$KRAYIN_ROOT_CONTAINER/composer.json';
\$json = json_decode(file_get_contents(\$file), true);
\$json['autoload']['psr-4']['Webkul\\\\EnacomLeadOrg\\\\'] = 'packages/Webkul/EnacomLeadOrg/src';
file_put_contents(\$file, json_encode(\$json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
"

# 3. Configurar Provider con Prioridad (PHP dentro del contenedor)
echo "3. Configurando Provider (Prioridad Alta)..."
docker exec "$CONTAINER" php -r "
\$file = '$KRAYIN_ROOT_CONTAINER/config/app.php';
if (!file_exists(\$file)) {
    echo 'ERROR: No se encuentra config/app.php en ' . \$file . PHP_EOL;
    exit(1);
}
\$content = file_get_contents(\$file);
\$provider = 'Webkul\\\\EnacomLeadOrg\\\\Providers\\\\EnacomLeadOrgServiceProvider::class';

// 3.1. INTENTO DE REPARACIÓN (Si el script anterior rompió el archivo)
// Buscamos si existe el provider seguido de un corchete abierto extra o array(
// El error común del fallback anterior era generar: 'providers' => [ Provider, [
// Buscamos: Provider::class, [
\$brokenPattern = '/' . preg_quote(\$provider, '/') . ',\s*\[/';
if (preg_match(\$brokenPattern, \$content)) {
    echo '  - DETECTADO ERROR DE SINTAXIS (doble corchete). Reparando...' . PHP_EOL;
    \$content = preg_replace(\$brokenPattern, \"\$provider,\", \$content);
    file_put_contents(\$file, \$content);
    echo '  - Reparación aplicada.' . PHP_EOL;
    // Re-leer contenido
    \$content = file_get_contents(\$file);
}

if (strpos(\$content, \$provider) !== false) {
    echo '  - El provider ya existe en config/app.php' . PHP_EOL;
} else {
    // 3.2. INSERCIÓN SEGURA
    // Buscamos 'providers' => [  O  'providers' => array(
    \$pattern = '/([\"\047]providers[\"\047]\s*=>\s*(?:\[|array\s*\())/m';
    
    if (preg_match(\$pattern, \$content)) {
        \$content = preg_replace(\$pattern, \"\$1\\n        \$provider,\", \$content, 1);
        file_put_contents(\$file, \$content);
        echo '  - Provider insertado exitosamente.' . PHP_EOL;
    } else {
        echo '  - ERROR: No se pudo encontrar el array providers en config/app.php' . PHP_EOL;
        echo '  - NO se aplicaron cambios para evitar romper el archivo.' . PHP_EOL;
        echo '  - Contenido detectado alrededor de "providers":' . PHP_EOL;
        
        // Mostrar contexto para debug
        if (preg_match('/[\"\047]providers[\"\047]/', \$content, \$matches, PREG_OFFSET_CAPTURE)) {
            \$start = max(0, \$matches[0][1] - 50);
            echo substr(\$content, \$start, 200) . PHP_EOL;
        } else {
            echo '    (Palabra "providers" no encontrada)' . PHP_EOL;
        }
        
        echo '  - POR FAVOR AGREGA EL PROVIDER MANUALMENTE EN config/app.php:' . PHP_EOL;
        echo \"    \$provider\" . PHP_EOL;
        exit(1);
    }
}
"

# 4. Regenerar Autoload y Cache
echo "4. Regenerando Autoload y Cache..."
docker exec "$CONTAINER" bash -c "cd $KRAYIN_ROOT_CONTAINER && composer dump-autoload && php artisan optimize:clear"

# 5. Verificación
echo "=== VERIFICACION ==="
echo "Verificando si el provider está registrado:"
docker exec "$CONTAINER" grep "EnacomLeadOrgServiceProvider" "$KRAYIN_ROOT_CONTAINER/config/app.php" || echo "  ERROR: El provider NO aparece en config/app.php"

echo ""
echo "Ruta de prueba (debe decir ENACOM PACKAGE IS ACTIVE):"
echo "  http://crm-enacom.onak.cl/admin/enacom-test"
echo ""
echo "Controlador activo para 'admin/leads':"
docker exec "$CONTAINER" bash -c "cd $KRAYIN_ROOT_CONTAINER && php artisan route:list | grep 'admin/leads' | head -n 1"
echo ""
echo "Si ves 'Webkul\EnacomLeadOrg...' arriba, la instalación fue EXITOSA."
