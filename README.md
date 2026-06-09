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

## Mises à jour (mars 2026)

### 1) Correction route de connexion sur la page annonce
- Erreur corrigée: `Unable to generate a URL for the named route "app_login"`.
- Cause: la route existante dans le projet est `connexion` (et non `app_login`).
- Correctif appliqué dans [templates/guest/annonces/annonce-show.html.twig](templates/guest/annonces/annonce-show.html.twig).

### 2) Amélioration UX du tableau catégorie
- Tableau de [templates/guest/category/details-category.html.twig](templates/guest/category/details-category.html.twig) rendu plus lisible.
- Ajout de classes Bootstrap: `table table-bordered table-hover align-middle`.
- Espacement cellules (`py-3 px-3`) pour aérer **Nom / Description / Prix**.
- Lignes cliquables:
	- utilisateur connecté → redirection vers `details-piece`
	- utilisateur non connecté → redirection vers `connexion`

### 3) Bouton “Contacter le vendeur” sur la page annonce
- Sur [templates/guest/annonces/annonce-show.html.twig](templates/guest/annonces/annonce-show.html.twig), ajout d’un bouton direct “💬 Contacter le vendeur”.
- Le bouton ouvre la création de message avec vendeur pré-sélectionné via `create-message?receiver={id}`.
- Prise en charge du paramètre `receiver` dans [src/Controller/Guest/MessagesController.php](src/Controller/Guest/MessagesController.php).
- Pré-sélection du destinataire dans [templates/guest/messages/create-message.html.twig](templates/guest/messages/create-message.html.twig).

### 4) Renforcement sécurité: modifications réservées au propriétaire
- Objectif: empêcher qu’un utilisateur modifie/supprime les contenus d’un autre utilisateur depuis les routes guest.

#### Pièces (guest)
- Vérification de propriété ajoutée dans [src/Controller/Guest/PieceController.php](src/Controller/Guest/PieceController.php) pour:
	- `update-piece/{id}`
	- `delete-piece/{id}`
- Si la pièce n’appartient pas à l’utilisateur connecté: action refusée + message flash d’erreur + redirection.
- Correction importante: suppression du `setUser($user)` pendant la modification pour éviter une prise de propriété involontaire.
- Côté interface, le bouton “Modifier” n’apparaît plus pour les pièces des autres utilisateurs dans [templates/guest/pieces/list-pieces.html.twig](templates/guest/pieces/list-pieces.html.twig).

#### Annonces (guest)
- Authentification désormais imposée (`ROLE_USER`) pour:
	- création
	- modification
	- suppression
- Contrôles “propriétaire uniquement” conservés sur update/delete dans [src/Controller/Guest/AnnonceController.php](src/Controller/Guest/AnnonceController.php).

#### Messages (guest)
- Déjà sécurisé: un utilisateur ne peut modifier que ses messages envoyés et ne peut supprimer/lire que ses messages autorisés dans [src/Controller/Guest/MessagesController.php](src/Controller/Guest/MessagesController.php).

#### Catégories
- Côté guest, pas de routes de modification/suppression de catégorie (liste + détail uniquement) dans [src/Controller/Guest/CategoryController.php](src/Controller/Guest/CategoryController.php).
- La gestion complète des catégories reste côté admin.

### 5) Harmonisation des formulaires “pièces” (profil)
- Objectif: rendre les formulaires **Créer une pièce** et **Modifier une pièce** visuellement cohérents avec le reste de l’application.
- Les images de fond sont conservées (aucun changement sur le principe visuel `bg-insert-piece` / `bg-update-piece`).

#### Templates mis à jour
- [templates/guest/pieces/update-piece.html.twig](templates/guest/pieces/update-piece.html.twig)
- [templates/guest/pieces/insertPiece.html.twig](templates/guest/pieces/insertPiece.html.twig)

#### Améliorations appliquées
- Formulaires réécrits avec une structure homogène: labels clairs, champs espacés, bouton principal en bas.
- Utilisation des classes Bootstrap (`form-control`, `form-select`, `btn btn-primary`) pour un rendu plus propre.
- Bouton retour harmonisé sur les deux pages.

