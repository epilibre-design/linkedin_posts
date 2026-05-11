<?php

/**
 * Action sécurisée : relance le scraping d'un linkedin_post existant.
 *
 * URL : ?action=rescraper_linkedin_post&arg=ID&hash=...
 *
 * @package SPIP\LinkedinPost\Action
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_rescraper_linkedin_post_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$id_linkedin_post = (int) $arg;
	if (!$id_linkedin_post) {
		spip_log('rescraper_linkedin_post: arg invalide', 'linkedin_post' . _LOG_ERREUR);
		return;
	}

	include_spip('inc/autoriser');
	if (!autoriser('rescraper', 'linkedin_post', $id_linkedin_post)) {
		spip_log("rescraper_linkedin_post: action refusée pour #$id_linkedin_post", 'linkedin_post' . _LOG_ERREUR);
		return;
	}

	include_spip('inc/linkedin_post_scraper');
	include_spip('inc/linkedin_post_article');
	include_spip('action/editer_objet');

	$post = sql_fetsel('*', 'spip_linkedin_posts', 'id_linkedin_post = ' . $id_linkedin_post);
	if (!$post) {
		return;
	}

	// Scraping synchrone
	$data = linkedin_post_scraper_url($post['url']);
	if ($data) {
		objet_modifier('linkedin_post', $id_linkedin_post, [
			'titre' => $data['titre'],
			'texte' => $data['texte'],
			'auteur_post' => $data['auteur_post'],
			'image_url' => $data['image_url'],
		]);
		sql_updateq(
			'spip_linkedin_posts',
			['date_post' => $data['date_post']],
			'id_linkedin_post = ' . $id_linkedin_post
		);

		// Mise à jour de l'article lié si déjà existant
		if ($post['id_article']) {
			linkedin_post_creer_article($id_linkedin_post);
		}
	} else {
		spip_log("rescraper_linkedin_post: scraping échoué pour #$id_linkedin_post", 'linkedin_post' . _LOG_ERREUR);
	}

	include_spip('inc/headers');
	$redirect = _request('redirect');
	if ($redirect) {
		redirige_par_entete($redirect);
	}
}
