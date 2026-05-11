<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/formulaires/editer_linkedin_post.php';

final class FormulaireEditerCvtIntegrationTest extends TestCase
{
    private const TEST_LINKEDIN_POST_ID = 99011;

    protected function setUp(): void
    {
        if (!function_exists('charger_fonction')) {
            $this->markTestSkipped('SPIP non disponible: test integration CVT ignore.');
        }

        include_spip('base/linkedin_post');
        include_spip('base/create');
        maj_tables(['spip_linkedin_posts']);

        $_REQUEST = [];
        $_POST = [];
        $GLOBALS['visiteur_session'] = [
            'id_auteur' => 1,
            'pass' => '',
            'statut' => '0minirezo',
        ];
    }

    protected function tearDown(): void
    {
        sql_delete('spip_linkedin_posts', 'id_linkedin_post = ' . self::TEST_LINKEDIN_POST_ID);
    }

    public function testChargerRetourneLesValeursInitialesEnCreation(): void
    {
        $result = formulaires_editer_linkedin_post_charger_dist('new', '/retour');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('titre', $result);
        $this->assertArrayHasKey('resume', $result);
        $this->assertArrayHasKey('texte', $result);
        $this->assertSame('', $result['url']);
        $this->assertSame('', $result['titre']);
    }

    public function testVerifierSignaleUrlEtTitreObligatoires(): void
    {
        $_REQUEST['url'] = '';
        $_REQUEST['titre'] = '';
        $_POST = $_REQUEST;

        $erreurs = formulaires_editer_linkedin_post_verifier_dist('new', '/retour');

        $this->assertArrayHasKey('url', $erreurs);
        $this->assertArrayHasKey('titre', $erreurs);
        $this->assertSame(_T('info_obligatoire'), $erreurs['url']);
        $this->assertSame(_T('info_obligatoire'), $erreurs['titre']);
    }

    public function testVerifierRefuseUneUrlLinkedinInvalide(): void
    {
        $_REQUEST['url'] = 'https://example.com/not-linkedin';
        $_REQUEST['titre'] = 'Titre';
        $_POST = $_REQUEST;

        $erreurs = formulaires_editer_linkedin_post_verifier_dist('new', '/retour');

        $this->assertArrayHasKey('url', $erreurs);
        $this->assertSame(_T('linkedin_post:erreur_url_invalide'), $erreurs['url']);
        $this->assertArrayNotHasKey('titre', $erreurs);
    }

    public function testTraiterMetAJourLeLinkedinPostExistant(): void
    {
        sql_delete('spip_linkedin_posts', 'id_linkedin_post = ' . self::TEST_LINKEDIN_POST_ID);

        $ok = sql_insertq('spip_linkedin_posts', [
            'id_linkedin_post' => self::TEST_LINKEDIN_POST_ID,
            'url' => 'https://www.linkedin.com/posts/original-activity-111',
            'titre' => 'Titre original',
            'resume' => 'Resume original',
            'texte' => 'Texte original',
            'auteur_post' => 'Auteur original',
            'image_url' => '',
            'date_post' => '2025-01-01 10:00:00',
            'date' => '2025-01-01 10:00:00',
            'statut' => 'prepa',
        ]);

        $this->assertNotFalse($ok);

        $_REQUEST = [
            'url' => 'https://www.linkedin.com/posts/updated-activity-222',
            'titre' => 'Titre mis a jour',
            'resume' => 'Resume mis a jour',
            'texte' => 'Texte mis a jour',
            'auteur_post' => 'Auteur mis a jour',
            'image_url' => 'https://example.test/image.jpg',
        ];
        $_POST = $_REQUEST;

        $erreurs = formulaires_editer_linkedin_post_verifier_dist(self::TEST_LINKEDIN_POST_ID, '/retour');
        $this->assertSame([], $erreurs);

        $res = formulaires_editer_linkedin_post_traiter_dist(self::TEST_LINKEDIN_POST_ID, '/retour');

        $this->assertIsArray($res);
        $this->assertArrayHasKey('message_ok', $res);
        $this->assertSame(_T('info_modification_enregistree'), $res['message_ok']);
        $this->assertSame(self::TEST_LINKEDIN_POST_ID, (int) ($res['id_linkedin_post'] ?? 0));

        $row = sql_fetsel(
            'url, titre, resume, texte, auteur_post, image_url',
            'spip_linkedin_posts',
            'id_linkedin_post = ' . self::TEST_LINKEDIN_POST_ID
        );

        $this->assertIsArray($row);
        $this->assertSame('https://www.linkedin.com/posts/updated-activity-222', $row['url']);
        $this->assertSame('Titre mis a jour', $row['titre']);
        $this->assertSame('Resume mis a jour', $row['resume']);
        $this->assertSame('Texte mis a jour', $row['texte']);
        $this->assertSame('Auteur mis a jour', $row['auteur_post']);
        $this->assertSame('https://example.test/image.jpg', $row['image_url']);
    }
}
