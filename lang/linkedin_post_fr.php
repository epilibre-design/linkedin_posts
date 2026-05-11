<?php
/**
 * Module de langue : linkedin_post (français)
 *
 * @package SPIP\LinkedinPost\Lang
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = [

	// Navigation
	'titre_menu_linkedin_posts'    => 'Posts LinkedIn',
	'titre_importer_linkedin_post' => 'Importer un post LinkedIn',
	'icone_importer_linkedin_post' => 'Importer un post LinkedIn',
	'icone_modifier_linkedin_post' => 'Modifier ce post LinkedIn',

	// Objet
	'titre_linkedin_post'  => 'Post LinkedIn',
	'titre_linkedin_posts' => 'Posts LinkedIn',
	'titre_par_defaut'     => 'Publication LinkedIn',

	'info_aucun_linkedin_post' => 'Aucun post LinkedIn',
	'info_1_linkedin_post'     => '1 post LinkedIn',
	'info_nb_linkedin_posts'   => '@nb@ posts LinkedIn',
	'info_pas_d_article'       => '—',

	// Champs
	'label_url'         => 'URL de la publication LinkedIn',
	'label_titre'       => 'Titre',
	'label_resume'      => 'Résumé',
	'label_texte'       => 'Texte',
	'label_auteur_post' => 'Auteur de la publication',
	'label_image_url'   => 'URL de l’image d’illustration',
	'label_image'       => 'Image d’illustration',
	'label_date_post'   => 'Date de publication',
	'label_rubrique'    => 'Rubrique de destination',
	'label_article'     => 'Article SPIP associé',
	'label_import_async' => 'Import en arrière-plan',

	// Explications
	'explication_url'         => 'Coller l’URL canonique d’un post (ex. https://www.linkedin.com/posts/...).',
	'explication_resume'      => 'Sert d’accroche dans la liste des articles.',
	'explication_import_async' => 'Le scraping est différé : décocher pour un import immédiat (peut être lent).',
	'explication_importer'    => 'Le contenu et l’image SEO de la publication seront récupérés et un article SPIP sera créé automatiquement dans la rubrique choisie.',

	// Boutons
	'bouton_importer'    => 'Importer',
	'bouton_rescraper'   => 'Re-scraper',
	'confirmer_rescraper' => 'Relancer le scraping ? Le contenu sera mis à jour.',

	// Messages
	'message_import_ok'    => 'Le post a été importé et un article a été créé.',
	'message_import_lance' => 'L’import a été lancé en arrière-plan.',

	// Erreurs
	'erreur_url_invalide'      => 'Cette URL n’est pas une publication LinkedIn valide.',
	'erreur_url_deja_importee' => 'Cette publication a déjà été importée.',
	'erreur_rubrique_invalide' => 'Veuillez choisir une rubrique de destination valide.',
	'erreur_scraping'          => 'Impossible de récupérer le contenu de cette publication.',
	'erreur_insertion'         => 'Erreur à l’enregistrement du post.',
	'erreur_creation_article'  => 'Erreur à la création de l’article.',

	// Article généré
	'source_linkedin' => 'Source : LinkedIn',
	'par'             => 'Par',

	// Statuts (textes pour statut_textes_instituer)
	'texte_statut_en_cours_redaction' => 'en attente d’import',
	'texte_statut_publie'             => 'importé',
	'texte_statut_poubelle'           => 'à la poubelle',

	// Configuration
	'titre_page_configurer' => 'Configurer les posts LinkedIn',
	'info_config_linkedin_post' => 'Ici vous pouvez configurer les options d’importation des posts LinkedIn.',
	'label_rubriques_autorisees' => 'Rubriques autorisées pour l’import',
	'titre_form_configurer' => 'Configuration',
	'explication_rubriques_autorisees' => 'Seules ces rubriques seront proposées dans le formulaire d’import. Laisser vide pour autoriser toutes les rubriques.',
];
