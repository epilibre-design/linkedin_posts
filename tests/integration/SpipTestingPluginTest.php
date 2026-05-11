<?php

declare(strict_types=1);

use Spip\Core\Testing\SquelettesTestCase;

final class SpipTestingPluginTest extends SquelettesTestCase
{
    public static function setUpBeforeClass(): void
	{
		include_spip('inc/filtres'); // pour affdate() utilisé dans linkedin_post_titre_article_depuis_date_post()
        require_once dirname(__DIR__, 2) . '/inc/linkedin_post_article.php';
	}

    public function testFormaterTexteArticleContientSourceEtAuteur(): void
    {
        $post = [
            'texte' => "Ligne 1\n\nLigne 2",
            'auteur_post' => 'Alice',
            'url' => 'https://www.linkedin.com/posts/demo-post',
        ];

        $texte = linkedin_post_formater_texte_article($post);

        $this->assertStringContainsString('Ligne 1', $texte);
        $this->assertStringContainsString('-* ' . _T('linkedin_post:par') . ' Alice', $texte);
        $this->assertStringContainsString('[' . _T('linkedin_post:source_linkedin') . '->https://www.linkedin.com/posts/demo-post]', $texte);
    }

    public function testFormaterTexteArticleSansAuteurNaffichePasLaLignePar(): void
    {
        $post = [
            'texte' => 'Contenu simple',
            'auteur_post' => '',
            'url' => 'https://www.linkedin.com/posts/without-author',
        ];

        $texte = linkedin_post_formater_texte_article($post);

        $this->assertStringNotContainsString(_T('linkedin_post:par'), $texte);
        $this->assertStringContainsString('[' . _T('linkedin_post:source_linkedin') . '->https://www.linkedin.com/posts/without-author]', $texte);
    }

    // ------------------------------------------------------------------
    // linkedin_post_titre_article_depuis_date_post()
    // Ces tests nécessitent affdate() fourni par le runtime SPIP.
    // ------------------------------------------------------------------

    public function testTitreArticleCommenceParSurLinkedin(): void
    {
        $titre = linkedin_post_titre_article_depuis_date_post('2024-06-10 08:30:00');

        $this->assertStringStartsWith('Sur LinkedIn, le ', $titre);
    }

    public function testTitreArticleAvecDateInvalideNeCrashePas(): void
    {
        $titre = linkedin_post_titre_article_depuis_date_post('date-invalide');

        $this->assertIsString($titre);
        $this->assertNotEmpty($titre);
        $this->assertStringStartsWith('Sur LinkedIn, le ', $titre);
    }

    public function testTitreArticleAvecDateVideNeCrashePas(): void
    {
        $titre = linkedin_post_titre_article_depuis_date_post('');

        $this->assertIsString($titre);
        $this->assertStringStartsWith('Sur LinkedIn, le ', $titre);
    }
}
