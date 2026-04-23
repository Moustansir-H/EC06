# SkillHub - Backend

Plateforme collaborative mettant en relation formateurs et apprenants.
Le backend gere les operations metier autour des formations.
L'authentification est deleguee au service `skillhub-auth` (Spring Boot).

## Technologies utilisees

- Laravel 12
- MySQL
- PHPUnit pour les tests

## Prerequis

- PHP 8.2+
- Composer
- MySQL

## Installation et lancement

Depuis le dossier `skillhub-back` :

```bash
# 1) Installer les dependances
composer install

# 2) Copier l'environnement
cp .env.example .env

# 3) Generer la cle d'application
php artisan key:generate

# 4) Configurer la base de donnees dans .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=skillhub
# DB_USERNAME=root
# DB_PASSWORD=
# AUTH_SERVICE_URL=http://127.0.0.1:8080/api
# AUTH_INTERNAL_SYNC_SECRET=skillhub-sync-secret

# 5) Lancer les migrations
php artisan migrate

# 6) Demarrer le serveur
php artisan serve
```

Le backend est accessible sur `http://127.0.0.1:8000`.

## Tests

```bash
php artisan test
```

Le fichier `.env.testing` utilise SQLite en memoire afin de ne pas impacter la base principale.

## Authentification

L'API `skillhub-back` ne genere plus de token.
La validation du token est faite en appelant `skillhub-auth` via `AUTH_SERVICE_URL`.

1. Se connecter via `skillhub-auth` (`POST /api/auth/login`) pour obtenir un token.
2. Inclure le token dans chaque requete : `Authorization: Bearer <token>`.
3. Le backend verifie ce token aupres de `skillhub-auth` via `GET /api/me`.

## Comptes formateur (developpement)

| Email                  | Mot de passe | Role      |
| ---------------------- | ------------ | --------- |
| formateur@test.com     | 123456       | FORMATEUR |
| emma.laurent@gmail.com | 123456       | FORMATEUR |
