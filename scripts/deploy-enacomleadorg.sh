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

if (strpos(\$content, \$provider) !== false) {
    echo '  - El provider ya existe en config/app.php' . PHP_EOL;
} else {
    // Intentar buscar 'providers' => [ (soportando retorno de carro y espacios)
    // El error indica que no matchea el patrón simple. Vamos a hacerlo más flexible
    // Buscamos la palabra 'providers' seguida de => y [
    \$pattern = '/([\"\047]providers[\"\047]\s*=>\s*\[)/m';
    
    if (preg_match(\$pattern, \$content)) {
        \$content = preg_replace(\$pattern, \"\$1\\n        \$provider,\", \$content, 1);
        file_put_contents(\$file, \$content);
        echo '  - Provider insertado exitosamente.' . PHP_EOL;
    } else {
        echo '  - ERROR: No se pudo encontrar el array providers en config/app.php' . PHP_EOL;
        echo '  - Intentando estrategia alternativa (buscar providers = ServiceProvider::defaultProviders()...)' . PHP_EOL;
        
        // Estrategia alternativa para Laravel 11 o configs diferentes
        // Buscamos simplemente el inicio del return [ si es que providers está muy abajo, 
        // pero lo más seguro es que la regex anterior falló por espacios o saltos de línea extraños.
        
        // Vamos a intentar buscar una cadena más simple 'providers' =>
        \$simplePattern = '/(\047providers\047\s*=>)/';
        if (preg_match(\$simplePattern, \$content)) {
             echo '  - Encontrado providers sin corchete inmediato. Insertando forzosamente...' . PHP_EOL;
             // Asumimos que despues viene un [
             \$content = preg_replace(\$simplePattern, \"\$1 [\\n        \$provider,\", \$content, 1);
             // NOTA: Esto es arriesgado si ya había un [ , pero es un fallback.
             file_put_contents(\$file, \$content);
             echo '  - Provider insertado exitosamente (fallback).' . PHP_EOL;
        } else {
            echo '  - Fallo total al buscar providers.' . PHP_EOL;
        }
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
