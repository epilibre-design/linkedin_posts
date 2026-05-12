# HOWTO TEST - Plugin linkedin_post

Ce document décrit le processus complet pour exécuter les tests unitaires et d'intégration du plugin dans un environnement reproductible.

Parcours recommandé (simplifié):
1. `composer install`
2. `composer tests-unit`
3. `composer tests-integration`

La commande `composer tests-integration` peut proposer de lancer automatiquement `composer install-spip-test` si SPIP local n'est pas encore installé.

Objectif:
- Avoir un plugin testable en mode unitaire (avec mocks légers)
- Avoir un SPIP réel installé en local pour les tests d'intégration

Contexte du dépôt:
- Plugin: linkedin_post
- Racine plugin: {SPIP_ROOT}/plugins/linkedin_post
- SPIP de test attendu: {SPIP_ROOT}/plugins/linkedin_post/vendor/spip/spip

## 1) Prérequis

1. PHP >= 8.2
2. Composer installé
3. Accès réseau pour:
   - https://get.spip.net/composer
   - https://git.spip.net/spip/tests.git
   - https://plugins.spip.net/depots/principal.xml

## 2) Installer les dépendances du plugin (dont spip/tests)

Le dépôt contient déjà la config nécessaire dans composer.json:
- require-dev: spip/tests
- repositories: entrée VCS vers https://git.spip.net/spip/tests.git

Depuis la racine du plugin:

```bash
cd {SPIP_ROOT}/plugins/linkedin_post
composer install
```

## 3) Installer un SPIP de test avec spip-cli (manuel, optionnel)

Cette section n'est utile que pour le diagnostic fin ou une installation pas à pas.
En usage normal, préférez `composer install-spip-test` (ou `composer tests-integration` qui peut le proposer automatiquement).

## 4) Bootstrap de tests: séparation unitaire/intégration

Règle clé:
- tests/bootstrap.php est destiné aux tests unitaires et contient des mocks (ex: sql_countsel).
- tests/bootstrap_integration.php doit charger SPIP réel et ne doit pas importer les mocks unitaires.

Organisation physique des fichiers de test:
- tests/unit/ contient les tests unitaires.
- tests/integration/ contient les tests d'intégration.

État attendu:
- Tests unitaires: phpunit.xml utilise tests/bootstrap.php
- Tests d'intégration: commande dédiée avec --bootstrap tests/bootstrap_integration.php

Commande d'intégration validée:

```bash
vendor/bin/phpunit --colors --testsuite integration --bootstrap tests/bootstrap_integration.php -c phpunit.xml
```

## 5) Exécuter les tests

Depuis la racine du plugin:

Parcours standard:

```bash
composer tests-unit
composer tests-integration
```

