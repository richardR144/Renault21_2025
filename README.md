# Renault21_2025

# Application web Symfony pour la gestion d’annonces de pièces et d’articles autour de la Renault 21 TD. Ce site a été conçue pour que des pationnés afin de réabiliter des Renault 21 Turbo et Turbo Diesel, échanger, acheter des pièces sur ces deux modèles, s'envoyer des messages.

# 1.Cloner le projet
# git clone https://github.com/ton-utilisateur/Renault21_2025.git
# cd Renault21_2025

# Dans le terminal, j'installe les dépendances PHP avec composer
# - composer install
# 1 composer create project symfony skeleton Renault21_2025
# 2 composer require symfony/apache-pack si mal installer composer remove symfony/apache-pack
# 3 composer require webapp
# 4 configurer le .env (copier coller) en .env.local ligne 27 root root et localhost en 3306, DATABASE_URL="mysql://user:password@127.0.0.1:3
# Créer la BDD
# php bin/console doctrine:database:create
# php bin/console doctrine:migrations
# php bin/console doctrine:migration:migrate

# Configuration
# Les images uploadées sont stockées dans :
# public/uploads/images pour les annonces guest
# public/Uploads/annonces pour les annonces admin
# Les paramètres de dossiers sont définis dans config/services.yaml
# Les rôles utilisateurs sont gérés (admin, guest)


# Installer les dépendances de bases
# composer require symfony/asset, twig/twig, maker bundle, --dev, anotations (router), api, doctrine, configurer le fichier htaccess, Générer un controller php bin/console make controller


# Dépendances principales
# Symfony (framework PHP)
# Doctrine ORM (base de données)
# Bootstrap (front-end)
# Twig (templates)
# Composer (gestion PHP)
# JS 

# Commandes utiles
# Créer un controller
# php bin/console make:controller
# Créer une entité
# php bin/console make:entity
# Lancer les migrations
# php bin/console doctrine:migrations:migrate
# Lancer les tests
# php bin/phpunit


# Déploiement
# Uploader le projet sur le serveur
# Installer les dépendances avec Composer
# Configurer .env pour la production
# Lancer les migrations
# Configurer le serveur web pour pointer sur le dossier public

# Documentation
# Symfony Docs
# Bootstrap Docs
# Doctrine Docs

# Fonctionnalités principales
# Gestion des annonces (création, modification, suppression, upload d’images)
# Gestion des articles (création, modification, suppression)
# Séparation des rôles (guest/admin)
# Sécurité CSRF sur les formulaires
# Responsive design avec Bootstrap
# Upload et affichage des images
# Suppression physique et logique des images
# Interface utilisateur moderne et claire



# Accès et routes admin
# Accès à l'espace d'administration
# URL principale admin
# /admin
# Connexion admin
# /admin/login
# Formulaire de connexion sécurisé, accès réservé aux utilisateurs avec le rôle ROLE_ADMIN
# Liste des annonces admin
# /admin/annonces
# Créer une annonce
# /admin/annonces/create
# Modifier une annonce
# /admin/annonces/{id}/update
# Supprimer une annonce
# /admin/annonces/{id}/delete
# Voir une annonce
# /admin/annonces/{id}
# idem pour article

# Sécurité
# Toutes les routes admin sont protégées par le rôle ROLE_ADMIN. Les utilisateurs non authentifiés ou sans le rôle admin sont redirigés vers la page de connexion. Idem pour moderator et user.

