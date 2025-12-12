#!/bin/bash
set -euo pipefail

KRAYIN_ROOT=${1:-/var/www/html}
CONTAINER=${2:-krayin-app}
TMP_DIR=$(mktemp -d)

echo "Copiando paquete..."
git clone --depth 1 --branch feature/enacom-agregar-empresa-en-leads-grid https://github.com/epalacios-enacom/krayin-crm.git "$TMP_DIR"
mkdir -p "$KRAYIN_ROOT/packages/Webkul"
rm -rf "$KRAYIN_ROOT/packages/Webkul/EnacomLeadOrg"
cp -r "$TMP_DIR/packages/Webkul/EnacomLeadOrg" "$KRAYIN_ROOT/packages/Webkul/EnacomLeadOrg"
rm -rf "$TMP_DIR"

echo "Registrando autoload..."
cp "$KRAYIN_ROOT/composer.json" "$KRAYIN_ROOT/composer.json.bak" || true
php -r '
$p=$argv[1];
$j=json_decode(file_get_contents($p),true);
if(!isset($j["autoload"])){$j["autoload"]=[];}
if(!isset($j["autoload"]["psr-4"])){$j["autoload"]["psr-4"]=[];}
$j["autoload"]["psr-4"]["Webkul\\\\EnacomLeadOrg\\\\"]="packages/Webkul/EnacomLeadOrg/src";
if(!isset($j["autoload"]["classmap"])){$j["autoload"]["classmap"]=[];}
if(!in_array("packages/Webkul/EnacomLeadOrg/src",$j["autoload"]["classmap"])){$j["autoload"]["classmap"][]="packages/Webkul/EnacomLeadOrg/src";}
file_put_contents($p,json_encode($j,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
' "$KRAYIN_ROOT/composer.json"

echo "Insertando provider..."
APP_CFG="$KRAYIN_ROOT/config/app.php"
if ! grep -q "Webkul\\EnacomLeadOrg\\Providers\\EnacomLeadOrgServiceProvider::class" "$APP_CFG"; then
  awk 'BEGIN{i=0} /providers[[:space:]]*=>[[:space:]]*\[/ && !i {print; print "        Webkul\\EnacomLeadOrg\\Providers\\EnacomLeadOrgServiceProvider::class,"; i=1; next} {print}' "$APP_CFG" > "$APP_CFG.new" && mv "$APP_CFG.new" "$APP_CFG"
fi

echo "Construyendo autoload..."
if docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
  docker exec "$CONTAINER" bash -lc "cd $KRAYIN_ROOT && composer dump-autoload -o && php artisan optimize:clear"
else
  composer dump-autoload -d "$KRAYIN_ROOT" -o
  php "$KRAYIN_ROOT/artisan" optimize:clear
fi

echo "Rutas publicadas:"
if docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
  docker exec "$CONTAINER" bash -lc "cd $KRAYIN_ROOT && php artisan route:list | grep -E 'admin/leads|admin/organizations/search' || true"
else
  php "$KRAYIN_ROOT/artisan" route:list | grep -E 'admin/leads|admin/organizations/search' || true
fi

echo "Listo"
