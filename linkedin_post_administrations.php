<?php

/**
 * Installation, mise à jour, désinstallation du plugin Import LinkedIn.
 *
 * @package SPIP\Post_linkedin\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Installation et migrations.
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function linkedin_post_upgrade($nom_meta_base_version, $version_cible) {

	$maj = [];

	// Première installation : crée la table
	$maj['create'] = [
		['maj_tables', ['spip_linkedin_posts']],
		// défaut de configuration : id_rubrique de destination
		['ecrire_meta', 'linkedin_post_id_rubrique', '1'],
		['ecrire_meta', 'linkedin_post_rubriques_autorisees', '1'],
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Désinstallation : suppression de la table et des métas.
 *
 * @param string $nom_meta_base_version
 */
function linkedin_post_vider_tables($nom_meta_base_version) {

	sql_drop_table('spip_linkedin_posts');

	effacer_meta('linkedin_post_id_rubrique');
	effacer_meta('linkedin_post_rubriques_autorisees');
	effacer_meta($nom_meta_base_version);
}
