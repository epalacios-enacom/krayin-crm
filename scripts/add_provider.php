<?php

$configFile = '/var/www/html/config/app.php';
$providerClass = 'Webkul\\EnacomLeadOrg\\Providers\\EnacomLeadOrgServiceProvider::class';

echo "  -> Procesando: $configFile\n";

if (!file_exists($configFile)) {
    echo "  [ERROR] El archivo de configuración no existe.\n";
    exit(1);
}

$content = file_get_contents($configFile);

// PRIMERO: Reparar corrupción conocida.
// 'providers' => [ Provider, ServiceProvider::defaultProviders()->merge...
$corruptionPattern = '/'providers'\s*=>\s*\[\s*' . preg_quote($providerClass, '/') . ',\s*(ServiceProvider::defaultProviders\(\)->merge)/';
if (preg_match($corruptionPattern, $content)) {
    echo "  -> Corrupción detectada (Array Wrap). Reparando...\n";
    $content = preg_replace($corruptionPattern, "'providers' => $1", $content);
    file_put_contents($configFile, $content);
    echo "  -> Archivo reparado. Releyendo...\n";
    $content = file_get_contents($configFile);
}

// SEGUNDO: Verificar si ya existe.
if (strpos($content, $providerClass) !== false) {
    echo "  -> El provider ya está registrado. No se necesita acción.\n";
    exit(0);
}

// TERCERO: Intentar insertar.
// Estrategia 1: Estilo moderno con ->merge([...])
$mergePattern = '/(ServiceProvider::defaultProviders\(\)->merge\(\s*\[)/';
if (preg_match($mergePattern, $content)) {
    echo "  -> Detectado estilo moderno (merge). Insertando provider...\n";
    $newContent = preg_replace($mergePattern, '$1' . "\n            $providerClass,", $content, 1);
    file_put_contents($configFile, $newContent);
    echo "  -> Provider insertado en merge() correctamente.\n";
    exit(0);
}

// Estrategia 2: Array de providers tradicional
$arrayPattern = '/(['"]providers['"]\s*=>\s*\[)/';
if (preg_match($arrayPattern, $content)) {
    echo "  -> Detectado estilo de array tradicional. Insertando provider...\n";
    $newContent = preg_replace($arrayPattern, '$1' . "\n            $providerClass,", $content, 1);
    file_put_contents($configFile, $newContent);
    echo "  -> Provider insertado en array correctamente.\n";
    exit(0);
}

echo "  [ERROR] No se pudo encontrar un lugar para insertar el provider. Revisa tu config/app.php.\n";
exit(1);
