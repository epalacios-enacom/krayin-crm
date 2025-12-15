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

// --- DIAGNÓSTICO ---
// Mostrar los alrededores de 'providers' para entender el estado actual
echo '  [DEBUG] Estado actual de config/app.php (sección providers):' . PHP_EOL;
if (preg_match('/([\"\047]providers[\"\047]\s*=>[\s\S]{0,300})/', \$content, \$m)) {
    echo '  --------------------------------------------------' . PHP_EOL;
    echo substr(\$m[1], 0, 300) . '...' . PHP_EOL;
    echo '  --------------------------------------------------' . PHP_EOL;
} else {
    echo '  [DEBUG] No se encontró la cadena providers.' . PHP_EOL;
}

// --- REPARACIÓN DE CORRUPCIÓN CONOCIDA (Doble Corchete) ---
// Busca: 'providers' => [ ... EnacomLeadOrgServiceProvider::class, [
// El error [ extra proviene de un fallo en el script anterior.
\$repairPattern = '/([\"\047]providers[\"\047]\s*=>\s*\[\s*)(.*' . preg_quote(\$provider, '/') . ',\s*)\[/s';
if (preg_match(\$repairPattern, \$content)) {
    echo '  - DETECTADO ERROR CRÍTICO (Doble Corchete). Reparando...' . PHP_EOL;
    \$content = preg_replace(\$repairPattern, \"\$1\$2\", \$content, 1);
    file_put_contents(\$file, \$content);
    echo '  - Archivo reparado y guardado.' . PHP_EOL;
    // Recargar contenido
    \$content = file_get_contents(\$file);
}

// --- REPARACIÓN 2: CORRUPCIÓN "ARRAY WRAP" (Conflict con Laravel 11 style) ---
// Detecta: 'providers' => [ Provider, ServiceProvider::defaultProviders()->merge
// Esto ocurre cuando el script intenta meter el provider en un array pero era una llamada a método.
\$wrapPattern = '/([\"\047]providers[\"\047]\s*=>\s*\[\s*' . preg_quote(\$provider, '/') . ',\s*)(ServiceProvider::defaultProviders)/';
if (preg_match(\$wrapPattern, \$content)) {
    echo '  - DETECTADO ERROR (Array Wrap). Reparando...' . PHP_EOL;
    // Restauramos a: 'providers' => ServiceProvider::defaultProviders
    \$content = preg_replace(\$wrapPattern, "'providers' => \$2", \$content);
    file_put_contents(\$file, \$content);
    echo '  - Archivo restaurado (Array Wrap).' . PHP_EOL;
    \$content = file_get_contents(\$file);
}

// --- VERIFICACIÓN E INSERCIÓN ---
if (strpos(\$content, \$provider) !== false) {
    echo '  - El provider ya existe en config/app.php' . PHP_EOL;
} else {
    // ESTRATEGIA 1: Laravel 11 / Krayin moderno (merge)
    // Busca: ServiceProvider::defaultProviders()->merge([
    \$mergePattern = '/(ServiceProvider::defaultProviders\(\)->merge\(\s*\[)/';
    if (preg_match(\$mergePattern, \$content)) {
         echo '  - Detectado estilo Laravel moderno (merge).' . PHP_EOL;
         \$content = preg_replace(\$mergePattern, "\$1\\n        \$provider,", \$content, 1);
         file_put_contents(\$file, \$content);
         echo '  - Provider insertado en merge([...]) exitosamente.' . PHP_EOL;
    } 
    // ESTRATEGIA 2: Array estándar
    // Buscamos 'providers' => [  O  'providers' => array(
    else {
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
