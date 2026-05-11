<?php

declare(strict_types=1);

$spipRoot = dirname(__DIR__) . '/vendor/spip/spip';

if (! defined('_SPIP_TEST_INC')) {
    define('_SPIP_TEST_INC', $spipRoot);
}
if (! defined('_SPIP_TEST_CHDIR')) {
    define('_SPIP_TEST_CHDIR', $spipRoot);
}

putenv('APP_ENV=test');
chdir($spipRoot);

if (is_file($spipRoot . '/vendor/autoload.php')) {
    require_once $spipRoot . '/vendor/autoload.php';
}
require_once $spipRoot . '/ecrire/inc_version.php';

include_spip('inc/plugin');
_chemin(dirname(__DIR__));
actualise_plugins_actifs();

// Ne pas charger tests/bootstrap.php ici : ce fichier contient des mocks unitaires
// (ex: sql_countsel) qui peuvent entrer en conflit avec les fonctions SPIP
// chargées plus tard pendant l'exécution des tests d'intégration.
