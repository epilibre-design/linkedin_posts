<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/linkedin_post_scraper.php';

/**
 * Tests unitaires pour inc/linkedin_post_scraper.php.
 *
 * Couverture :
 *  - linkedin_post_url_valide()
 *  - linkedin_post_normaliser_url()
 *  - linkedin_post_extraire_meta()
 *  - linkedin_post_extraire_jsonld()
 */
final class ScraperTest extends TestCase
{
    // ------------------------------------------------------------------
    // linkedin_post_url_valide()
    // ------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('urlsValides')]
    public function testUrlValideAccepteUrlsLegitimes(string $url): void
    {
        $this->assertTrue(linkedin_post_url_valide($url), "Attendu valide : $url");
    }

    public static function urlsValides(): array
    {
        return [
            'posts standard'          => ['https://www.linkedin.com/posts/johndoe_some-slug-activity-1234567890'],
            'feed/update'             => ['https://www.linkedin.com/feed/update/urn:li:activity:1234567890'],
            'pulse article'           => ['https://www.linkedin.com/pulse/mon-article-johndoe/'],
            'sans www'                => ['https://linkedin.com/posts/company_slug-activity-123'],
            'sous-domaine fr'         => ['https://fr.linkedin.com/posts/slug-activity-123'],
            'sous-domaine de 2 chars' => ['https://de.linkedin.com/posts/slug-activity-123'],
            'http (non-https)'        => ['http://www.linkedin.com/posts/slug-activity-123'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('urlsInvalides')]
    public function testUrlValideRefuseUrlsInvalides(mixed $url): void
    {
        $this->assertFalse(linkedin_post_url_valide($url), "Attendu invalide : " . var_export($url, true));
    }

    public static function urlsInvalides(): array
    {
        return [
            'chaîne vide'                => [''],
            'domaine autre'              => ['https://example.com/posts/slug'],
            'faux linkedin'              => ['https://notlinkedin.com/posts/slug'],
            'profil utilisateur'         => ['https://www.linkedin.com/in/johndoe/'],
            'page entreprise'            => ['https://www.linkedin.com/company/acme/'],
            'recherche'                  => ['https://www.linkedin.com/search/results/all/?keywords=php'],
            'null converti string false' => [false],
            'entier'                     => [42],
            'null'                       => [null],
        ];
    }

    // ------------------------------------------------------------------
    // linkedin_post_normaliser_url()
    // ------------------------------------------------------------------

    public function testNormaliserUrlRetireFragment(): void
    {
        $url = 'https://www.linkedin.com/posts/slug-activity-123#comments';
        $this->assertSame(
            'https://www.linkedin.com/posts/slug-activity-123',
            linkedin_post_normaliser_url($url)
        );
    }

    public function testNormaliserUrlRetireParamsUtm(): void
    {
        $url = 'https://www.linkedin.com/posts/slug?utm_source=newsletter&utm_medium=email';
        $this->assertSame(
            'https://www.linkedin.com/posts/slug',
            linkedin_post_normaliser_url($url)
        );
    }

    public function testNormaliserUrlRetireParamTrk(): void
    {
        $url = 'https://www.linkedin.com/posts/slug?trk=public_post_share-update-article';
        $this->assertSame(
            'https://www.linkedin.com/posts/slug',
            linkedin_post_normaliser_url($url)
        );
    }

    public function testNormaliserUrlRetireOriginalSubdomain(): void
    {
        $url = 'https://fr.linkedin.com/posts/slug?originalSubdomain=fr';
        $this->assertSame(
            'https://fr.linkedin.com/posts/slug',
            linkedin_post_normaliser_url($url)
        );
    }

    public function testNormaliserUrlConserveParamsLegitimes(): void
    {
        $url = 'https://www.linkedin.com/posts/slug?page=2&sort=asc';
        $result = linkedin_post_normaliser_url($url);
        // Les paramètres légitimes sont conservés
        $this->assertStringContainsString('page=2', $result);
        $this->assertStringContainsString('sort=asc', $result);
    }

    public function testNormaliserUrlRetireALaFoisFragmentEtUtm(): void
    {
        $url = 'https://www.linkedin.com/posts/slug?utm_campaign=spring#likes';
        $this->assertSame(
            'https://www.linkedin.com/posts/slug',
            linkedin_post_normaliser_url($url)
        );
    }

    public function testNormaliserUrlSansParamsResteinchangee(): void
    {
        $url = 'https://www.linkedin.com/posts/johndoe_slug-activity-123';
        $this->assertSame($url, linkedin_post_normaliser_url($url));
    }

    public function testNormaliserUrlTrimeEspaces(): void
    {
        $url = '  https://www.linkedin.com/posts/slug  ';
        $this->assertSame(
            'https://www.linkedin.com/posts/slug',
            linkedin_post_normaliser_url($url)
        );
    }

    // ------------------------------------------------------------------
    // linkedin_post_extraire_meta()
    // ------------------------------------------------------------------

    public function testExtraireMetaPropertyAvantContent(): void
    {
        $html = '<meta property="og:title" content="Titre du post" />';
        $this->assertSame('Titre du post', linkedin_post_extraire_meta($html, 'og:title'));
    }

    public function testExtraireMetaContentAvantProperty(): void
    {
        // Ordre inversé : content d'abord, puis property
        $html = '<meta content="Description ici" property="og:description" />';
        $this->assertSame('Description ici', linkedin_post_extraire_meta($html, 'og:description'));
    }

    public function testExtraireMetaAvecName(): void
    {
        $html = '<meta name="twitter:title" content="Titre Twitter" />';
        $this->assertSame('Titre Twitter', linkedin_post_extraire_meta($html, 'twitter:title'));
    }

    public function testExtraireMetaDecodeEntitesHtml(): void
    {
        $html = '<meta property="og:title" content="Post &amp; actu &lt;2024&gt;" />';
        $this->assertSame('Post & actu <2024>', linkedin_post_extraire_meta($html, 'og:title'));
    }

    public function testExtraireMetaRetourneVideSiAbsente(): void
    {
        $html = '<meta property="og:description" content="Quelque chose" />';
        $this->assertSame('', linkedin_post_extraire_meta($html, 'og:title'));
    }

    public function testExtraireMetaRetourneVideSurHtmlVide(): void
    {
        $this->assertSame('', linkedin_post_extraire_meta('', 'og:title'));
    }

    public function testExtraireMetaAvecGuillemetsSimples(): void
    {
        $html = "<meta property='og:image' content='https://example.com/img.jpg' />";
        $this->assertSame('https://example.com/img.jpg', linkedin_post_extraire_meta($html, 'og:image'));
    }

    // ------------------------------------------------------------------
    // linkedin_post_extraire_jsonld()
    // ------------------------------------------------------------------

    public function testExtraireJsonldRetourneVideSiAbsent(): void
    {
        $html = '<html><head><title>Page</title></head></html>';
        $this->assertSame([], linkedin_post_extraire_jsonld($html));
    }

    public function testExtraireJsonldRetourneVideSiJsonInvalide(): void
    {
        $html = '<script type="application/ld+json">{ invalid json }</script>';
        $this->assertSame([], linkedin_post_extraire_jsonld($html));
    }

    public function testExtraireJsonldRetourneSocialMediaPosting(): void
    {
        $data = [
            '@type' => 'SocialMediaPosting',
            'articleBody' => 'Contenu du post',
            'author' => ['name' => 'Jean Dupont'],
            'datePublished' => '2024-03-15T10:00:00Z',
        ];
        $html = '<script type="application/ld+json">' . json_encode($data) . '</script>';

        $result = linkedin_post_extraire_jsonld($html);

        $this->assertSame('SocialMediaPosting', $result['@type']);
        $this->assertSame('Contenu du post', $result['articleBody']);
        $this->assertSame('Jean Dupont', $result['author']['name']);
    }

    public function testExtraireJsonldRetournePremierBlocDansListePrefere(): void
    {
        // SocialMediaPosting en premier → retourné en premier
        $social  = ['@type' => 'SocialMediaPosting', 'articleBody' => 'Contenu SocialMedia'];
        $article = ['@type' => 'Article', 'articleBody' => 'Contenu Article'];

        $html  = '<script type="application/ld+json">' . json_encode($social) . '</script>';
        $html .= '<script type="application/ld+json">' . json_encode($article) . '</script>';

        $result = linkedin_post_extraire_jsonld($html);
        $this->assertSame('SocialMediaPosting', $result['@type']);
        $this->assertSame('Contenu SocialMedia', $result['articleBody']);
    }

    public function testExtraireJsonldRetournePremierBlocPrefereMemeSiArrivePlusTard(): void
    {
        // Article en premier dans le HTML mais c'est quand même le premier bloc
        // dont le @type est dans $prefere → Article est retourné
        $other   = ['@type' => 'WebSite', 'name' => 'LinkedIn'];
        $article = ['@type' => 'Article', 'articleBody' => 'Contenu Article'];

        $html  = '<script type="application/ld+json">' . json_encode($other) . '</script>';
        $html .= '<script type="application/ld+json">' . json_encode($article) . '</script>';

        $result = linkedin_post_extraire_jsonld($html);
        $this->assertSame('Article', $result['@type']);
    }

    public function testExtraireJsonldGereStructureGraph(): void
    {
        $data = [
            '@graph' => [
                ['@type' => 'WebSite', 'name' => 'LinkedIn'],
                ['@type' => 'SocialMediaPosting', 'articleBody' => 'Contenu via @graph', 'author' => ['name' => 'Marie']],
            ],
        ];
        $html = '<script type="application/ld+json">' . json_encode($data) . '</script>';

        $result = linkedin_post_extraire_jsonld($html);
        $this->assertSame('SocialMediaPosting', $result['@type']);
        $this->assertSame('Contenu via @graph', $result['articleBody']);
    }

    public function testExtraireJsonldFallbackSurPremierBlocValide(): void
    {
        // Aucun bloc n'est SocialMediaPosting/Article : retourne le premier valide
        $data = ['@type' => 'WebSite', 'name' => 'LinkedIn'];
        $html = '<script type="application/ld+json">' . json_encode($data) . '</script>';

        $result = linkedin_post_extraire_jsonld($html);
        $this->assertSame('WebSite', $result['@type']);
    }

    public function testExtraireJsonldGereTypeEnTableau(): void
    {
        // @type peut être un tableau selon la spec JSON-LD
        $data = ['@type' => ['SocialMediaPosting', 'CreativeWork'], 'articleBody' => 'Contenu'];
        $html = '<script type="application/ld+json">' . json_encode($data) . '</script>';

        $result = linkedin_post_extraire_jsonld($html);
        $this->assertSame('Contenu', $result['articleBody']);
    }
}