#### CSS ciblé
- Ajout de styles dédiés dans [public/asset/css/profil.css](public/asset/css/profil.css):
	- `.piece-form-card` pour une largeur/présentation cohérente
	- meilleure lisibilité des labels/champs
	- alignement des options radio `Vente / Échange`

### 6) Suppression directe des messages reçus depuis le profil
- Ajout d’un bouton `Supprimer ce message` sur chaque carte de message reçu dans [templates/guest/profil.html.twig](templates/guest/profil.html.twig).
- UX: suppression directe avec popup de confirmation en français (`Supprimer ce message ?`).
- Sécurité renforcée: suppression en `POST` + token CSRF (`delete_message_{id}`).
- Validation CSRF côté backend dans [src/Controller/Guest/MessagesController.php](src/Controller/Guest/MessagesController.php).
- Durcissement supplémentaire: route `delete-message` en `POST` uniquement (plus de suppression via `GET`).

### 7) Stabilisation du flux admin des pièces (juin 2026)
- Objectif: fiabiliser la création/modification des pièces côté admin et supprimer les incohérences fonctionnelles.

#### Backend admin
- Correctifs appliqués dans [src/Controller/Admin/AdminPieceController.php](src/Controller/Admin/AdminPieceController.php):
	- Vérification explicite de l’existence de la pièce en modification (`admin-update-piece/{id}`) avec redirection propre si introuvable.
	- Normalisation du prix (`1500`, `1500,50`, `1500.50`) via une méthode dédiée pour éviter les valeurs invalides.
	- Règle métier alignée: si type `Vente`, le prix est obligatoire; si type `Échange`, le prix peut être vide.
	- Validation de base sur `name` et `description` non vides avec message flash clair.

#### Template admin de modification
- Mise à jour de [templates/admin/piece/update-piece.html.twig](templates/admin/piece/update-piece.html.twig):
	- Champ `Type` (`Vente` / `Échange`) réintroduit dans le formulaire d’édition.
	- Champ `Prix` rendu optionnel côté HTML pour rester cohérent avec la logique métier backend.

#### Résultat
- Le type `Vente/Échange` n’est plus perdu lors d’une modification admin.
- Les erreurs de saisie prix sont remontées proprement à l’utilisateur.
- Le flux admin pièces est désormais cohérent entre formulaire, validation et persistance.

### 8) Renforcement des tests de régression admin (juin 2026)
- Objectif: verrouiller les correctifs récents sur les flux admin critiques et éviter les régressions silencieuses.

#### Validation annonces
- Harmonisation de la règle de longueur de description dans [src/Controller/Admin/AdminAnnonceController.php](src/Controller/Admin/AdminAnnonceController.php) avec le formulaire [src/Form/AnnonceTypeForm.php](src/Form/AnnonceTypeForm.php).
- Règle désormais cohérente côté admin: description entre `10` et `10000` caractères.

#### Tests ajoutés
- Mise à jour de [tests/AdminSecurityTest.php](tests/AdminSecurityTest.php) avec de nouveaux scénarios ciblés:
	- création d’une pièce admin en mode `Échange` sans prix autorisée
	- création d’une pièce admin en mode `Vente` sans prix refusée
	- création d’une annonce admin avec description > `1000` caractères autorisée

#### Validation exécutée
- Exécution confirmée en local:
	- `php bin/phpunit --filter AdminSecurityTest`
	- résultat: `OK (15 tests, 42 assertions)`

### 9) Deuxième lot de tests admin sur les pièces (juin 2026)
- Objectif: couvrir les cas métier et uploads restés sensibles après stabilisation du CRUD admin des pièces.

#### Tests ajoutés
- Extension de [tests/AdminSecurityTest.php](tests/AdminSecurityTest.php) avec les scénarios suivants:
	- création d’une pièce admin avec image au mauvais MIME refusée
	- modification d’une pièce admin avec image au mauvais MIME refusée
	- bascule d’une pièce admin de `Vente` vers `Échange` sans prix autorisée

#### Résultat validé
- Exécution confirmée en local:
	- `php bin/phpunit --filter AdminSecurityTest`
	- résultat: `OK (18 tests, 50 assertions)`

