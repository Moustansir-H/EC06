# SkillHub

SkillHub est une plateforme collaborative de mise en relation entre des apprenants en reconversion et des formateurs indépendants. Le projet couvre deux services principaux :

- un back-end Laravel qui expose l’API métier, gère les formations, les ateliers, les inscriptions et les journaux d’activité ;
- un service d’authentification Spring Boot qui gère l’inscription, la connexion SSO, l’émission des jetons et la synchronisation des utilisateurs avec le back-end.

Le dépôt contient aussi une interface front-end React utilisée pendant le développement, mais la consigne de remise demandée par l’établissement concerne principalement le projet Laravel + Spring Boot, la chaîne Docker et les preuves de fonctionnement.

## Objectif du projet

L’objectif est de fournir une plateforme complète et cohérente où :

- un utilisateur crée son compte via le service d’authentification ;
- le compte est synchronisé côté Laravel ;
- l’utilisateur se connecte avec une preuve SSO ;
- les routes protégées du back-end vérifient le jeton fourni par le service d’authentification ;
- les formations, ateliers, inscriptions et logs peuvent être consultés et manipulés via l’API.

## Architecture

Le projet est organisé autour de trois briques techniques.

### 1. Service d’authentification

Dossier : `skillhub-auth/auth`

Technologie : Spring Boot 3, Java 17, MySQL

Rôle :

- enregistrer les utilisateurs ;
- valider la connexion par SSO ;
- émettre un jeton d’accès ;
- exposer l’endpoint `/api/me` ;
- synchroniser l’utilisateur vers le back-end Laravel lors de l’inscription.

### 2. Back-end métier

Dossier : `skillhub-back`

Technologie : Laravel 12, PHP 8.2, MySQL, MongoDB pour les logs

Rôle :

- gérer les formations, catégories et leçons ;
- exposer les routes publiques et privées de l’API ;
- vérifier le jeton via le service d’authentification ;
- enregistrer les activités et les inscriptions.

### 3. Interface front-end

Dossier : `skillhub-front`

Technologie : React + Vite

Rôle :

- interface de démonstration et de navigation ;
- appels vers les services d’API ;
- tests front-end.

Remarque : pour la remise finale, cette partie ne doit pas être incluse dans l’archive si la consigne du jury ne demande que le projet Laravel + Spring Boot.

## Prérequis

Pour lancer le projet en local, il faut au minimum :

- Docker ;
- Docker Compose ;
- Git.

Pour exécuter les tests hors conteneur, il faut aussi :

- PHP 8.2 ou supérieur ;
- Composer ;
- Java 17 ;
- Maven.

## Démarrage avec Docker

La stack Docker attendue pour la remise contient uniquement les services utiles au projet back-end :

- MySQL ;
- le service d’authentification Spring Boot ;
- le back-end Laravel.

Lancer la stack :

```bash
docker compose up --build
```

Arrêter la stack :

```bash
docker compose down
```

### Services exposés

- API Laravel : `http://localhost:8000`
- Service auth : `http://localhost:8080`
- MySQL : `localhost:3306`

## Variables d’environnement

Le fichier `.env.example` contient les variables nécessaires.

### Variables utilisées par Laravel

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `AUTH_SERVICE_URL`
- `AUTH_INTERNAL_SYNC_SECRET`
- `APP_URL`

### Variables utilisées par Spring Boot

- `AUTH_DB_URL`
- `AUTH_DB_USERNAME`
- `AUTH_DB_PASSWORD`
- `BACKEND_API_URL`
- `AUTH_INTERNAL_SYNC_SECRET`
- `APP_MASTER_KEY`

### Variables front-end

- `VITE_API_URL`
- `VITE_AUTH_API_URL`

Ces variables ne sont utiles que si l’interface React est lancée localement. Elles ne sont pas indispensables à la remise finale si le front n’est pas demandé.

## Tests

### Back-end Laravel

```bash
php artisan test
```

Dans Docker :

```bash
docker compose exec backend php artisan test
```

### Service Spring Boot

```bash
cd skillhub-auth/auth
mvn clean test
```

### Front-end React

```bash
cd skillhub-front
npm test -- --run
```

## Qualité et analyse statique

Le projet est configuré pour l’analyse Sonar via le fichier `sonar-project.properties`.

Le périmètre d’analyse retenu couvre :

- `skillhub-back/app`
- `skillhub-back/routes`
- `skillhub-back/bootstrap`
- `skillhub-back/config`
- `skillhub-back/database`
- `skillhub-auth/auth/src/main/java`

Les tests et rapports de couverture attendus sont configurés pour Laravel et Spring Boot.

## Intégration continue

La pipeline GitHub Actions se trouve dans `.github/workflows/ci.yml`.

Elle exécute :

- les tests Laravel avec couverture ;
- les tests Spring Boot avec couverture JaCoCo ;
- l’analyse SonarCloud après les tests.

## Contenu demandé pour la remise

L’archive finale doit respecter la consigne suivante :

- projet complet Laravel + Spring Boot ;
- dossier `.git` ;
- `Dockerfile` pour Laravel ;
- `Dockerfile` pour Spring Boot ;
- `docker-compose.yml` ;
- `.env.example` ;
- `.github/workflows/ci.yml` ;
- `sonar-project.properties` ;
- `README.md` ;
- captures d’écran du `docker compose` fonctionnel ;
- captures d’écran de la pipeline CI/CD réussie ;
- captures d’écran de SonarCloud ;
- captures d’écran de l’authentification SSO.

Ne pas inclure dans l’archive :

- `vendor/` ;
- `target/`.

## Nom de l’archive

L’archive attendue doit suivre le format :

```text
EC06_NumCandidat.zip
```

## Organisation du dépôt

```text
skillhub/
├── skillhub-auth/
│   └── auth/
│       ├── Dockerfile
│       ├── src/
│       └── pom.xml
├── skillhub-back/
│   ├── Dockerfile
│   ├── app/
│   ├── routes/
│   ├── database/
│   └── composer.json
├── skillhub-front/
│   └── ...
├── .github/
│   └── workflows/
│       └── ci.yml
├── docker-compose.yml
├── sonar-project.properties
├── .env.example
└── README.md
```

## Points à retenir

- Le back-end Laravel et le service Spring Boot sont les briques principales à remettre.
- Le front React peut rester présent dans le dépôt de travail, mais il ne doit pas polluer la remise finale si la consigne ne le demande pas.
- Les dossiers générés `vendor/` et `target/` doivent être exclus de l’archive.
- La stack Docker doit démarrer sans dépendre d’une interface front-end.
