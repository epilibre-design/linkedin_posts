<?php

/**
 * Utilisations de pipelines
 *
 * @package SPIP\LinkedinPost\Pipelines
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Copier le statut sur la table spip_linkedin_posts
 * à chaque changement d'un article.
 *
 * @pipeline post_edition
 *
 * @param array $flux
 *     Données du pipeline
 * @return array
 *     Données du pipeline
 */
function linkedin_post_post_edition($flux) {
	if (
		isset($flux['args']['objet'])
		and ($flux['args']['objet'] == 'article')
        and ($flux['args']['action'] == 'instituer')
		and isset($flux['data']['statut'])
	) {
		// Déterminer le statut du post LinkedIn en fonction du statut de l'article
		$statut_article = $flux['data']['statut'];
		$statut_post = match($statut_article) {
			'publie' => 'publie',
			'poubelle' => 'poubelle',
            'refuse' => 'poubelle',
			default => 'prepa'
		};

		sql_updateq('spip_linkedin_posts', ['statut' => $statut_post], 'id_article=' . $flux['args']['id_objet']);
	}

	return $flux;
}
