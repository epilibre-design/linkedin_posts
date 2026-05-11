<?php

declare(strict_types=1);

use Spip\Core\Testing\SquelettesTestCase;

/**
 * Tests d'integration du rendu HTML du formulaire #FORMULAIRE_EDITER_LINKEDIN_POST.
 */
final class FormulaireEditerRenduTest extends SquelettesTestCase
{
    private function renderForm(array $contexte = []): string
    {
        return recuperer_fond('formulaires/editer_linkedin_post', array_merge([
            'editable'         => true,
            'action'           => '/editer',
            'erreurs'          => [],
            'message_ok'       => '',
            'message_erreur'   => '',
            'id_linkedin_post' => 1,
            'url'              => '',
            'titre'            => '',
            'resume'           => '',
            'texte'            => '',
            'auteur_post'      => '',
            'image_url'        => '',
        ], $contexte));
    }

    public function testFormulairePossedeLesChampsPrincipaux(): void
    {
        $html = $this->renderForm();

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('name="id_linkedin_post"', $html);
        $this->assertStringContainsString('name="url"', $html);
        $this->assertStringContainsString('name="titre"', $html);
        $this->assertStringContainsString('name="resume"', $html);
        $this->assertStringContainsString('name="texte"', $html);
        $this->assertStringContainsString('name="auteur_post"', $html);
        $this->assertStringContainsString('name="image_url"', $html);
        $this->assertStringContainsString('type="submit"', $html);
    }

    public function testFormulaireUtiliseLActionPasseeEnContexte(): void
    {
        $html = $this->renderForm(['action' => '/mon-edition']);

        $this->assertStringContainsString('/mon-edition', $html);
    }

    public function testFormulairePorteIdLinkedinPostPasseEnContexte(): void
    {
        $html = $this->renderForm(['id_linkedin_post' => 42]);

        $this->assertStringContainsString('name="id_linkedin_post" value="42"', $html);
    }

    public function testFormulaireVideSiNonEditable(): void
    {
        $html = $this->renderForm(['editable' => false]);

        $this->assertEmpty($html);
    }

    public function testFormulaireAfficheMessageOk(): void
    {
        $html = $this->renderForm(['message_ok' => 'Modification enregistree']);

        $this->assertStringContainsString('reponse_formulaire_ok', $html);
        $this->assertStringContainsString('Modification enregistree', $html);
    }

    public function testFormulaireAfficheMessageErreur(): void
    {
        $html = $this->renderForm(['message_erreur' => 'Erreur de sauvegarde']);

        $this->assertStringContainsString('reponse_formulaire_erreur', $html);
        $this->assertStringContainsString('Erreur de sauvegarde', $html);
    }

    public function testFormulaireMarqueChampUrlEnErreur(): void
    {
        $html = $this->renderForm([
            'erreurs' => ['url' => 'URL invalide'],
        ]);

        $this->assertStringContainsString('editer_url', $html);
        $this->assertStringContainsString('erreur', $html);
        $this->assertStringContainsString('URL invalide', $html);
    }

    public function testFormulaireMarqueChampTitreEnErreur(): void
    {
        $html = $this->renderForm([
            'erreurs' => ['titre' => 'Titre obligatoire'],
        ]);

        $this->assertStringContainsString('editer_titre', $html);
        $this->assertStringContainsString('erreur', $html);
        $this->assertStringContainsString('Titre obligatoire', $html);
    }

    public function testFormulaireAfficheApercuImageQuandImageUrlRenseignee(): void
    {
        $html = $this->renderForm([
            'image_url' => 'https://example.test/image.jpg',
        ]);

        $this->assertStringContainsString('apercu_image', $html);
        $this->assertStringContainsString('src="https://example.test/image.jpg"', $html);
    }

    public function testFormulaireContientLabelUrlEtBoutonEnregistrer(): void
    {
        $html = $this->renderForm();

        $this->assertStringContainsString(_T('linkedin_post:label_url'), $html);
        $this->assertStringContainsString(_T('bouton_enregistrer'), $html);
    }
}
