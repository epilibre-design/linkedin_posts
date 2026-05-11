<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/linkedin_post_pipelines.php';

final class PipelinePostEditionIntegrationTest extends TestCase
{
    private ?int $idArticle = null;
    private ?int $idLinkedinPost = null;
    private ?int $idRubrique = null;
    private bool $rubriqueCreee = false;

    protected function setUp(): void
    {
        if (!function_exists('charger_fonction')) {
            $this->markTestSkipped('SPIP non disponible: test integration pipeline ignoré.');
        }

        include_spip('base/linkedin_post');
        include_spip('base/create');
        include_spip('base/abstract_sql');
        include_spip('inc/plugin');
        include_spip('inc/autoriser');
        include_spip('action/editer_article');
        _chemin(dirname(__DIR__, 2));
        actualise_plugins_actifs();
        maj_tables(['spip_articles', 'spip_rubriques', 'spip_linkedin_posts']);

        $_REQUEST = [];
        $_POST = [];
        $GLOBALS['visiteur_session'] = [
            'id_auteur' => 1,
            'pass' => '',
            'statut' => '0minirezo',
        ];

        $this->idRubrique = $this->ensureRubriqueDeTest();
        $this->idArticle = $this->creerArticleDeTest();
        $this->idLinkedinPost = $this->creerPostLinkedinDeTest($this->idArticle);
        autoriser_exception('publierdans', 'rubrique', (int) $this->idRubrique);
    }

    protected function tearDown(): void
    {
        if ($this->idLinkedinPost !== null) {
            sql_delete('spip_linkedin_posts', 'id_linkedin_post = ' . (int) $this->idLinkedinPost);
        }

        if ($this->idArticle !== null) {
            sql_delete('spip_articles', 'id_article = ' . (int) $this->idArticle);
        }

        if ($this->rubriqueCreee && $this->idRubrique !== null) {
            sql_delete('spip_rubriques', 'id_rubrique = ' . (int) $this->idRubrique);
        }
    }

    private function ensureRubriqueDeTest(): int
    {
        $idRubrique = (int) sql_getfetsel('id_rubrique', 'spip_rubriques', 'id_parent = 0', '', 'id_rubrique ASC', '1');
        if ($idRubrique > 0) {
            return $idRubrique;
        }

        $idRubrique = (int) sql_insertq('spip_rubriques', [
            'titre' => 'Rubrique test pipeline LinkedIn',
            'id_parent' => 0,
            'id_secteur' => 0,
            'statut' => 'publie',
        ]);

        $this->assertGreaterThan(0, $idRubrique);
        $this->rubriqueCreee = true;

        return $idRubrique;
    }

    private function creerArticleDeTest(): int
    {
        $idArticle = (int) article_inserer((int) $this->idRubrique);

        $this->assertGreaterThan(0, $idArticle);

        return $idArticle;
    }

    private function creerPostLinkedinDeTest(int $idArticle): int
    {
        $ok = sql_insertq('spip_linkedin_posts', [
            'id_linkedin_post' => $idArticle,
            'id_article' => $idArticle,
            'url' => 'https://www.linkedin.com/posts/test-pipeline-' . $idArticle,
            'titre' => 'Titre test pipeline ' . $idArticle,
            'resume' => '',
            'texte' => '',
            'auteur_post' => '',
            'image_url' => '',
            'date_post' => '2025-01-01 10:00:00',
            'date' => '2025-01-01 10:00:00',
            'statut' => 'prepa',
        ]);

        $this->assertNotFalse($ok);

        return $idArticle;
    }

    private function getStatutPost(): string
    {
        return (string) sql_getfetsel(
            'statut',
            'spip_linkedin_posts',
            'id_article = ' . (int) $this->idArticle
        );
    }

    private function instituerArticle(string $statut): void
    {
        $err = article_instituer((int) $this->idArticle, ['statut' => $statut]);
        $this->assertSame('', $err);
    }

    public function testArticlePublieMetPostAPublie(): void
    {
        $this->instituerArticle('publie');

        $this->assertSame('publie', $this->getStatutPost());
    }

    public function testArticlePoubelleMetPostAPoubelle(): void
    {
        $this->instituerArticle('poubelle');

        $this->assertSame('poubelle', $this->getStatutPost());
    }

    public function testArticleRefuseMetPostAPoubelle(): void
    {
        $this->instituerArticle('refuse');

        $this->assertSame('poubelle', $this->getStatutPost());
    }

    public function testArticlePasseDePublieAPropEtPostRepartEnPrepa(): void
    {
        $this->instituerArticle('publie');
        $this->assertSame('publie', $this->getStatutPost());

        $this->instituerArticle('prop');

        $this->assertSame('prepa', $this->getStatutPost());
    }
}
