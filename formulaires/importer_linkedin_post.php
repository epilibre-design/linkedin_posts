<?php

/**
 * Formulaire CVT d'import d'une publication LinkedIn.
 *
 * Saisie : URL du post + rubrique de destination.
 * Le scraping est exécuté de façon synchrone au submit.
 *
 * @package SPIP\LinkedinPost\Formulaires
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/linkedin_post_scraper');

/**
 * Liste des rubriques autorisées depuis la configuration du plugin.
 *
 * @return int[]
 */
function linkedin_post_lire_rubriques_autorisees() {
	$valeur = lire_config('linkedin_post_rubriques_autorisees');
	if (!$valeur) {
		return [];
	}

	if (is_array($valeur)) {
		$ids = $valeur;
	} else {
		$ids = preg_split('/\s*,\s*/', (string) $valeur, -1, PREG_SPLIT_NO_EMPTY);
	}

	$ids = array_map('intval', $ids);
	$ids = array_values(array_filter($ids, static fn($id) => $id > 0));
	$ids = array_values(array_unique($ids));
	sort($ids);

	return $ids;
}

/**
 * Charger : valeurs initiales du formulaire.
 *
 * @param int $id_rubrique Rubrique pré-sélectionnée (optionnel)
 * @return array|false
 */
function formulaires_importer_linkedin_post_charger_dist($id_rubrique = 0) {

	if (!autoriser('importer', 'linkedin_post')) {
		return false;
	}

	$rubriques_autorisees = linkedin_post_lire_rubriques_autorisees();

	if (!$id_rubrique) {
		$id_rubrique = (int) lire_config('linkedin_post_id_rubrique');
	}

	if ($rubriques_autorisees) {
		if (!$id_rubrique || !in_array($id_rubrique, $rubriques_autorisees, true)) {
			$id_rubrique = (int) $rubriques_autorisees[0];
		}
	}

	return [
		'url' => '',
		'id_rubrique' => $id_rubrique,
		'rubriques_autorisees' => $rubriques_autorisees,
	];
}

/**
 * Vérifier : URL valide, non déjà importée, rubrique existante.
 *
 * @param int $id_rubrique
 * @return array Tableau d'erreurs (vide si OK)
 */
function formulaires_importer_linkedin_post_verifier_dist($id_rubrique = 0) {

	$erreurs = [];

	$url = trim((string) _request('url'));
	if (!$url) {
		$erreurs['url'] = _T('info_obligatoire');
	} elseif (!linkedin_post_url_valide($url)) {
		$erreurs['url'] = _T('linkedin_post:erreur_url_invalide');
	} else {
		$url_norm = linkedin_post_normaliser_url($url);
		if (sql_countsel('spip_linkedin_posts', 'url = ' . sql_quote($url_norm)) > 0) {
			$erreurs['url'] = _T('linkedin_post:erreur_url_deja_importee');
		}
	}

	$id_rub = (int) (_request('id_rubrique') ?: $id_rubrique);
	$rubriques_autorisees = linkedin_post_lire_rubriques_autorisees();
	if (
		!$id_rub
		|| ($rubriques_autorisees && !in_array($id_rub, $rubriques_autorisees, true))
		|| !sql_countsel('spip_rubriques', 'id_rubrique = ' . $id_rub)
	) {
		$erreurs['id_rubrique'] = _T('linkedin_post:erreur_rubrique_invalide');
	}

	return $erreurs;
}

/**
 * Traiter : scrape l'URL, insère le linkedin_post, crée l'article SPIP.
 * Tout se passe dans la même requête HTTP.
 *
 * @param int $id_rubrique
 * @return array
 */
function formulaires_importer_linkedin_post_traiter_dist($id_rubrique = 0) {

	include_spip('action/editer_objet');
	include_spip('inc/linkedin_post_article');

	$url = linkedin_post_normaliser_url((string) _request('url'));
	$id_rub = (int) (_request('id_rubrique') ?: $id_rubrique);

	// 1. Scraping
	$data = linkedin_post_scraper_url($url);
	if (!$data) {
		return ['message_erreur' => _T('linkedin_post:erreur_scraping')];
	}

	// 2. Insertion du linkedin_post avec toutes les données scrapées
	$id_linkedin_post = objet_inserer('linkedin_post', null, [
		'url' => $data['url'],
		'titre' => $data['titre'] ?: _T('linkedin_post:titre_par_defaut'),
		'texte' => $data['texte'],
		'auteur_post' => $data['auteur_post'],
		'image_url' => $data['image_url'],
		'date_post' => $data['date_post'],
		'date' => date('Y-m-d H:i:s'),
		'statut' => 'prepa',
	]);

	if (!$id_linkedin_post) {
		return ['message_erreur' => _T('linkedin_post:erreur_insertion')];
	}

	// 3. Création de l'article SPIP lié (+ téléchargement de l'image)
	$id_article = linkedin_post_creer_article($id_linkedin_post, $id_rub);
	if (!$id_article) {
		return ['message_erreur' => _T('linkedin_post:erreur_creation_article')];
	}

	return [
		'message_ok' => _T('linkedin_post:message_import_ok'),
		'redirect' => generer_url_ecrire('article', "id_article=$id_article"),
	];
}
