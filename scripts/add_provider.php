<?php

if ($argc < 2) {
    echo "[ERROR] Missing root path argument.\n";
    exit(1);
}
$krayinRoot = $argv[1];
$configFile = $krayinRoot . '/config/app.php';
$providerClass = 'Webkul\\EnacomLeadOrg\\Providers\\EnacomLeadOrgServiceProvider::class';

echo "  -> Processing: $configFile\n";

if (!file_exists($configFile)) {
    echo "  [ERROR] The configuration file does not exist.\n";
    exit(1);
}

$content = file_get_contents($configFile);

// Corruption repair
// This regex uses double quotes to avoid conflicts with single quotes inside the pattern.
$corruptionPattern = "/'providers'\s*=>\s*\[\s*" . preg_quote($providerClass, '/') . ",\s*(ServiceProvider::defaultProviders\(\)->merge)/";
if (preg_match($corruptionPattern, $content)) {
    echo "  -> Detected corruption (Array Wrap). Repairing...\n";
    $content = preg_replace($corruptionPattern, "'providers' => \$1", $content);
    file_put_contents($configFile, $content);
    echo "  -> File repaired. Re-reading...\n";
    $content = file_get_contents($configFile);
}

// If provider already exists, move it to the top of providers for priority
if (strpos($content, $providerClass) !== false) {
    echo "  -> Provider already registered. Reordering to top for priority...\n";
    // Remove existing occurrence
    $content = str_replace($providerClass . ",", "", $content);

    // Insert right after providers array start (traditional) or at beginning of merge()
    $mergePattern = '/(ServiceProvider::defaultProviders\(\)->merge\(\s*\[)/';
    if (preg_match($mergePattern, $content)) {
        $newContent = preg_replace($mergePattern, "$1\n            $providerClass,", $content, 1);
        file_put_contents($configFile, $newContent);
        echo "  -> Provider moved to top inside merge() successfully.\n";
        exit(0);
    }

    $arrayPattern = '/([\'\"]providers[\'\"]\s*=>\s*\[)/';
    if (preg_match($arrayPattern, $content)) {
        $newContent = preg_replace($arrayPattern, "$1\n            $providerClass,", $content, 1);
        file_put_contents($configFile, $newContent);
        echo "  -> Provider moved to top inside providers array successfully.\n";
        exit(0);
    }

    echo "  [WARN] Could not determine providers style for reordering.\n";
    exit(0);
}

// Insertion Strategy 1: Modern style with ->merge([...])
$mergePattern = '/(ServiceProvider::defaultProviders\(\)->merge\(\s*\[)/';
if (preg_match($mergePattern, $content)) {
    echo "  -> Modern style (merge) detected. Inserting provider...\n";
    $newContent = preg_replace($mergePattern, "$1\n            $providerClass,", $content, 1);
    file_put_contents($configFile, $newContent);
    echo "  -> Provider inserted into merge() successfully.\n";
    exit(0);
}

// Insertion Strategy 2: Traditional providers array
// This regex correctly handles single or double quotes around 'providers'.
$arrayPattern = '/([\'"]providers[\'"]\s*=>\s*\[)/';
if (preg_match($arrayPattern, $content)) {
    echo "  -> Traditional array style detected. Inserting provider...\n";
    $newContent = preg_replace($arrayPattern, "$1\n            $providerClass,", $content, 1);
    file_put_contents($configFile, $newContent);
    echo "  -> Provider inserted into array successfully.\n";
    exit(0);
}

echo "  [ERROR] Could not find a place to insert the provider. Check your config/app.php.\n";
exit(1);
