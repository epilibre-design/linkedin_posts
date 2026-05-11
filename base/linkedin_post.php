<?php

/**
 * Déclaration de l'objet éditorial `linkedin_post`.
 *
 * Table : spip_linkedin_posts
 * Type  : linkedin_post
 * Surnoms boucle : (LINKEDIN_POSTS)
 *
 * @package SPIP\LinkedinPost\Base
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Déclaration de la table principale du plugin.
 *
 * @pipeline declarer_tables_objets_sql
 * @param array $tables
 * @return array
 */
function linkedin_post_declarer_tables_objets_sql($tables) {

	$tables['spip_linkedin_posts'] = [
		// --- Identité ---
		'type' => 'linkedin_post',
		'table_objet' => 'linkedin_posts',
		'principale' => 'oui',
		'page' => '',           // pas de page publique : on s'appuie sur l'article SPIP créé

		// --- Schéma SQL ---
		'field' => [
			'id_linkedin_post' => 'bigint(21) NOT NULL',
			'url' => "varchar(255) DEFAULT '' NOT NULL",
			'id_article' => "bigint(21) DEFAULT '0' NOT NULL",
			'titre' => "text DEFAULT '' NOT NULL",
			'resume' => "text DEFAULT '' NOT NULL",
			'texte' => "longtext DEFAULT '' NOT NULL",
			'auteur_post' => "varchar(255) DEFAULT '' NOT NULL",
			'image_url' => "varchar(255) DEFAULT '' NOT NULL",
			'id_document' => "bigint(21) DEFAULT '0' NOT NULL",
			'date_post' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			'date' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			'maj' => 'TIMESTAMP',
			'statut' => "varchar(10) DEFAULT 'prepa' NOT NULL",
		],

		'key' => [
			'PRIMARY KEY' => 'id_linkedin_post',
			'KEY url' => 'url',
			'KEY id_article' => 'id_article',
			'KEY statut' => 'statut',
		],

		// --- Champs éditables (utilisés par objet_modifier) ---
		'champs_editables' => ['url', 'titre', 'resume', 'texte', 'auteur_post', 'image_url'],

		// --- Accesseurs canoniques ---
		'titre' => "titre, '' AS lang",
		'date' => 'date',

		// --- Cycle de vie statut ---
		'statut_textes_instituer' => [
			'prepa' => 'texte_statut_en_cours_redaction',
			'publie' => 'texte_statut_publie',
			'poubelle' => 'texte_statut_poubelle',
		],

		'statut' => [
			[
				'champ' => 'statut',
				'publie' => 'publie',
				'previsu' => 'publie,prepa',
				'post_date' => 'date',
				'exception' => ['statut', 'tout'],
			],
		],

		// --- i18n espace privé ---
		'texte_retour' => 'icone_retour',
		'texte_objets' => 'linkedin_post:titre_linkedin_posts',
		'texte_objet' => 'linkedin_post:titre_linkedin_post',
		'texte_modifier' => 'linkedin_post:icone_modifier_linkedin_post',
		'texte_creer' => 'linkedin_post:icone_importer_linkedin_post',
		'info_aucun_objet' => 'linkedin_post:info_aucun_linkedin_post',
		'info_1_objet' => 'linkedin_post:info_1_linkedin_post',
		'info_nb_objets' => 'linkedin_post:info_nb_linkedin_posts',
		'icone_objet' => 'linkedin_post',

		// --- Recherche ---
		'rechercher_champs' => [
			'titre' => 8,
			'resume' => 5,
			'texte' => 3,
			'auteur_post' => 2,
			'url' => 1,
		],
	];

	return $tables;
}

/**
 * Déclare les interfaces de tables pour le compilateur de boucles SPIP.
 *
 * @pipeline declarer_tables_interfaces
 * @param array $interfaces
 * @return array
 */
function linkedin_post_declarer_tables_interfaces($interfaces) {
	$interfaces['table_des_tables']['linkedin_posts'] = 'linkedin_posts';

	return $interfaces;
}
