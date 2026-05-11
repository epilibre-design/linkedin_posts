<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = [
	'linkedin_post_description' => 'Permet d’importer une publication LinkedIn dans SPIP : un formulaire de l’espace privé prend en entrée l’URL d’un post, le plugin en extrait le contenu et l’image (Open Graph + JSON-LD), puis en génère automatiquement un article SPIP.',
	'linkedin_post_slogan'      => 'Importer un post LinkedIn et le transformer en article',
	'linkedin_post_nom'         => 'Import LinkedIn',
];
