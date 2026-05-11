<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/action/rescraper_linkedin_post.php';

final class ActionRescraperIntegrationTest extends TestCase
{
    private const TEST_LINKEDIN_POST_ID = 99001;

    protected function setUp(): void
    {
        if (!function_exists('charger_fonction')) {
            $this->markTestSkipped('SPIP non disponible: test integration action ignore.');
        }

        include_spip('base/linkedin_post');
        include_spip('base/create');
        maj_tables(['spip_linkedin_posts']);
        include_spip('action/editer_objet');

        $_REQUEST = [];
        $_POST = [];
        $GLOBALS['visiteur_session'] = [
            'id_auteur' => 0,
            'pass' => '',
            'statut' => '6forum',
        ];
    }

    protected function tearDown(): void
    {
        sql_delete('spip_linkedin_posts', 'id_linkedin_post = ' . self::TEST_LINKEDIN_POST_ID);
    }

    public function testRescraperNeModifiePasLePostPourUnVisiteur(): void
    {
        $id_linkedin_post = self::TEST_LINKEDIN_POST_ID;
        sql_delete('spip_linkedin_posts', 'id_linkedin_post = ' . (int) $id_linkedin_post);

        $ok = sql_insertq('spip_linkedin_posts', [
            'id_linkedin_post' => $id_linkedin_post,
            'url' => 'https://www.linkedin.com/posts/test-action-123',
            'titre' => 'Titre avant rescrape',
            'resume' => 'Resume avant rescrape',
            'texte' => 'Texte avant rescrape',
            'auteur_post' => 'Auteur initial',
            'image_url' => '',
            'date_post' => '2025-01-01 10:00:00',
            'date' => '2025-01-01 10:00:00',
            'statut' => 'prepa',
        ]);

        $this->assertNotFalse($ok);

        $avant = sql_fetsel(
            'id_linkedin_post, titre, texte, auteur_post, image_url, date_post',
            'spip_linkedin_posts',
            'id_linkedin_post = ' . (int) $id_linkedin_post
        );

        $this->assertIsArray($avant);

        $GLOBALS['visiteur_session'] = [
            'id_auteur' => 0,
            'pass' => '',
            'statut' => '6forum',
        ];

        $secu = generer_action_auteur('rescraper_linkedin_post', (string) $id_linkedin_post, '', -1);
        $_REQUEST = [
            'action' => $secu['action'],
            'arg' => (string) $secu['arg'],
            'hash' => $secu['hash'],
        ];
        $_POST = $_REQUEST;

        action_rescraper_linkedin_post_dist();

        $apres = sql_fetsel(
            'id_linkedin_post, titre, texte, auteur_post, image_url, date_post',
            'spip_linkedin_posts',
            'id_linkedin_post = ' . (int) $id_linkedin_post
        );

        $this->assertSame($avant, $apres);
    }
}
