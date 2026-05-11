<?php

declare(strict_types=1);

use Spip\Core\Testing\SquelettesTestCase;

require_once dirname(__DIR__, 2) . '/inc/linkedin_post_scraper.php';

/**
 * Tests d'intégration du rendu HTML du formulaire #IMPORTER_LINKEDIN_POST.
 *
 * On appelle recuperer_fond() directement sur le squelette du formulaire
 * pour maîtriser le contexte et vérifier le HTML produit.
 */
final class FormulaireImporterRenduTest extends SquelettesTestCase
{
    public static function setUpBeforeClass(): void
    {
        // Enregistrer le répertoire du plugin dans le path SPIP
        _chemin(dirname(__DIR__, 2));

        // Ensure at least one rubrique exists for form rendering
        self::ensureTestRubriques();
    }
    
    private static function ensureTestRubriques(): void
    {
        // Utiliser l'API SQL SPIP pour cibler la base réellement configurée.
        try {
            include_spip('base/abstract_sql');

            if ((int) sql_countsel('spip_rubriques') === 0) {
                sql_insertq('spip_rubriques', [
                    'titre' => 'Test Rubrique',
                    'id_parent' => 0,
                    'id_secteur' => 0,
                    'statut' => 'publie',
                ]);
            }
        } catch (Throwable) {
            // Silently skip if database not accessible
        }
    }

    // ------------------------------------------------------------------
    // Contexte helper
    // ------------------------------------------------------------------

    private function renderForm(array $contexte = []): string
    {
        return recuperer_fond('formulaires/importer_linkedin_post', array_merge([
            'editable'       => true,
            'action'         => '/import',
            'erreurs'        => [],
            'message_ok'     => '',
            'message_erreur' => '',
            'url'            => '',
            'id_rubrique'    => 0,
        ], $contexte));
    }

    // ------------------------------------------------------------------
    // Structure de base du formulaire
    // ------------------------------------------------------------------

    public function testFormulairePossedeLesChampsPrincipaux(): void
    {
        $html = $this->renderForm();

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('name="url"', $html);
        $this->assertStringContainsString('name="id_rubrique"', $html);
        $this->assertStringContainsString('type="submit"', $html);
    }

    public function testFormulaireUtiliseLActionPasseeEnContexte(): void
    {
        $html = $this->renderForm(['action' => '/mon-import']);

        $this->assertStringContainsString('/mon-import', $html);
    }

    // ------------------------------------------------------------------
    // Formulaire non autorisé (editable = false)
    // ------------------------------------------------------------------

    public function testFormulaireVideSiNonEditable(): void
    {
        $html = $this->renderForm(['editable' => false]);

        $this->assertEmpty($html);
    }

    // ------------------------------------------------------------------
    // Messages de retour CVT
    // ------------------------------------------------------------------

    public function testFormulaireAfficheMessageOk(): void
    {
        $html = $this->renderForm(['message_ok' => 'Import réussi']);

        $this->assertStringContainsString('reponse_formulaire_ok', $html);
        $this->assertStringContainsString('Import réussi', $html);
    }

    public function testFormulaireAfficheMessageErreur(): void
    {
        $html = $this->renderForm(['message_erreur' => 'Scraping échoué']);

        $this->assertStringContainsString('reponse_formulaire_erreur', $html);
        $this->assertStringContainsString('Scraping échoué', $html);
    }

    // ------------------------------------------------------------------
    // Erreurs de validation sur les champs
    // ------------------------------------------------------------------

    public function testFormulaireMarqueChampUrlEnErreur(): void
    {
        $html = $this->renderForm([
            'erreurs' => ['url' => 'URL invalide'],
        ]);

        $this->assertStringContainsString('editer_url', $html);
        $this->assertStringContainsString('erreur', $html);
        $this->assertStringContainsString('URL invalide', $html);
    }

    public function testFormulaireMarqueChampRubriqueEnErreur(): void
    {
        $html = $this->renderForm([
            'erreurs' => ['id_rubrique' => 'Rubrique invalide'],
        ]);

        $this->assertStringContainsString('editer_id_rubrique', $html);
        $this->assertStringContainsString('erreur', $html);
        $this->assertStringContainsString('Rubrique invalide', $html);
    }

    // ------------------------------------------------------------------
    // Labels et textes traduits
    // ------------------------------------------------------------------

    public function testFormulaireContientLabelUrl(): void
    {
        $html = $this->renderForm();

        $this->assertStringContainsString(_T('linkedin_post:label_url'), $html);
    }

    public function testFormulaireContientBoutonImporter(): void
    {
        $html = $this->renderForm();

        $this->assertStringContainsString(_T('linkedin_post:bouton_importer'), $html);
    }
}
