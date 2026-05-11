<?php

/**
 * Formulaire CVT d'édition d'un linkedin_post déjà importé.
 * S'appuie sur l'API formulaires_editer_objet de SPIP.
 *
 * @package SPIP\LinkedinPost\Formulaires
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');
include_spip('inc/editer');

/**
 * Charger : valeurs courantes du linkedin_post.
 *
 * @param int|string $id_linkedin_post 'new' ou id existant
 * @param string     $retour            URL de retour après traitement
 * @param int        $lier_id_article   id_article auquel relier le post (optionnel)
 * @param string     $config_fonc
 * @param array      $row
 * @param string     $hidden
 * @return array|bool
 */
function formulaires_editer_linkedin_post_charger_dist(
	$id_linkedin_post = 'new',
	$retour = '',
	$lier_id_article = 0,
	$config_fonc = '',
	$row = [],
	$hidden = ''
) {
	$valeurs = formulaires_editer_objet_charger(
		'linkedin_post',
		$id_linkedin_post,
		0,
		0,
		$retour,
		$config_fonc,
		$row,
		$hidden
	);

	if (intval($id_linkedin_post) && !autoriser('modifier', 'linkedin_post', intval($id_linkedin_post))) {
		$valeurs['editable'] = '';
	}

	return $valeurs;
}

/**
 * Vérifier : champs obligatoires.
 */
function formulaires_editer_linkedin_post_verifier_dist(
	$id_linkedin_post = 'new',
	$retour = '',
	$lier_id_article = 0,
	$config_fonc = '',
	$row = [],
	$hidden = ''
) {
	include_spip('inc/linkedin_post_scraper');

	$erreurs = formulaires_editer_objet_verifier('linkedin_post', $id_linkedin_post, ['url', 'titre']);

	if (!isset($erreurs['url']) && _request('url')) {
		if (!linkedin_post_url_valide(_request('url'))) {
			$erreurs['url'] = _T('linkedin_post:erreur_url_invalide');
		}
	}

	return $erreurs;
}

/**
 * Traiter : enregistre les modifications via objet_modifier.
 */
function formulaires_editer_linkedin_post_traiter_dist(
	$id_linkedin_post = 'new',
	$retour = '',
	$lier_id_article = 0,
	$config_fonc = '',
	$row = [],
	$hidden = ''
) {
	$res = formulaires_editer_objet_traiter(
		'linkedin_post',
		$id_linkedin_post,
		0,
		0,
		$retour,
		$config_fonc,
		$row,
		$hidden
	);

	return $res;
}
