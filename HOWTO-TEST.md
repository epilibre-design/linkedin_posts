# HOWTO TEST - Plugin linkedin_post

## Avant de commencer

Après un `git clone`, créer le fichier `phpunit.xml` à partir de `phpunit-dist.xml`:

```bash
cp phpunit-dist.xml phpunit.xml
```

`phpunit.xml` n'est pas fourni par défaut car il est ignoré par Git.

Ce document décrit le processus complet pour exécuter les tests unitaires et d'intégration du plugin dans un environnement reproductible.

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
3. spip-cli disponible, par exemple:
   - /src/spip-cli/bin/spip
4. Accès réseau pour:
   - https://get.spip.net/composer
   - https://git.spip.net/spip/tests.git
   - https://plugins.spip.net/depots/principal.xml

Important:
- Le package spip/tests peut échouer si la plateforme Composer force une version PHP trop basse.
- Vérifier la plateforme Composer effective avant installation.

Exemples:

```bash
php -v
composer --version
composer config platform.php
```

## 2) Installer les dépendances du plugin (dont spip/tests)

Le dépôt contient déjà la config nécessaire dans composer.json:
- require-dev: spip/tests
- repositories: entrée VCS vers https://git.spip.net/spip/tests.git

Depuis la racine du plugin:

```bash
cd {SPIP_ROOT}/plugins/linkedin_post
composer install
```

## 3) Installer un SPIP de test avec spip-cli dans vendor/spip/spip

Depuis la racine du plugin:

```bash
cd {SPIP_ROOT}/plugins/linkedin_post
/src/spip-cli/bin/spip core:telecharger -d vendor/spip/spip
cd vendor/spip/spip
/src/spip-cli/bin/spip core:preparer
```

Installation base:
- Dans cette stack, le serveur SQL attendu est sqlite3 (pas sqlite).
- Avec sqlite3, db-database doit être un nom logique (ex: spip_test), pas un chemin fichier.

```bash
/src/spip-cli/bin/spip core:installer \
  --db-server=sqlite3 \
  --db-host='' \
  --db-login='' \
  --db-pass='' \
  --db-database='spip_test' \
  --db-prefix=spip \
  --admin-nom='Admin Test' \
  --admin-login='admin' \
  --admin-email='admin@example.test' \
  --admin-pass='adminadmin' \
  --adresse-site='http://localhost'
```

## 4) Ajouter le dépôt principal des plugins SPIP

Dans le SPIP installé:

```bash
cd {SPIP_ROOT}/plugins/linkedin_post/vendor/spip/spip
/src/spip-cli/bin/spip plugins:svp:depoter https://plugins.spip.net/depots/principal.xml
```

## 5) Rendre les plugins nécessaires visibles via symlinks

Le plugin linkedin_post et ses dépendances doivent être disponibles depuis SPIP.

### 5.1 Lien du plugin courant

```bash
ln -s {SPIP_ROOT}/plugins/linkedin_post \
  {SPIP_ROOT}/plugins/linkedin_post/vendor/spip/spip/plugins/linkedin_post
```

### 5.2 Dépendances en local (dossier auto)

Si vous maintenez des dépendances dans un dossier local auto, les rendre visibles dans plugins.

Exemple (lien global):

```bash
ln -s {SPIP_ROOT}/plugins/auto \
  {SPIP_ROOT}/plugins/linkedin_post/vendor/spip/spip/plugins/auto
```

Ou liens plugin par plugin vers vendor/spip/spip/plugins.

## 6) Activer le plugin linkedin_post et sa dépendance Saisies

```bash
cd {SPIP_ROOT}/plugins/linkedin_post/vendor/spip/spip
/src/spip-cli/bin/spip plugins:activer saisies
/src/spip-cli/bin/spip plugins:activer linkedin_post
```

Note pratique:
- Si `saisies` est maintenu dans `{SPIP_ROOT}/plugins/auto`, créer d'abord le lien global:

```bash
ln -sfn {SPIP_ROOT}/plugins/auto \
   {SPIP_ROOT}/plugins/linkedin_post/vendor/spip/spip/plugins/auto
```

- Ensuite réessayer l'activation, puis vérifier explicitement l'état réel:

```bash
/src/spip-cli/bin/spip plugins:lister | grep -Ei 'saisies|linkedin_post'
```

- Le CLI peut afficher un message d'erreur résiduel sur `linkedin_post` pendant l'activation de `saisies`, même si les deux plugins finissent actifs.

