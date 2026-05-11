<?php

/**
 * Génère un article SPIP à partir d'un post linkedin scrapé,
 * télécharge l'image d'illustration et la lie comme document.
 *
 * @package SPIP\LinkedinPost\Inc\Article
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * À partir d'un id_linkedin_post, crée l'article SPIP correspondant.
 * Idempotent : si un id_article est déjà associé, met à jour l'article existant.
 *
 * @param int $id_linkedin_post
 * @param int $id_rubrique Rubrique de destination (0 = celle configurée par défaut)
 * @return int|false id_article créé/mis à jour ou false
 */
function linkedin_post_creer_article($id_linkedin_post, $id_rubrique = 0) {

	include_spip('action/editer_objet');

	$id_linkedin_post = (int) $id_linkedin_post;
	$post = sql_fetsel('*', 'spip_linkedin_posts', 'id_linkedin_post = ' . $id_linkedin_post);
	if (!$post) {
		return false;
	}

	if (!$id_rubrique) {
		$id_rubrique = (int) (lire_config('linkedin_post_id_rubrique') ?: 1);
	}

	$date_redac = linkedin_post_date_redac_depuis_date_post($post['date_post']);

	$set = [
		'titre' => linkedin_post_titre_article_depuis_date_post($post['date_post']),
		'descriptif' => '',
		'texte' => linkedin_post_formater_texte_article($post),
		'id_rubrique' => $id_rubrique,
		'date_redac' => $date_redac,
		'url_site' => $post['url'],
		'nom_site' => 'LinkedIn',
	];

	$id_article = (int) $post['id_article'];

	if ($id_article && sql_countsel('spip_articles', 'id_article = ' . $id_article) > 0) {
		// Mise à jour d'un article existant
		objet_modifier('article', $id_article, $set);
	} else {
		// Création
		$id_article = objet_inserer('article', $id_rubrique, $set);
		if (!$id_article) {
			return false;
		}
		objet_modifier('article', $id_article, $set);
	}

	// Téléchargement et définition comme logo de l'article
	if ($post['image_url'] && !(int) $post['id_document']) {
		$ok_logo = linkedin_post_importer_image($post['image_url'], $id_article);
		// On marque id_document à 1 pour éviter un double téléchargement au rescraping.
		// Le vrai id_document SPIP est géré en interne par logo_modifier() dans spip_documents.
		$flag_logo = $ok_logo ? 1 : 0;
	} else {
		$flag_logo = (int) $post['id_document'];
	}

	// Mise à jour du lien linkedin_post → article
	sql_updateq(
		'spip_linkedin_posts',
		[
			'id_article' => $id_article,
			'id_document' => $flag_logo,
			'statut' => 'publie',
		],
		'id_linkedin_post = ' . $id_linkedin_post
	);

	return $id_article;
}

/**
 * Construit le corps SPIP de l'article à partir du post scrapé.
 *
 * @param array $post Ligne de spip_linkedin_posts
 * @return string Texte SPIP
 */
function linkedin_post_formater_texte_article($post) {
	$texte = trim($post['texte']);

	// Préserve les sauts de ligne LinkedIn
	$texte = preg_replace("/\r\n|\r/", "\n", $texte);
	$texte = preg_replace("/\n{3,}/", "\n\n", $texte);

	$texte .= "\n\n----\n\n";

	if ($post['auteur_post']) {
		$texte .= '-* ' . _T('linkedin_post:par') . ' ' . $post['auteur_post'] . "\n";
	}

	$texte .= '-* [' . _T('linkedin_post:source_linkedin') . '->' . $post['url'] . ']';

	return $texte;
}

/**
 * Formate le titre d'article à partir de la date de publication LinkedIn.
 *
 * @param string $date_post
 * @return string
 */
function linkedin_post_titre_article_depuis_date_post($date_post) {
	include_spip('inc/filtres_dates');

	$ts = strtotime((string) $date_post);
	if (!$ts) {
		$ts = time();
	}

	return 'Sur LinkedIn, le ' . affdate($ts);
}

/**
 * Convertit la date de publication LinkedIn en datetime SQL pour date_redac.
 *
 * @param string $date_post
 * @return string
 */
function linkedin_post_date_redac_depuis_date_post($date_post) {
	$ts = strtotime((string) $date_post);
	if (!$ts) {
		$ts = time();
	}

	return date('Y-m-d H:i:s', $ts);
}

/**
 * Télécharge une image distante et la définit comme logo de l'article.
 *
 * Utilise l'API officielle SPIP 4 : logo_modifier() (inc/logo).
 * Depuis SPIP 4.0, les logos sont des documents stockés dans IMG/logo/
 * et gérés en base via spip_documents avec mode 'logoon'/'logooff'.
 *
 * @param string $url_image URL de l'image à télécharger
 * @param int    $id_article
 * @return bool  true si le logo a été affecté, false en cas d'échec
 */
function linkedin_post_importer_image($url_image, $id_article) {
	include_spip('inc/distant');
	include_spip('inc/logo');

	if (!$url_image || !$id_article) {
		return false;
	}

	// Téléchargement de l'image
	$contenu = recuperer_url($url_image, [
		'taille_max' => 8 * 1024 * 1024,
		'follow' => true,
	]);
	if (empty($contenu['page'])) {
		spip_log("linkedin_post: échec téléchargement image $url_image", 'linkedin_post' . _LOG_ERREUR);
		return false;
	}

	// Détermination de l'extension depuis l'URL (fallback jpg)
	$path = parse_url($url_image, PHP_URL_PATH) ?: '';
	$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
	if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
		$ext = 'jpg';
	}

	// Écriture dans un fichier temporaire dans tmp/upload
	$tmp_file = _DIR_TMP . 'upload/linkedin_post_logo_' . md5($url_image) . '.' . $ext;
	if (!ecrire_fichier($tmp_file, $contenu['page'])) {
		spip_log("linkedin_post: impossible d'écrire le fichier temporaire $tmp_file", 'linkedin_post' . _LOG_ERREUR);
		return false;
	}

	// Affectation du logo via l'API SPIP 4
	// logo_modifier(type_objet, id_objet, etat, source)
	// etat 'on' = logo principal ; source = chemin absolu du fichier

	include_spip('action/editer_logo');
	$erreur_logo = logo_modifier('article', $id_article, 'on', $tmp_file);

	@unlink($tmp_file);

	if ($erreur_logo) {
		spip_log(
			"linkedin_post: logo_modifier a échoué pour article #$id_article ($erreur_logo)",
			'linkedin_post' . _LOG_ERREUR
		);
		return false;
	}

	return true;
}
