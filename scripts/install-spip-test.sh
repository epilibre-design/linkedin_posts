#!/usr/bin/env sh
set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
SPIP_ROOT="$ROOT_DIR/vendor/spip/spip"
SPIP_BIN="$ROOT_DIR/vendor/spip/spip-cli-custom/bin/spip"
DEPOT_PRINCIPAL="https://plugins.spip.net/depots/principal.xml"
SPIP_AUTO_DIR="$SPIP_ROOT/plugins/auto"
FALLBACK_AUTO_DIR="$ROOT_DIR/../auto"

is_spip_depot_registered() {
    depot_url="$1"

    depot_count=$(
        "$SPIP_BIN" php:eval "include_spip('base/abstract_sql'); echo (int) sql_countsel('spip_depots', 'xml_paquets=' . sql_quote('$depot_url'));" 2>/dev/null || true
    )

    [ "$depot_count" -gt 0 ] 2>/dev/null
}

is_plugin_active() {
    plugin_prefix="$1"

    "$SPIP_BIN" plugins:lister | grep -Eiq "^[[:space:]]*$plugin_prefix[[:space:]]"
}

ensure_spip_depot() {
    depot_url="$1"

    if is_spip_depot_registered "$depot_url"; then
        echo "Depot deja enregistre: $depot_url" >&2
        return 0
    fi

    if "$SPIP_BIN" plugins:svp:depoter "$depot_url"; then
        return 0
    fi

    if is_spip_depot_registered "$depot_url"; then
        echo "Depot deja enregistre: $depot_url" >&2
        return 0
    fi

    echo "Echec: impossible d'enregistrer le depot $depot_url" >&2
    return 1
}

copy_plugin_from_fallback_auto() {
    plugin_prefix="$1"
    fallback_plugin_dir="$FALLBACK_AUTO_DIR/$plugin_prefix"
    local_plugin_dir="$SPIP_AUTO_DIR/$plugin_prefix"

    if [ ! -d "$fallback_plugin_dir" ] || [ -d "$local_plugin_dir" ]; then
        return 0
    fi

    echo "Copie de secours de $plugin_prefix vers $local_plugin_dir" >&2
    mkdir -p "$local_plugin_dir"
    cp -R "$fallback_plugin_dir"/. "$local_plugin_dir/"
}

activate_or_install_plugin() {
    plugin_prefix="$1"

    if is_plugin_active "$plugin_prefix"; then
        return 0
    fi

    if "$SPIP_BIN" plugins:activer "$plugin_prefix" -y && is_plugin_active "$plugin_prefix"; then
        return 0
    fi

    echo "Activation de $plugin_prefix impossible, tentative de telechargement via SVP" >&2
    "$SPIP_BIN" plugins:svp:telecharger "$plugin_prefix" -y || true
    copy_plugin_from_fallback_auto "$plugin_prefix"

    if is_plugin_active "$plugin_prefix"; then
        return 0
    fi

    "$SPIP_BIN" plugins:activer "$plugin_prefix" -y || true
    is_plugin_active "$plugin_prefix"
}

if [ ! -x "$SPIP_BIN" ]; then
    echo "spip-cli introuvable: $SPIP_BIN" >&2
    echo "Installez d'abord les dependances: composer install" >&2
    exit 1
fi

mkdir -p "$ROOT_DIR/vendor/spip"

if [ ! -f "$SPIP_ROOT/ecrire/inc_version.php" ]; then
    echo "Telechargement de SPIP dans $SPIP_ROOT"
    "$SPIP_BIN" core:telecharger -d "$SPIP_ROOT" -b 4.4
fi

cd "$SPIP_ROOT"
echo "Preparation du SPIP local"
"$SPIP_BIN" core:preparer

if [ ! -f "$SPIP_ROOT/config/connect.php" ]; then
    echo "Installation de SPIP (sqlite3)"
    "$SPIP_BIN" core:installer \
      --db-server=sqlite3 \
      --db-host='' \
      --db-login='' \
      --db-pass='' \
      --db-database='spip_test' \
      --db-prefix=spip \
      --admin-nom='Admin Test' \
      --admin-login='admin' \
      --admin-email='admin@example.test' \
      --admin-pass='adminadmin' \
      --adresse-site='http://localhost'
else
    echo "SPIP deja installe (config/connect.php present)"
fi

echo "Installation du plugin linkedin_post dans SPIP local"
mkdir -p "$SPIP_ROOT/plugins"
ln -sfn "$ROOT_DIR" "$SPIP_ROOT/plugins/linkedin_post"

mkdir -p "$SPIP_AUTO_DIR"

echo "Enregistrement du depot principal SPIP"
ensure_spip_depot "$DEPOT_PRINCIPAL"

echo "Activation des dependances du plugin"
PLUGIN_DEPS=$(php -r '
$xml = simplexml_load_file($argv[1]);
if (! $xml) {
    fwrite(STDERR, "Impossible de lire paquet.xml\n");
    exit(1);
}
foreach ($xml->necessite as $necessite) {
    $name = (string) $necessite["nom"];
    if ($name !== "" && $name !== "spip") {
        echo $name, PHP_EOL;
    }
}
' "$ROOT_DIR/paquet.xml")

for plugin_dep in $PLUGIN_DEPS; do
    echo "Traitement dependance: $plugin_dep"
    if ! activate_or_install_plugin "$plugin_dep"; then
        echo "Echec: impossible d'installer/activer la dependance $plugin_dep via spip-cli." >&2
        exit 1
    fi
done

echo "Activation du plugin linkedin_post"
"$SPIP_BIN" plugins:activer linkedin_post -y

echo "Environnement d'integration pret."