# Renault21_2025

# Application Symfony pour la communauté Renault 21 (Turbo / Turbo Diesel) permettant de publier des annonces de pièces, écrire des articles, échanger des messages et organiser les contenus par rôles (guest/admin).

**Fonctionnalités**
- Annonces et articles: création, modification, suppression, upload d’images
- Messagerie entre utilisateurs
- Rôles et sécurité: `guest`, `admin`, CSRF sur formulaires
- Interface responsive (Bootstrap) + améliorations CSS
- Menu burger accessible (ARIA + clavier)

**Stack technique**
- Symfony 7.2, Doctrine ORM, Twig
- Bootstrap (via CDN)
- PHP ≥ 8.2, Composer

## Prérequis
- PHP 8.2+ et Composer
- MySQL/MariaDB
- Git (pour cloner le projet)

## Installation
```bash
git clone https://github.com/ton-utilisateur/Renault21_2025.git
cd Renault21_2025
composer install
```

## Configuration (env)
Créez un fichier `.env.local` et renseignez la base de données:
```env
APP_ENV=dev
APP_DEBUG=1
DATABASE_URL="mysql://user:password@127.0.0.1:3306/renault21_2025"
```

## Base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## Lancer en local
Avec Symfony CLI (si installé):
```bash
symfony server:start -d
```
Sans Symfony CLI:
```bash
php -S 127.0.0.1:8000 -t public
```

## Tests
```bash
php bin/phpunit
```

## CSS et responsive
- Développement: `public/asset/css/styles.css` + `public/asset/css/responsive.css`
- Minification (optionnelle): script simple pour concaténer/minifier en `styles.min.css`
```bash
php scripts/build_css.php
```
Note: Le chargement conditionnel du CSS minifié en prod peut être activé plus tard dans les templates de base.

## Déploiement (checklist)
```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php scripts/build_css.php
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console doctrine:migrations:migrate --no-interaction
```
Configurer le serveur web pour pointer sur le dossier `public/` et vérifier les droits sur `public/asset/Uploads/` si upload.

## Structure
- `src/` code applicatif (Controllers, Entities, Services)
- `templates/` vues Twig (guest/admin)
- `public/asset/css/` styles (`styles.css`, `responsive.css`)
- `scripts/build_css.php` script de minification CSS
- `config/` configuration Symfony
- `migrations/` migrations Doctrine

## Accessibilité
- Burger: attributs ARIA (`aria-controls`, `aria-expanded`) et support clavier (Entrée/Espace). Styles mobiles dans `public/asset/css/responsive.css`.

## Upload d’images
- Annonces (guest): `public/uploads/images`
- Annonces (admin): `public/Uploads/annonces`
Paramétrés dans `config/services.yaml`.

## Routes (aperçu rapide)
- Espace admin protégé par `ROLE_ADMIN`
- Pages guest: accueil, annonces, catégories, pièces, articles, profil, messages

## Documentation
- Symfony: https://symfony.com/doc
- Doctrine: https://www.doctrine-project.org/projects/doctrine-orm/en/current/index.html
- Bootstrap: https://getbootstrap.com/docs

## Architecture
- Contrôleurs: logique de routes et rendu Twig (guest/admin) dans [src/Controller](src/Controller)
- Entités/ORM: mapping Doctrine dans [src/Entity](src/Entity) et requêtes dans [src/Repository](src/Repository)
- Formulaires: définitions dans [src/Form](src/Form)
- Sécurité: configuration rôles et authentification dans [src/Security](src/Security) et [config/packages/security.yaml](config/packages/security.yaml)
- Services: helpers (ex: upload) dans [src/Service](src/Service)
- Vues: templates Twig dans [templates](templates)
- Config: YAML/attributes dans [config](config)

## Entités (aperçu)
- `User`: comptes, rôles, identifiants (local + OAuth)
- `Piece`/`Annonce`: contenu, prix, images, auteur, dates
- `Category`: catégorisation des pièces/annonces
- `Message`: messagerie utilisateur ↔ utilisateur
- `Article`: contenu éditorial (Panthéon)

## Configuration OAuth (Google/Facebook)
- Bundle: KNP OAuth2 Client (voir [config/packages/knpu_oauth2_client.yaml](config/packages/knpu_oauth2_client.yaml))
- Variables env à définir (exemples):
```env
GOOGLE_CLIENT_ID=xxx
GOOGLE_CLIENT_SECRET=xxx
FACEBOOK_CLIENT_ID=xxx
FACEBOOK_CLIENT_SECRET=xxx
```
- Créez des applications sur Google/Facebook, configurez URL de callback selon vos routes (ex: `/connect/google/check`, `/connect/facebook/check`).

