<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/linkedin_post_scraper.php';
require_once dirname(__DIR__, 2) . '/formulaires/importer_linkedin_post.php';

final class FormulaireImporterCvtIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('charger_fonction')) {
            $this->markTestSkipped('SPIP non disponible: test integration CVT ignore.');
        }

        $_REQUEST = [];
        $_POST = [];
    }

    public function testVerifierEnvironnementSpipSignaleUrlEtRubriqueObligatoires(): void
    {
        $_REQUEST['url'] = '';
        $_REQUEST['id_rubrique'] = 0;
        $_POST = $_REQUEST;

        $erreurs = formulaires_importer_linkedin_post_verifier_dist();

        $this->assertArrayHasKey('url', $erreurs);
        $this->assertArrayHasKey('id_rubrique', $erreurs);
        $this->assertSame(_T('info_obligatoire'), $erreurs['url']);
        $this->assertSame(_T('linkedin_post:erreur_rubrique_invalide'), $erreurs['id_rubrique']);
    }

    public function testVerifierEnvironnementSpipRefuseUrlInvalide(): void
    {
        $_REQUEST['url'] = 'https://example.com/not-linkedin';
        $_REQUEST['id_rubrique'] = 0;
        $_POST = $_REQUEST;

        $erreurs = formulaires_importer_linkedin_post_verifier_dist();

        $this->assertArrayHasKey('url', $erreurs);
        $this->assertSame(_T('linkedin_post:erreur_url_invalide'), $erreurs['url']);
    }
}
