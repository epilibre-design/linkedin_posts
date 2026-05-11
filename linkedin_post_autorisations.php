<?php

/**
 * Autorisations du plugin Import LinkedIn.
 *
 * Lookup chain SPIP :
 *   autoriser_{type}_{faire}_dist  -- ex: autoriser_postlinkedin_modifier_dist
 *
 * Le type SPIP normalisé pour l'objet `linkedin_post` est `postlinkedin`
 * (objet_type() retire les underscores). On déclare donc nos fonctions
 * avec ce nom normalisé.
 *
 * @package SPIP\LinkedinPost\Autorisations
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonction d'appel pour le pipeline `autoriser`.
 * Sa seule présence active le chargement automatique des fonctions
 * d'autorisation présentes dans ce fichier.
 */
function linkedin_post_autoriser() {
}

// ------------------------------------------------------------------
// Autorisations sur l'objet linkedin_post
// ------------------------------------------------------------------

/**
 * Autorisation : voir un linkedin_post.
 * Toute personne ayant accès à l'espace privé peut voir.
 */
function autoriser_postlinkedin_voir_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('ecrire', '', 0, $qui);
}

/**
 * Autorisation : voir la liste des linkedin_posts (menu, ?exec=linkedin_posts).
 */
function autoriser_voir_liste_linkedin_posts_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('ecrire', '', 0, $qui);
}

/**
 * Autorisation : créer un linkedin_post.
 * Toujours interdit : la création passe obligatoirement par le formulaire d'import.
 */
function autoriser_postlinkedin_creer_dist($faire, $type, $id, $qui, $opt) {
	return false;
}

/**
 * Autorisation : modifier un linkedin_post.
 */
function autoriser_postlinkedin_modifier_dist($faire, $type, $id, $qui, $opt) {
	if ($qui['statut'] === '0minirezo') {
		return true;
	}
	if ($qui['statut'] !== '1comite') {
		return false;
	}
	// Un rédacteur ne modifie que ses propres imports
	// (lien spip_auteurs_liens si on a plus tard une notion d'auteur).
	// Ici, par défaut, on autorise les rédacteurs.
	return true;
}

/**
 * Autorisation : supprimer un linkedin_post.
 * Réservé aux administrateurs.
 */
function autoriser_postlinkedin_supprimer_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] === '0minirezo';
}

/**
 * Autorisation : (re)déclencher le scraping d'un linkedin_post déjà importé.
 */
function autoriser_postlinkedin_rescraper_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('modifier', 'linkedin_post', $id, $qui);
}

/**
 * Autorisation globale : configurer le plugin.
 */
function autoriser_postlinkedin_configurer_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] === '0minirezo';
}

/**
 * Autorisation : importer un post LinkedIn (formulaire d'import).
 * Réservé aux administrateurs et rédacteurs.
 */
function autoriser_postlinkedin_importer_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], ['0minirezo', '1comite']);
}