## Routes (exemples)
- Guest: accueil, annonces, catégories, pièces, articles, profil, messages (voir [templates/guest/base.html.twig](templates/guest/base.html.twig))
- Admin: dashboard, gestion annonces, utilisateurs, catégories, pièces, messages, articles (voir [templates/admin/base.html.twig](templates/admin/base.html.twig))
- Connexion/Inscription: pages harmonisées visuellement (voir [templates/guest/user-connexion.html.twig](templates/guest/user-connexion.html.twig) et [templates/guest/user-inscription.html.twig](templates/guest/user-inscription.html.twig))

## Images & Uploads
- Guest annonces: `public/uploads/images`
- Admin annonces: `public/Uploads/annonces`
- Paramétrage: [config/services.yaml](config/services.yaml)

## Accessibilité & Responsive
- Burger accessible: `aria-controls`, `aria-expanded` + gestion clavier (Entrée/Espace) dans [templates/guest/base.html.twig](templates/guest/base.html.twig) et [templates/admin/base.html.twig](templates/admin/base.html.twig)
- Styles responsives: [public/asset/css/responsive.css](public/asset/css/responsive.css)
- Harmonisation formulaires mobile: pleine largeur/colonnes empilées

## Harmonisation Connexion/Inscription (UI)
- Wrapper commun: les champs sont enveloppés dans `.user-inscription-form` pour une largeur intérieure cohérente sur les deux pages.
- Largeurs: `form` en 100% et `.user-inscription-form` en 60% afin d’éviter l’effet de "zoom arrière" sur la page de connexion.
- Images: chevauchement harmonisé via largeur portée à ~150% sur connexion et inscription.
- Spacing accueil: `.container2` utilise `justify-content: space-between` sur desktop, et une pile en colonne avec `gap` sur mobile.

## Guide CSS (sans SCSS, sans npm)
- Fichiers: modifier [public/asset/css/styles.css](public/asset/css/styles.css) pour le style principal et [public/asset/css/responsive.css](public/asset/css/responsive.css) pour les overrides mobiles.
- Ordre de chargement: `responsive.css` doit être chargé après `styles.css`.
- Breakpoints de référence: 576px, 768px, 992px (mobile → tablette → desktop).
- Burger: styles et focus visibles, avec ARIA et support clavier.

## Workflow Git rapide
Exemple de message de commit:
- "UI: harmoniser connexion/inscription (wrapper + largeur), agrandir légèrement les images, ajuster responsive et spacing (.container2)"

Commandes courantes:
```bash
git add .
git commit -m "UI: harmoniser connexion/inscription (wrapper + largeur), agrandir légèrement les images, ajuster responsive et spacing (.container2)"
git push origin master
```

## Workflow de développement
1. Créer entités: `php bin/console make:entity`
2. Migrations: `php bin/console make:migration` puis `php bin/console doctrine:migrations:migrate`
3. Contrôleurs/vues: `php bin/console make:controller`
4. Tests: `php bin/phpunit`
5. Serveur dev: `symfony server:start -d` (ou PHP builtin)

## Build CSS (optionnel)
- Dév: modifier [public/asset/css/styles.css](public/asset/css/styles.css) et [public/asset/css/responsive.css](public/asset/css/responsive.css)
- Minifier: concatène + compresse via [scripts/build_css.php](scripts/build_css.php)
```bash
php scripts/build_css.php
```
- Chargement minifié en prod: peut être activé dans les templates de base (au besoin).

## Déploiement (détaillé)
1. Dépendances:
```bash
composer install --no-dev --prefer-dist --optimize-autoloader
```
2. Assets CSS (optionnel):
```bash
php scripts/build_css.php
```
3. Cache:
```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```
4. DB:
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```
5. Serveur Web: pointer vers `public/` et vérifier permissions d’écriture (uploads).

## Dépannage
- 404 routes: vérifier annotations/attributes et [config/routes.yaml](config/routes.yaml)
- DB: vérifier `DATABASE_URL` dans `.env.local`, migrations à jour
- Cache: vider `var/cache/` ou `php bin/console cache:clear`
- Upload: vérifier droits sur `public/uploads/images` et `public/Uploads/annonces`
- CSS non appliqué: forcer refresh (Ctrl+F5), vérifier ordre de chargement (`responsive.css` après `styles.css`)

## Contribuer
- Branches par fonctionnalité
- Messages de commit clairs (scope: backend/frontend, feat/fix/chore)
- PR avec description et captures si UI 



