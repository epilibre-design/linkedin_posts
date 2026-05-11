<?php

/**
 * Formulaire CVT de configuration du plugin LinkedIn Post.
 *
 * @package SPIP\LinkedinPost\Formulaires
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Convertit une valeur picker (rubrique|ID) en tableau d'identifiants de rubriques.
 *
 * @param mixed $brut
 * @return int[]
 */
function linkedin_post_extraire_ids_rubriques_depuis_picker($brut) {
	if ($brut === null || $brut === '') {
		return [];
	}

	$items = is_array($brut) ? $brut : explode(',', (string) $brut);
	$ids = [];

	foreach ($items as $item) {
		if (is_numeric($item)) {
			$id = (int) $item;
		} elseif (preg_match('/^rubrique\|(\d+)$/', (string) $item, $matches)) {
			$id = (int) $matches[1];
		} else {
			continue;
		}

		if ($id > 0) {
			$ids[] = $id;
		}
	}

	$ids = array_values(array_unique($ids));
	sort($ids);

	return $ids;
}

/**
 * Charge les valeurs de configuration.
 *
 * @return array
 */
function formulaires_configurer_linkedin_post_charger_dist() {
	$rubriques = lire_config('linkedin_post_rubriques_autorisees');
	if (!$rubriques) {
		$rubrique_defaut = (int) lire_config('linkedin_post_id_rubrique');
		$rubriques = $rubrique_defaut > 0 ? [$rubrique_defaut] : [];
	}

	return [
		'rubriques_autorisees' => $rubriques,
	];
}

/**
 * Vérification du formulaire.
 *
 * @return array
 */
function formulaires_configurer_linkedin_post_verifier_dist() {
	return [];
}

/**
 * Enregistre la configuration.
 *
 * @return array
 */
function formulaires_configurer_linkedin_post_traiter_dist() {
	$ids = linkedin_post_extraire_ids_rubriques_depuis_picker(_request('rubriques_autorisees'));

	ecrire_config('linkedin_post_rubriques_autorisees', $ids);
	ecrire_config('linkedin_post_id_rubrique', ($ids[0] ?? 0));

	return [
		'message_ok' => _T('config_info_enregistree'),
	];
}