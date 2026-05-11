<?php
/**
 * Language module: linkedin_post (English)
 *
 * @package SPIP\LinkedinPost\Lang
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = [

	'titre_menu_linkedin_posts'    => 'LinkedIn posts',
	'titre_importer_linkedin_post' => 'Import a LinkedIn post',
	'icone_importer_linkedin_post' => 'Import a LinkedIn post',
	'icone_modifier_linkedin_post' => 'Edit this LinkedIn post',

	'titre_linkedin_post'  => 'LinkedIn post',
	'titre_linkedin_posts' => 'LinkedIn posts',
	'titre_par_defaut'     => 'LinkedIn publication',

	'info_aucun_linkedin_post' => 'No LinkedIn post',
	'info_1_linkedin_post'     => '1 LinkedIn post',
	'info_nb_linkedin_posts'   => '@nb@ LinkedIn posts',
	'info_pas_d_article'       => '—',

	'label_url'          => 'LinkedIn post URL',
	'label_titre'        => 'Title',
	'label_resume'       => 'Summary',
	'label_texte'        => 'Body',
	'label_auteur_post'  => 'Post author',
	'label_image_url'    => 'Illustration image URL',
	'label_image'        => 'Illustration image',
	'label_date_post'    => 'Publication date',
	'label_rubrique'     => 'Target section',
	'label_article'      => 'Linked SPIP article',
	'label_import_async' => 'Import in the background',

	'explication_url'          => 'Paste the canonical URL of a post (e.g. https://www.linkedin.com/posts/...).',
	'explication_resume'       => 'Used as the lead/teaser in article listings.',
	'explication_import_async' => 'The scraping is deferred: uncheck for an immediate (potentially slow) import.',
	'explication_importer'     => 'The SEO content and image of the publication will be fetched and a SPIP article will be created in the chosen section.',

	'bouton_importer'     => 'Import',
	'bouton_rescraper'    => 'Re-scrape',
	'confirmer_rescraper' => 'Re-run scraping? The content will be updated.',

	'message_import_ok'    => 'The post has been imported and an article has been created.',
	'message_import_lance' => 'Import has been queued in the background.',

	'erreur_url_invalide'      => 'This URL is not a valid LinkedIn publication.',
	'erreur_url_deja_importee' => 'This publication has already been imported.',
	'erreur_rubrique_invalide' => 'Please choose a valid target section.',
	'erreur_scraping'          => 'Failed to fetch the content of this publication.',
	'erreur_insertion'         => 'Error while saving the post.',
	'erreur_creation_article'  => 'Error while creating the article.',

	'source_linkedin' => 'Source: LinkedIn',
	'par'             => 'By',

	'texte_statut_en_cours_redaction' => 'pending import',
	'texte_statut_publie'             => 'imported',
	'texte_statut_poubelle'           => 'in the trash',

	// Configuration
	'titre_page_configurer' => 'Configure LinkedIn posts',
	'info_config_linkedin_post' => 'Here you can configure the import options for LinkedIn posts.',
	'label_rubriques_autorisees' => 'Allowed sections for import',
	'titre_form_configurer' => 'Configuration',
	'explication_rubriques_autorisees' => 'Only these sections will be available in the import form. Leave empty to allow all sections.',
];
