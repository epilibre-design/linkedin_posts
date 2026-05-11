<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/formulaires/importer_linkedin_post.php';
require_once dirname(__DIR__, 2) . '/inc/linkedin_post_scraper.php';

final class FormulaireImporterCvtTest extends TestCase
{
    protected function setUp(): void
    {
        $_REQUEST = [];
        $_POST = [];
        $GLOBALS['_test_request'] = [];
        $GLOBALS['_test_sql_countsel'] = [];
        $GLOBALS['_test_config'] = [];
        $GLOBALS['_test_autoriser'] = true;
    }

    // ------------------------------------------------------------------
    // Cas : champs obligatoires
    // ------------------------------------------------------------------

    public function testVerifierSignaleUrlObligatoireEtRubriqueInvalide(): void
    {
        $_REQUEST = [
            'url' => '',
            'id_rubrique' => 0,
        ];
        $_POST = $_REQUEST;

        $erreurs = formulaires_importer_linkedin_post_verifier_dist();

        $this->assertSame(_T('info_obligatoire'), $erreurs['url'] ?? null);
        $this->assertSame(_T('linkedin_post:erreur_rubrique_invalide'), $erreurs['id_rubrique'] ?? null);
    }

    // ------------------------------------------------------------------
    // Cas : URL invalide (domaine non LinkedIn)
    // ------------------------------------------------------------------

    public function testVerifierSignaleUrlInvalide(): void
    {
        $_REQUEST = [
            'url' => 'https://example.com/not-linkedin',
            'id_rubrique' => 0,
        ];
        $_POST = $_REQUEST;

        $erreurs = formulaires_importer_linkedin_post_verifier_dist();

        $this->assertSame(_T('linkedin_post:erreur_url_invalide'), $erreurs['url'] ?? null);
    }

    // ------------------------------------------------------------------
    // Cas : URL déjà importée (doublon)
    // ------------------------------------------------------------------

    public function testVerifierSignaleUrlDejaImportee(): void
    {
        $url = 'https://www.linkedin.com/posts/johndoe_slug-activity-123';
        $_REQUEST = [
            'url' => $url,
            'id_rubrique' => 5,
        ];
        $_POST = $_REQUEST;

        // La table linkedin_posts indique 1 doublon, la table rubriques indique 1 entrée
        $GLOBALS['_test_sql_countsel']['spip_linkedin_posts'] = 1;
        $GLOBALS['_test_sql_countsel']['spip_rubriques'] = 1;

        $erreurs = formulaires_importer_linkedin_post_verifier_dist();

        $this->assertSame(_T('linkedin_post:erreur_url_deja_importee'), $erreurs['url'] ?? null);
        // Pas d'erreur rubrique : elle existe bien
        $this->assertArrayNotHasKey('id_rubrique', $erreurs);
    }

    // ------------------------------------------------------------------
    // Cas : rubrique inexistante
    // ------------------------------------------------------------------

    public function testVerifierSignaleRubriqueInexistante(): void
    {
        $url = 'https://www.linkedin.com/posts/johndoe_slug-activity-456';
        $_REQUEST = [
            'url' => $url,
            'id_rubrique' => 99,
        ];
        $_POST = $_REQUEST;

        // URL nouvelle (pas de doublon), mais rubrique 99 n'existe pas
        $GLOBALS['_test_sql_countsel']['spip_linkedin_posts'] = 0;
        $GLOBALS['_test_sql_countsel']['spip_rubriques'] = 0;

        $erreurs = formulaires_importer_linkedin_post_verifier_dist();

        $this->assertArrayNotHasKey('url', $erreurs);
        $this->assertSame(_T('linkedin_post:erreur_rubrique_invalide'), $erreurs['id_rubrique'] ?? null);
    }

    // ------------------------------------------------------------------
    // Cas : tout est valide → pas d'erreur
    // ------------------------------------------------------------------

    public function testVerifierSansErreurSiToutEstValide(): void
    {
        $url = 'https://www.linkedin.com/posts/johndoe_slug-activity-789';
        $_REQUEST = [
            'url' => $url,
            'id_rubrique' => 3,
        ];
        $_POST = $_REQUEST;

        $GLOBALS['_test_sql_countsel']['spip_linkedin_posts'] = 0;
        $GLOBALS['_test_sql_countsel']['spip_rubriques'] = 1;

        $erreurs = formulaires_importer_linkedin_post_verifier_dist();

        $this->assertEmpty($erreurs);
    }

    public function testVerifierRefuseRubriqueHorsConfiguration(): void
    {
        $url = 'https://www.linkedin.com/posts/johndoe_slug-activity-790';
        $_REQUEST = [
            'url' => $url,
            'id_rubrique' => 3,
        ];
        $_POST = $_REQUEST;

        $GLOBALS['_test_config']['linkedin_post_rubriques_autorisees'] = [1, 2];
        $GLOBALS['_test_sql_countsel']['spip_linkedin_posts'] = 0;
        $GLOBALS['_test_sql_countsel']['spip_rubriques'] = 1;

        $erreurs = formulaires_importer_linkedin_post_verifier_dist();

        $this->assertSame(_T('linkedin_post:erreur_rubrique_invalide'), $erreurs['id_rubrique'] ?? null);
    }

    // ------------------------------------------------------------------
    // charger : renvoie false si non autorisé
    // ------------------------------------------------------------------

    public function testChargerRetourneFalseSiNonAutorise(): void
    {
        $GLOBALS['_test_autoriser'] = false;

        $result = formulaires_importer_linkedin_post_charger_dist();

        $this->assertFalse($result);
    }

    // ------------------------------------------------------------------
    // charger : rubrique par défaut depuis config
    // ------------------------------------------------------------------

    public function testChargerRetourneStructureAttendue(): void
    {
        $GLOBALS['_test_autoriser'] = true;

        $result = formulaires_importer_linkedin_post_charger_dist();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('id_rubrique', $result);
        $this->assertSame('', $result['url']);
    }

    // ------------------------------------------------------------------
    // charger : rubrique passée en paramètre prime sur la config
    // ------------------------------------------------------------------

    public function testChargerUtiliseRubriqueEnParametre(): void
    {
        $GLOBALS['_test_autoriser'] = true;

        $result = formulaires_importer_linkedin_post_charger_dist(42);

        $this->assertIsArray($result);
        $this->assertSame(42, $result['id_rubrique']);
    }

    public function testChargerBasculeSurPremiereRubriqueAutorisee(): void
    {
        $GLOBALS['_test_autoriser'] = true;
        $GLOBALS['_test_config']['linkedin_post_rubriques_autorisees'] = [7, 9];

        $result = formulaires_importer_linkedin_post_charger_dist(0);

        $this->assertSame(7, $result['id_rubrique']);
        $this->assertSame([7, 9], $result['rubriques_autorisees']);
    }
}
