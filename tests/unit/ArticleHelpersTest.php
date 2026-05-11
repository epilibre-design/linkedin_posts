<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/linkedin_post_article.php';

final class ArticleHelpersTest extends TestCase
{
    // ------------------------------------------------------------------
    // linkedin_post_date_redac_depuis_date_post()
    // ------------------------------------------------------------------

    public function testDateRedacDepuisDatePostValide(): void
    {
        $this->assertSame('2025-01-02 13:14:15', linkedin_post_date_redac_depuis_date_post('2025-01-02 13:14:15'));
    }

    public function testDateRedacDepuisDatePostInvalideRetourneDatetimeSql(): void
    {
        $result = linkedin_post_date_redac_depuis_date_post('date-invalide');

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    public function testDateRedacDepuisDatePostVideRetourneDatetimeSql(): void
    {
        $result = linkedin_post_date_redac_depuis_date_post('');

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    public function testDateRedacDepuisDatePostIso8601(): void
    {
        $this->assertSame('2024-06-10 08:30:00', linkedin_post_date_redac_depuis_date_post('2024-06-10T08:30:00Z'));
    }

    // ------------------------------------------------------------------
    // linkedin_post_formater_texte_article()
    // ------------------------------------------------------------------

    private function makePost(array $overrides = []): array
    {
        return array_merge([
            'texte'       => "Ligne 1\nLigne 2",
            'auteur_post' => 'Jean Dupont',
            'url'         => 'https://www.linkedin.com/posts/jean_slug-activity-123',
        ], $overrides);
    }

    public function testFormaterTexteContientContenuOriginal(): void
    {
        $post = $this->makePost();
        $result = linkedin_post_formater_texte_article($post);

        $this->assertStringContainsString('Ligne 1', $result);
        $this->assertStringContainsString('Ligne 2', $result);
    }

    public function testFormaterTexteContientLigneAuteur(): void
    {
        $post = $this->makePost();
        $result = linkedin_post_formater_texte_article($post);

        $this->assertStringContainsString('-* ', $result);
        $this->assertStringContainsString('Jean Dupont', $result);
    }

    public function testFormaterTexteContientLienSource(): void
    {
        $post = $this->makePost();
        $result = linkedin_post_formater_texte_article($post);

        $this->assertStringContainsString('https://www.linkedin.com/posts/jean_slug-activity-123', $result);
    }

    public function testFormaterTexteSansAuteurNaffichePasLignePar(): void
    {
        $post = $this->makePost(['auteur_post' => '']);
        $result = linkedin_post_formater_texte_article($post);

        // La ligne source doit être présente
        $this->assertStringContainsString($post['url'], $result);
        // La ligne auteur ne doit pas apparaître
        $this->assertStringNotContainsString('Jean Dupont', $result);
    }

    public function testFormaterTexteNormaliseSautsDeLineWindowsEnUnix(): void
    {
        $post = $this->makePost(['texte' => "Ligne 1\r\nLigne 2\rLigne 3"]);
        $result = linkedin_post_formater_texte_article($post);

        $this->assertStringNotContainsString("\r", $result);
    }

    public function testFormaterTexteCollapseTripleSautsDeLigne(): void
    {
        $post = $this->makePost(['texte' => "Ligne 1\n\n\n\n\nLigne 2"]);
        $result = linkedin_post_formater_texte_article($post);

        // Pas plus de 2 sauts consécutifs dans le corps du texte
        $this->assertDoesNotMatchRegularExpression('/\n{3,}/', $result);
    }

    public function testFormaterTexteVideNeCrashePas(): void
    {
        $post = $this->makePost(['texte' => '']);
        $result = linkedin_post_formater_texte_article($post);

        $this->assertIsString($result);
        $this->assertStringContainsString($post['url'], $result);
    }

    // ------------------------------------------------------------------
    // linkedin_post_titre_article_depuis_date_post()
    // Note : appelle affdate() qui dépend de SPIP → on vérifie le préfixe
    // et le format attendu sans dépendance à la locale.
    // ------------------------------------------------------------------

    public function testTitreArticleCommenceParSurLinkedin(): void
    {
        // affdate() n'est pas disponible dans le bootstrap unit, on stub
        if (!function_exists('affdate')) {
            $this->markTestSkipped('affdate() non disponible hors contexte SPIP.');
        }

        $titre = linkedin_post_titre_article_depuis_date_post('2024-06-10 08:30:00');
        $this->assertStringStartsWith('Sur LinkedIn, le ', $titre);
    }

    public function testTitreArticleAvecDateInvalideNeCrashePas(): void
    {
        if (!function_exists('affdate')) {
            $this->markTestSkipped('affdate() non disponible hors contexte SPIP.');
        }

        $titre = linkedin_post_titre_article_depuis_date_post('invalid-date');
        $this->assertIsString($titre);
        $this->assertNotEmpty($titre);
    }
}