### 10) Dette technique identifiée: propriétés de Category non camelCase
- Contexte: dans [src/Entity/Category.php](src/Entity/Category.php), les propriétés privées historiques `$Name`, `$Description` et `$Image` ne respectent pas la convention camelCase attendue.
- État actuel: le projet fonctionne correctement car les méthodes publiques (`getName()`, `setName()`, `getDescription()`, `setDescription()`, `getImage()`, `setImage()`) sont cohérentes et utilisées dans le reste du code.
- Décision: ne pas refactorer immédiatement pour éviter un changement ORM inutilement risqué dans Doctrine tant qu’aucun besoin fonctionnel ne l’impose.
- Recommandation future: effectuer ce renommage dans un refactor isolé, avec validation du mapping Doctrine, vérification du schéma et exécution complète des tests après modification.

### 11) Finalisation sécurité guest et tests de non-régression (juin 2026)
- Objectif: clôturer le périmètre guest avec une couverture de tests renforcée sur `pièces`, `messages` et `annonces`.

#### Pièces (guest)
- Extension de [tests/GuestSecurityTest.php](tests/GuestSecurityTest.php):
	- accès modification refusé pour un non-propriétaire
	- création `Échange` sans prix autorisée
	- création `Vente` sans prix refusée
	- création avec image MIME invalide refusée
- Exécution validée:
	- `php bin/phpunit --filter GuestSecurityTest`
	- résultat: `OK (18 tests, 49 assertions)`

#### Messages (guest)
- Extension de [tests/MessageSecurityTest.php](tests/MessageSecurityTest.php):
	- création refusée avec CSRF invalide
	- création autorisée avec CSRF valide
	- modification refusée avec CSRF invalide
	- modification refusée si contenu vide
	- modification autorisée avec payload valide (`updatedAt` vérifié)
- Exécution validée:
	- `php bin/phpunit --filter MessageSecurityTest`
	- résultat: `OK (11 tests, 28 assertions)`

#### Annonces (guest)
- Extension de [tests/AnnonceSecurityTest.php](tests/AnnonceSecurityTest.php):
	- modification propriétaire refusée avec CSRF invalide
	- suppression propriétaire autorisée avec CSRF valide
- Exécution validée:
	- `php bin/phpunit --filter AnnonceSecurityTest`
	- résultat: `OK (11 tests, 34 assertions)`

### 12) Durcissement sécurité modérateur (juin 2026)
- Objectif: fiabiliser les routes et renforcer la non-régression sur les opérations de modération des articles/pièces.

#### Contrôleurs modérateur
- Nettoyage de [src/Controller/Moderator/ModeratorDashboardController.php](src/Controller/Moderator/ModeratorDashboardController.php):
	- suppression des routes dupliquées `moderator-list-articles` et `moderator-list-pieces`
	- conservation de la route dashboard uniquement
- Impact: évite les collisions de définitions de routes et clarifie la responsabilité entre dashboard et CRUD de modération.

#### Tests ajoutés
- Extension de [tests/ModeratorSecurityTest.php](tests/ModeratorSecurityTest.php) avec les scénarios suivants:
	- accès refusé (403) pour un utilisateur connecté sans `ROLE_MODERATOR`
	- accès autorisé (200) aux routes dashboard/listes/update pour un modérateur
	- affichage des flashs d'erreur après CSRF invalide
	- affichage des flashs de succès après mise à jour valide (article/pièce)
	- modification d’article autorisée avec CSRF valide
	- modification de pièce autorisée avec CSRF valide
	- modification de pièce refusée si nom vide
	- modification d’article avec image MIME invalide refusée
	- modification de pièce avec image MIME invalide refusée

#### Résultat validé
- Exécution confirmée en local:
	- `php bin/phpunit tests/ModeratorSecurityTest.php`
	- résultat: `OK (17 tests, 43 assertions)`

#### Validation globale
- Exécution complète confirmée en local:
	- `php bin/phpunit`
	- résultat: `OK (75 tests, 204 assertions)`

## Contribuer
- Branches par fonctionnalité
- Messages de commit clairs (scope: backend/frontend, feat/fix/chore)
- PR avec description et captures si UI 