Optionnel, vérifier la présence:

```bash
/src/spip-cli/bin/spip plugins:lister | grep -i linkedin
```

## 7) Bootstrap de tests: séparation unitaire/intégration

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

## 8) Exécuter les tests

Depuis la racine du plugin:

### 8.1 Unitaires

```bash
composer tests-unit
```

### 8.2 Intégration

```bash
composer tests-integration
```

## 9) Vérifications minimales recommandées

1. Vérifier les scripts Composer:
   - composer tests-unit
   - composer tests-integration
2. Vérifier l'existence de SPIP:
   - vendor/spip/spip/ecrire/inc_version.php
3. Vérifier l'activation plugin:
   - spip plugins:lister | grep linkedin
4. Vérifier que le bootstrap d'intégration ne charge pas tests/bootstrap.php.

## 10) Dépannage

### 10.1 Fatal "Cannot redeclare function sql_countsel()"

Cause:
- Les mocks unitaires ont été chargés en intégration.

Correctif:
- Ne pas inclure tests/bootstrap.php dans tests/bootstrap_integration.php.
- Laisser SPIP charger ses propres fonctions SQL.

### 10.2 Le plugin est actif mais la table spip_linkedin_posts n'existe pas

Symptôme observé:
- plugins:activer montre le plugin actif
- mais la table n'est pas créée
- core:maj:bdd n'applique rien

Workaround validé (SQLite):

1. Marquer le plugin installé:

```sql
UPDATE spip_paquets SET installe='oui' WHERE prefixe='LINKEDIN_POST';
```

2. Forcer une version_base basse:

```sql
UPDATE spip_paquets SET version_base='0.0.0' WHERE prefixe='LINKEDIN_POST';
```

3. Si nécessaire, créer manuellement la table et index:

```sql
CREATE TABLE spip_linkedin_posts (
  id_linkedin_post BIGINT PRIMARY KEY NOT NULL,
  url VARCHAR(255) DEFAULT '' NOT NULL,
  id_article BIGINT DEFAULT 0 NOT NULL,
  titre TEXT DEFAULT '' NOT NULL,
  resume TEXT DEFAULT '' NOT NULL,
  texte LONGTEXT DEFAULT '' NOT NULL,
  auteur_post VARCHAR(255) DEFAULT '' NOT NULL,
  image_url VARCHAR(255) DEFAULT '' NOT NULL,
  id_document BIGINT DEFAULT 0 NOT NULL,
  date_post DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
  date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
  maj TIMESTAMP,
  statut VARCHAR(10) DEFAULT 'prepa' NOT NULL
);

CREATE INDEX idx_url ON spip_linkedin_posts(url);
CREATE INDEX idx_id_article ON spip_linkedin_posts(id_article);
CREATE INDEX idx_statut ON spip_linkedin_posts(statut);
```

### 10.3 `saisies` introuvable ou non référencé via SVP

Symptômes observés:
- `plugins:activer saisies` retourne "préfixe introuvable"
- `plugins:svp:telecharger saisies` retourne "Le plugin saisies n'est pas référencé"
- l'activation de `linkedin_post` échoue sur la dépendance SAISIES

Correctif:
1. Vérifier si `saisies` existe déjà en local, par exemple dans `{SPIP_ROOT}/plugins/auto/saisies`.
2. Exposer le dossier `auto` au SPIP de test:

```bash
ln -sfn {SPIP_ROOT}/plugins/auto \
   {SPIP_ROOT}/plugins/linkedin_post/vendor/spip/spip/plugins/auto
```

3. Relancer l'activation:

```bash
cd {SPIP_ROOT}/plugins/linkedin_post/vendor/spip/spip
/src/spip-cli/bin/spip plugins:activer saisies
/src/spip-cli/bin/spip plugins:activer linkedin_post
```

4. Vérifier l'état effectif, qui fait foi:

```bash
/src/spip-cli/bin/spip plugins:lister | grep -Ei 'saisies|linkedin_post'
```

## 11) Résultat attendu final

Quand l'environnement est correctement préparé:
1. composer tests-unit passe
2. composer tests-integration passe
3. la commande phpunit integration avec bootstrap dédié passe
4. le plugin linkedin_post est activable dans le SPIP de test

---

Ce guide est destiné à un agent AI qui doit pouvoir reconstituer l'environnement de test sans hypothèses implicites.