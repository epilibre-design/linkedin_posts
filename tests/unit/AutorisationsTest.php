<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/linkedin_post_autorisations.php';

final class AutorisationsTest extends TestCase
{
    // helpers
    private static function admin(): array    { return ['statut' => '0minirezo']; }
    private static function redacteur(): array { return ['statut' => '1comite']; }
    private static function visiteur(): array  { return ['statut' => '6forum']; }

    // ------------------------------------------------------------------
    // creer
    // ------------------------------------------------------------------

    public function testCreerToujoursInterdit(): void
    {
        $this->assertFalse(autoriser_postlinkedin_creer_dist('creer', 'linkedin_post', 0, self::admin(), []));
        $this->assertFalse(autoriser_postlinkedin_creer_dist('creer', 'linkedin_post', 0, self::redacteur(), []));
        $this->assertFalse(autoriser_postlinkedin_creer_dist('creer', 'linkedin_post', 0, self::visiteur(), []));
    }

    // ------------------------------------------------------------------
    // supprimer
    // ------------------------------------------------------------------

    public function testSupprimerReserveAuxAdmins(): void
    {
        $this->assertTrue(autoriser_postlinkedin_supprimer_dist('supprimer', 'linkedin_post', 1, self::admin(), []));
        $this->assertFalse(autoriser_postlinkedin_supprimer_dist('supprimer', 'linkedin_post', 1, self::redacteur(), []));
        $this->assertFalse(autoriser_postlinkedin_supprimer_dist('supprimer', 'linkedin_post', 1, self::visiteur(), []));
    }

    // ------------------------------------------------------------------
    // modifier
    // ------------------------------------------------------------------

    public function testModifierAutoriseAdminEtRedacteur(): void
    {
        $this->assertTrue(autoriser_postlinkedin_modifier_dist('modifier', 'linkedin_post', 1, self::admin(), []));
        $this->assertTrue(autoriser_postlinkedin_modifier_dist('modifier', 'linkedin_post', 1, self::redacteur(), []));
    }

    public function testModifierRefuseVisiteur(): void
    {
        $this->assertFalse(autoriser_postlinkedin_modifier_dist('modifier', 'linkedin_post', 1, self::visiteur(), []));
    }

    // ------------------------------------------------------------------
    // configurer
    // ------------------------------------------------------------------

    public function testConfigurerReserveAuxAdmins(): void
    {
        $this->assertTrue(autoriser_postlinkedin_configurer_dist('configurer', 'linkedin_post', 0, self::admin(), []));
        $this->assertFalse(autoriser_postlinkedin_configurer_dist('configurer', 'linkedin_post', 0, self::redacteur(), []));
        $this->assertFalse(autoriser_postlinkedin_configurer_dist('configurer', 'linkedin_post', 0, self::visiteur(), []));
    }

    // ------------------------------------------------------------------
    // rescraper (délègue à modifier)
    // ------------------------------------------------------------------

    public function testRescraperSuivitModifierViaGlobalMock(): void
    {
        // Le mock bootstrap renvoie $GLOBALS['_test_autoriser'] pour autoriser()
        $GLOBALS['_test_autoriser'] = true;
        $this->assertTrue(autoriser_postlinkedin_rescraper_dist('rescraper', 'linkedin_post', 1, self::redacteur(), []));

        $GLOBALS['_test_autoriser'] = false;
        $this->assertFalse(autoriser_postlinkedin_rescraper_dist('rescraper', 'linkedin_post', 1, self::visiteur(), []));
    }

    // ------------------------------------------------------------------
    // importer
    // ------------------------------------------------------------------

    public function testImporterAutorisePourAdminEtRedacteur(): void
    {
        $this->assertTrue(autoriser_postlinkedin_importer_dist('importer', 'linkedin_post', 0, self::admin(), []));
        $this->assertTrue(autoriser_postlinkedin_importer_dist('importer', 'linkedin_post', 0, self::redacteur(), []));
        $this->assertFalse(autoriser_postlinkedin_importer_dist('importer', 'linkedin_post', 0, self::visiteur(), []));
    }

    // ------------------------------------------------------------------
    // voir / voir_liste (délèguent à autoriser('ecrire'))
    // ------------------------------------------------------------------

    public function testVoirDelegueAAutoriserEcrire(): void
    {
        $GLOBALS['_test_autoriser'] = true;
        $this->assertTrue(autoriser_postlinkedin_voir_dist('voir', 'linkedin_post', 1, self::redacteur(), []));

        $GLOBALS['_test_autoriser'] = false;
        $this->assertFalse(autoriser_postlinkedin_voir_dist('voir', 'linkedin_post', 1, self::visiteur(), []));
    }

    public function testVoirListeDelegueAAutoriserEcrire(): void
    {
        $GLOBALS['_test_autoriser'] = true;
        $this->assertTrue(autoriser_voir_liste_linkedin_posts_dist('voir_liste', 'linkedin_post', 0, self::redacteur(), []));

        $GLOBALS['_test_autoriser'] = false;
        $this->assertFalse(autoriser_voir_liste_linkedin_posts_dist('voir_liste', 'linkedin_post', 0, self::visiteur(), []));
    }
}
