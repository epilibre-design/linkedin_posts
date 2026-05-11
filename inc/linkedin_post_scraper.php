<?php

/**
 * Scraping d'une publication LinkedIn.
 *
 * On exploite les métadonnées SEO servies par LinkedIn aux bots :
 *   - Open Graph : og:title, og:description, og:image
 *   - JSON-LD    : @type SocialMediaPosting / Article (articleBody, author, datePublished)
 *
 * @package SPIP\LinkedinPost\Inc\Scraper
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Vérifie qu'une URL pointe bien sur une publication LinkedIn supportée.
 *
 * @param string $url
 * @return bool
 */
function linkedin_post_url_valide($url) {
	if (!is_string($url) || $url === '') {
		return false;
	}
	return (bool) preg_match('#^https?://(?:[a-z]{2,3}\.)?linkedin\.com/(?:posts|feed/update|pulse)/[^\s]+$#i', $url);
}

/**
 * Normalise une URL LinkedIn (retire fragments et paramètres de tracking).
 *
 * @param string $url
 * @return string
 */
function linkedin_post_normaliser_url($url) {
	$url = trim($url);
	// retire le fragment
	if (($pos = strpos($url, '#')) !== false) {
		$url = substr($url, 0, $pos);
	}
	// retire les paramètres utm_*, trk, etc.
	$parts = parse_url($url);
	if (!empty($parts['query'])) {
		parse_str($parts['query'], $q);
		foreach ($q as $k => $v) {
			if (preg_match('#^(utm_|trk|originalSubdomain)#i', $k)) {
				unset($q[$k]);
			}
		}
		$parts['query'] = http_build_query($q);
	}
	$url = (isset($parts['scheme']) ? $parts['scheme'] . '://' : 'https://');
	$url .= $parts['host'] ?? '';
	$url .= $parts['path'] ?? '';
	if (!empty($parts['query'])) {
		$url .= '?' . $parts['query'];
	}
	return $url;
}

/**
 * Récupère la page distante, en se présentant comme un bot SEO
 * (LinkedIn renvoie alors les métadonnées Open Graph).
 *
 * @param string $url
 * @return string|false HTML de la page ou false en cas d'échec.
 */
function linkedin_post_recuperer_html($url) {
	include_spip('inc/distant');

	// User-Agent type bot SEO : LinkedIn sert volontiers les og:* aux bots Facebook/Twitter.
	$options = [
		'transcoder' => true,
		'follow' => true,
		'taille_max' => 2 * 1024 * 1024,
		'datas' => '',
		'methode' => 'GET',
		'headers' => [
			'User-Agent' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
			'Accept-Language' => 'fr,en;q=0.8',
		],
	];

	$res = recuperer_url($url, $options);
	if (empty($res['page'])) {
		spip_log("linkedin_post: échec fetch $url (status={$res['status']})", 'linkedin_post' . _LOG_ERREUR);
		return false;
	}

	return $res['page'];
}

/**
 * Scrape une URL LinkedIn et retourne ses métadonnées extraites.
 *
 * @param string $url URL canonique du post LinkedIn.
 * @return array|false Tableau associatif ou false en cas d'échec.
 *                     Clés : url, titre, resume, texte, image_url, auteur_post, date_post.
 */
function linkedin_post_scraper_url($url) {

	$url = linkedin_post_normaliser_url($url);

	if (!linkedin_post_url_valide($url)) {
		spip_log("linkedin_post: URL invalide $url", 'linkedin_post' . _LOG_ERREUR);
		return false;
	}

	$html = linkedin_post_recuperer_html($url);
	if (!$html) {
		return false;
	}

	$jsonld = linkedin_post_extraire_jsonld($html);

	$titre = linkedin_post_extraire_meta($html, 'og:title');
	$resume = linkedin_post_extraire_meta($html, 'og:description');
	$image = linkedin_post_extraire_meta($html, 'og:image');

	// Texte intégral : on tente JSON-LD en priorité (articleBody est plus complet
	// que og:description souvent tronqué à ~250 caractères).
	$texte = '';
	if (!empty($jsonld['articleBody'])) {
		$texte = trim($jsonld['articleBody']);
	} elseif (!empty($jsonld['text'])) {
		$texte = trim($jsonld['text']);
	} elseif (!empty($jsonld['description'])) {
		$texte = trim($jsonld['description']);
	} else {
		$texte = $resume;
	}

	$auteur = '';
	if (!empty($jsonld['author']['name'])) {
		$auteur = $jsonld['author']['name'];
	} elseif (!empty($jsonld['author'][0]['name'])) {
		$auteur = $jsonld['author'][0]['name'];
	}

	$date_post = date('Y-m-d H:i:s');
	if (!empty($jsonld['datePublished'])) {
		$ts = strtotime($jsonld['datePublished']);
		if ($ts) {
			$date_post = date('Y-m-d H:i:s', $ts);
		}
	}

	return [
		'url' => $url,
		'titre' => $titre,
		'resume' => $resume,
		'texte' => $texte,
		'image_url' => $image,
		'auteur_post' => $auteur,
		'date_post' => $date_post,
	];
}

/**
 * Extrait une balise <meta property|name="..." content="..." />.
 *
 * @param string $html
 * @param string $property
 * @return string
 */
function linkedin_post_extraire_meta($html, $property) {
	$prop = preg_quote($property, '#');
	// property="og:..." ou name="twitter:..."
	if (preg_match(
		'#<meta\s+[^>]*(?:property|name)\s*=\s*["\']' . $prop . '["\'][^>]*content\s*=\s*["\'](.*?)["\']#is',
		$html,
		$m
	)) {
		return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
	}
	// inversion content puis property
	if (preg_match(
		'#<meta\s+[^>]*content\s*=\s*["\'](.*?)["\'][^>]*(?:property|name)\s*=\s*["\']' . $prop . '["\']#is',
		$html,
		$m
	)) {
		return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
	}
	return '';
}

/**
 * Extrait et fusionne les blocs <script type="application/ld+json">.
 * Retourne le premier qui correspond à un type SocialMediaPosting/Article/NewsArticle.
 *
 * @param string $html
 * @return array Tableau JSON-LD décodé, ou tableau vide.
 */
function linkedin_post_extraire_jsonld($html) {
	if (!preg_match_all(
		'#<script[^>]+type\s*=\s*["\']application/ld\+json["\'][^>]*>(.*?)</script>#is',
		$html,
		$matches
	)) {
		return [];
	}

	$prefere = ['SocialMediaPosting', 'NewsArticle', 'Article', 'BlogPosting'];

	foreach ($matches[1] as $json) {
		$data = json_decode(trim($json), true);
		if (!is_array($data)) {
			continue;
		}
		// JSON-LD peut être un tableau de blocs ou un graphe @graph
		$candidats = [];
		if (isset($data['@graph']) && is_array($data['@graph'])) {
			$candidats = $data['@graph'];
		} elseif (isset($data[0])) {
			$candidats = $data;
		} else {
			$candidats = [$data];
		}

		foreach ($candidats as $c) {
			$type = $c['@type'] ?? '';
			if (is_array($type)) {
				$type = reset($type);
			}
			if (in_array($type, $prefere, true)) {
				return $c;
			}
		}
	}

	// Fallback : retourner le premier bloc valide
	foreach ($matches[1] as $json) {
		$data = json_decode(trim($json), true);
		if (is_array($data)) {
			return $data;
		}
	}
	return [];
}
