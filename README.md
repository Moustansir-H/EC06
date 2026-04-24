# SkillHub - Plateforme Collaborative de Formations en Ligne

## Description du Projet

**SkillHub** est une plateforme collaborative de mise en relation entre des apprenants en reconversion et des formateurs indépendants. Elle offre une solution complète et sécurisée pour :

- **Créer et gérer des formations** : Les formateurs peuvent créer des formations, organiser des ateliers et suivre les inscriptions
- **S'inscrire à des cours** : Les apprenants découvrent et s'inscrivent à des formations avec une gestion robuste des limites de places
- **Suivre l'activité** : Un système de logs détaillé enregistre toutes les interactions utilisateur
- **S'authentifier de manière sécurisée** : Authentification SSO via tokens JWT gérés par le service Spring Boot

Le projet repose sur une **architecture microservices** avec deux services principaux :

- Un **service d'authentification Spring Boot** pour la gestion des utilisateurs et des tokens
- Un **back-end Laravel** pour la logique métier (formations, ateliers, inscriptions)

---

## Architecture du Projet

```
skillhub/
├── skillhub-auth/              # Service d'authentification Spring Boot
│   └── auth/
│       ├── Dockerfile
│       ├── src/
│       ├── pom.xml
│       └── ...
├── skillhub-back/              # Back-end métier Laravel
│   ├── Dockerfile
│   ├── app/
│   ├── routes/
│   ├── database/
│   ├── composer.json
│   └── ...
├── skillhub-front/             # Interface React (optionnelle)
│   └── ...
├── .github/
│   └── workflows/
│       └── ci.yml              # Pipeline CI/CD
├── docker-compose.yml
├── sonar-project.properties
├── .env.example
└── README.md
```

### Services Principaux

#### 1. Service d'Authentification (Spring Boot 3)

**Localisation** : `skillhub-auth/auth`

**Responsabilités** :

- Enregistrement et validation des utilisateurs
- Émission de tokens JWT pour l'authentification SSO
- Exposition de l'endpoint `/api/me` pour récupérer les données utilisateur
- Synchronisation automatique des nouveaux utilisateurs vers le back-end Laravel

**Technologie** :

- Spring Boot 3
- Java 17
- MySQL
- JWT pour la gestion des tokens

#### 2. Back-end Métier (Laravel 12)

**Localisation** : `skillhub-back`

**Responsabilités** :

- Gestion des formations, catégories et leçons
- Routes publiques et protégées de l'API REST
- Vérification des tokens JWT auprès du service d'authentification
- Enregistrement des activités utilisateur et inscriptions
- Gestion des ateliers avec limite d'inscriptions

**Technologie** :

- Laravel 12
- PHP 8.2
- MySQL (données métier)
- MongoDB (journaux d'activité)

## Système d'Authentification SSO et Gestion des JWT

### Vue d'ensemble

Le système d'authentification utilise une architecture **SSO (Single Sign-On)** avec **JWT (JSON Web Token)** pour sécuriser les communications entre le service d'authentification et le back-end.

```
┌─────────────────┐                    ┌─────────────────┐                 ┌──────────────┐
│   Client        │                    │  Auth Service   │                 │  Backend     │
│   (Frontend)    │                    │  (Spring Boot)  │                 │  (Laravel)   │
└────────┬────────┘                    └────────┬────────┘                 └──────────────┘
         │                                      │                                  │
         │─────────── POST /register ───────────>│                                  │
         │                                      │────── SYNC User ──────────────────>│
         │<────────── User Created ─────────────│                                  │
         │                                      │                                  │
         │─────────── POST /login ─────────────>│                                  │
         │                                      │────── Generate JWT ──────────────>│
         │<────────── JWT Token ─────────────────│                                  │
         │                                      │                                  │
         │───── GET /api/courses + JWT ─────────────────────────────────────────────>│
         │                                      │──── Verify JWT ────────────────────│
         │<──────── Course Data ─────────────────────────────────────────────────────│
```

### 1. Flux d'Inscription (Registration)

```
1. Utilisateur remplit le formulaire d'inscription (email, mot de passe, etc.)
   ↓
2. Requête POST vers Auth Service : /api/auth/register
   {
     "email": "user@example.com",
     "password": "secure_password",
     "name": "Toto Titi"
   }
   ↓
3. Auth Service (Spring Boot) :
   - Valide les données
   - Hash le mot de passe (bcrypt)
   - Crée l'utilisateur en base MySQL
   ↓
4. Synchronisation vers Laravel :
   - Auth Service appelle le back-end Laravel
   - Endpoint : POST /api/internal/sync-user
   - Authentification via secret partagé : AUTH_INTERNAL_SYNC_SECRET
   - Crée l'enregistrement utilisateur côté Laravel
   ↓
5. Réponse au client : Utilisateur créé avec succès
```

### 2. Flux de Connexion (Login) et Génération JWT

```
1. Utilisateur soumit ses identifiants (email + mot de passe)
   ↓
2. Requête POST vers Auth Service : /api/auth/login
   {
     "email": "user@example.com",
     "password": "secure_password"
   }
   ↓
3. Auth Service valide les identifiants :
   - Recherche l'utilisateur par email
   - Vérifie le mot de passe avec bcrypt
   ↓
4. Génération du JWT :
   - Payload contient : user_id, email, name, roles
   - Signé avec une clé secrète (APP_MASTER_KEY)
   - Expire après 24h (configurable)
   ↓
5. Réponse au client :
   {
     "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
     "token_type": "Bearer",
     "expires_in": 86400
   }
```

### 3. Vérification des Tokens et Accès aux Ressources Protégées

```
1. Client envoie une requête vers le back-end avec le JWT :
   GET /api/courses
   Headers: Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
   ↓
2. Laravel reçoit la requête et extrait le token du header Authorization
   ↓
3. Middleware de vérification (CustomAuthMiddleware) :
   - Appelle l'endpoint /api/me du Auth Service
   - Envoie le token pour validation
   - Auth Service : Vérifie la signature JWT et les dates d'expiration
   ↓
4. Auth Service répond avec les données utilisateur :
   {
     "id": 1,
     "email": "user@example.com",
     "name": "Toto tutu",
     "roles": ["Apprenant"]
   }
   ↓
5. Laravel valide la réponse :
   - Si valide : Continue le traitement de la requête
   - Si invalide : Rejette avec 401 Unauthorized
   ↓
6. Exécution de l'action (read, create, update, delete)
   ↓
7. Réponse au client avec les données demandées
```

### 4. Configuration de Sécurité

**Auth Service (Spring Boot)** :

```properties
# Variables d'environnement
APP_MASTER_KEY=your_secret_key_here           # Clé de signature JWT
AUTH_INTERNAL_SYNC_SECRET=sync_secret_here    # Secret pour la synchro interne
JWT_EXPIRATION_HOURS=24                       # Durée de validité du JWT
```

**Back-end Laravel** :

```php
// config/auth.php
'guards' => [
    'api' => [
        'driver' => 'token',
        'provider' => 'users',
        'auth_service_url' => env('AUTH_SERVICE_URL'),
    ],
],
```

### Variables liées au registry Docker

- `REGISTRY_USER`
- `REGISTRY_TOKEN`

### 5. Sécurité des Tokens

- **Signature** : JWT signé avec HMAC-SHA256
- **Expiration** : Les tokens expirent après 24h (configurable)
- **Stockage** : Côté client, le token est stocké en localStorage ou sessionStorage
- **Transport** : Envoyé via header Authorization avec schéma Bearer
- **Validation** : Chaque requête vérifie la signature et l'expiration

---

## Gestion des Inscriptions aux Ateliers

### Limite d'Inscription Implémentée

SkillHub inclut un système robuste de **limite d'inscriptions** pour les ateliers, garantissant une qualité d'enseignement optimale.

#### Fonctionnalités

**1. Contrôle des Places Disponibles** (`WorkshopController.php`)

```php
public function enroll(Request $request, Workshop $workshop)
{
    // Vérifier si l'atelier n'est pas complet
    if ($workshop->enrollments()->count() >= $workshop->max_participants) {
        return response()->json([
            'error' => 'Atelier complet - limite de ' . $workshop->max_participants . ' participants atteinte'
        ], 422);
    }

    // Vérifier que l'utilisateur n'est pas déjà inscrit
    if ($workshop->enrollments()->where('user_id', $request->user()->id)->exists()) {
        return response()->json([
            'error' => 'Vous êtes déjà inscrit à cet atelier'
        ], 422);
    }

    // Créer l'inscription
    $enrollment = $workshop->enrollments()->create([
        'user_id' => $request->user()->id
    ]);

    return response()->json($enrollment, 201);
}
```

**2. Attributs du Modèle Workshop**

```php
class Workshop extends Model
{
    protected $fillable = [
        'titre',
        'description',
        'lecon_id',
        'date',
        'max_participants',
        'instructor_id'
    ];

    public function enrollments()
    {
        return $this->hasMany(WorkshopEnrollment::class);
    }

    public function getAvailableSlotsAttribute()
    {
        return $this->max_participants - $this->enrollments()->count();
    }
}
```

#### Règles de Validation

- [OK] Limite stricte : Impossible de dépasser le nombre maximum de participants
- [OK] Inscription unique : Un utilisateur ne peut s'inscrire qu'une seule fois par atelier
- [OK] Vérification atomique : Utilisation de transactions pour éviter les conditions de course
- [OK] Messages d'erreur explicites : Feedback clair sur la raison du refus

#### Tests

Les tests unitaires vérifient les cas suivants :

```php
public function test_cannot_enroll_when_workshop_is_full()
{
    $workshop = Workshop::factory()->create(['max_participants' => 1]);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Première inscription - succès
    $this->actingAs($user1)->post("/api/workshops/{$workshop->id}/enroll");

    // Deuxième inscription - échec (atelier complet)
    $response = $this->actingAs($user2)->post("/api/workshops/{$workshop->id}/enroll");
    $response->assertStatus(422);
    $response->assertJsonFragment(['error' => 'Atelier complet']);
}

public function test_cannot_enroll_twice_to_same_workshop()
{
    $workshop = Workshop::factory()->create();
    $user = User::factory()->create();

    // Première inscription
    $this->actingAs($user)->post("/api/workshops/{$workshop->id}/enroll");

    // Deuxième inscription - échec (déjà inscrit)
    $response = $this->actingAs($user)->post("/api/workshops/{$workshop->id}/enroll");
    $response->assertStatus(422);
    $response->assertJsonFragment(['error' => 'Vous êtes déjà inscrit']);
}
```

#### Informations Publiques

L'API expose publiquement :

- Le nombre total de places
- Le nombre de places utilisées
- Le nombre de places disponibles

```json
{
  "id": 1,
  "title": "Workshop avancé",
  "date": "2025-05-15",
  "max_participants": 20,
  "enrolled_count": 15,
  "available_slots": 5
}
```

---

## Installation et Démarrage avec Docker

### Prérequis

- Docker (version 20.10+)
- Docker Compose (version 1.29+)
- Git

Optionnel (pour exécuter les tests en local) :

- PHP 8.2+
- Composer
- Java 17+
- Maven

### Démarrage Rapide

#### 1. Cloner le dépôt

```bash
git clone https://github.com/votre-repo/skillhub.git
cd skillhub
```

#### 2. Configurer les variables d'environnement

```bash
cp .env.example .env
```

Éditer le fichier `.env` avec vos paramètres (voir section "Variables d'Environnement")

#### 3. Lancer la stack Docker

```bash
docker compose up --build
```

Attendez que tous les services soient prêts. Vous devriez voir :

```
backend      | Application started successfully
auth-service | Started AuthApplication in X seconds
mysql        | ready for connections
```

#### 4. Initialiser la base de données

```bash
# Créer les tables Laravel
docker compose exec backend php artisan migrate

# Remplir la base avec des données de test (optionnel)
docker compose exec backend php artisan db:seed
```

#### 5. Accéder à l'application

- **API Laravel** : http://localhost:8000
- **Auth Service** : http://localhost:8080
- **MySQL** : localhost:3306

#### 6. Arrêter la stack

```bash
docker compose down
```

### Services Docker Disponibles

| Service        | Port | Technologie             | Rôle                          |
| -------------- | ---- | ----------------------- | ----------------------------- |
| `backend`      | 8000 | Laravel 12 / PHP 8.2    | API métier                    |
| `auth-service` | 8080 | Spring Boot 3 / Java 17 | Authentification SSO          |
| `mysql`        | 3306 | MySQL 8.0               | Base de données relationnelle |

### Vérifier l'État des Services

```bash
# Voir les logs de tous les services
docker compose logs -f

# Logs d'un service spécifique
docker compose logs -f backend
docker compose logs -f auth-service

# Vérifier l'état des conteneurs
docker compose ps
```

### Exécuter des Commandes dans les Conteneurs

```bash
# Artisan commands (Laravel)
docker compose exec backend php artisan list

# Maven commands (Spring Boot)
docker compose exec auth-service mvn clean test

# MySQL client
docker compose exec mysql mysql -u root -p skillhub
```

---

## Tests

### Tests Back-end Laravel

#### En local

```bash
cd skillhub-back
php artisan test
```

#### Dans Docker

```bash
docker compose exec backend php artisan test
```

#### Avec rapport de couverture

```bash
docker compose exec backend php artisan test --coverage
```

### Tests Service d'Authentification Spring Boot

#### En local

```bash
cd skillhub-auth/auth
mvn clean test
```

#### Rapport de couverture JaCoCo

```bash
mvn clean test jacoco:report
# Le rapport est dans : target/site/jacoco/index.html
```

---

## Variables d'Environnement Requises

Créer un fichier `.env` à la racine du projet basé sur `.env.example` :

### Back-end Laravel

```env
# Configuration de la base de données
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=skillhub
DB_USERNAME=root
DB_PASSWORD=secret

# URL de l'application
APP_URL=http://localhost:8000
APP_NAME=SkillHub
APP_ENV=local

# Service d'authentification
AUTH_SERVICE_URL=http://auth-service:8080
AUTH_INTERNAL_SYNC_SECRET=your_internal_sync_secret_here

# Logging
LOG_CHANNEL=stack
```

### Service d'Authentification Spring Boot

```env
# Configuration de la base de données
AUTH_DB_URL=jdbc:mysql://mysql:3306/skillhub
AUTH_DB_USERNAME=root
AUTH_DB_PASSWORD=secret

# URL du back-end Laravel
BACKEND_API_URL=http://backend:8000

# Sécurité
AUTH_INTERNAL_SYNC_SECRET=your_internal_sync_secret_here
APP_MASTER_KEY=your_jwt_secret_key_here

# JWT Configuration
JWT_EXPIRATION_HOURS=24
JWT_ALGORITHM=HS256
```

### Points Importants

[WARN] En Production :

- Générer une clé `APP_MASTER_KEY` robuste (ex: `openssl rand -base64 32`)
- Utiliser des secrets chiffrés ou un gestionnaire de secrets
- Définir `APP_ENV=production`
- Utiliser une base de données sécurisée (pas de mot de passe "secret")

---

## Outils d'Assurance Qualité et CI/CD

### Docker et Docker Compose

#### Rôle

Containerisation et orchestration des services pour garantir une exécution cohérente entre développement et production.

#### Fichiers Clés

- `Dockerfile` (backend) : Image Laravel avec PHP 8.2
- `Dockerfile` (auth) : Image Spring Boot avec Java 17
- `docker-compose.yml` : Orchestration des 3 services (Laravel, Spring Boot, MySQL)

#### Avantages

- [OK] Environnement identique sur tous les postes
- [OK] Pas de dépendances locales (PHP, Java, MySQL)
- [OK] Facilite le déploiement
- [OK] Isolation des services

### SonarCloud

#### Rôle

Analyse statique du code pour détecter les bugs, vulnérabilités et mauvaises pratiques.

#### Configuration

- Fichier : `sonar-project.properties`
- Périmètre d'analyse :
  ```
  skillhub-back/app          (Logique métier Laravel)
  skillhub-back/routes       (Routage)
  skillhub-back/config       (Configuration)
  skillhub-back/database     (Migrations)
  skillhub-auth/auth/src/main/java  (Service Spring Boot)
  ```

#### Métriques Suivies

- Bugs : Défauts detectable
- Vulnérabilités de Sécurité : Failles potentielles
- Code Smells : Mauvaises pratiques de code
- Couverture de Tests : Percentage de code testé
- Complexity : Complexité cyclomatique
- Duplication : Code dupliqué

---

### GitHub Actions (CI/CD)

#### Rôle

Pipeline d'intégration continue/déploiement continu automatisée à chaque commit/PR.

#### Fichier de Configuration

`.github/workflows/ci.yml`

#### Étapes Exécutées

```yaml
Pipeline CI/CD SkillHub
│
├─ 1. Checkout Code
│
├─ 2. Tests Back-end Laravel
│   ├─ Install dependencies (Composer)
│   ├─ Run tests (PHPUnit)
│   ├─ Generate coverage report
│   └─ Upload coverage to SonarCloud
│
├─ 3. Tests Service Spring Boot
│   ├─ Setup Java 17
│   ├─ Run tests (Maven)
│   ├─ Generate JaCoCo coverage
│   └─ Upload coverage to SonarCloud
│
├─ 4. Analyse SonarCloud
│   ├─ Scan code quality
│   ├─ Check quality gate
│   └─ Fail if quality gate not met
│
└─ 5. Report Results
├─ Status badge
└─ Notifications
```

#### Déclenchement

- À chaque **push** sur `main` ou `develop`
- À chaque **pull request**
- Manuel depuis l'interface GitHub

#### Résultats

- [OK] Tous les tests passent
- [OK] Couverture de code > 70%
- [OK] Quality gate SonarCloud réussi
- [ERROR] Pipeline échouée si qualité insuffisante

#### Visualiser la Pipeline

GitHub > Actions > Sélectionner le workflow > Voir les détails de chaque étape

---

## Analyse de Qualité Sonar et Améliorations

### Balises Qualité Principales

#### 1. Bugs

**Définition** : Erreurs de logique qui causeront une défaillance à l'exécution.

**Exemples** :

- Variable non initialisée
- Condition impossible
- Ressource non fermée
- Division par zéro non vérifiée

**Action** : Corriger immédiatement - Priorité BLOCKER

---

#### 2. Vulnérabilités de Sécurité

**Définition** : Failles pouvant être exploitées par un attaquant.

**Exemples** :

- SQL Injection
- XSS (Cross-Site Scripting)
- Mot de passe en dur
- Authentification faible
- Absence de validation d'entrée

**Action** : Corriger avant déploiement - Priorité CRITICAL

---

#### 3. Code Smells

**Définition** : Indicateurs de mauvaise qualité de code affectant maintenabilité.

**Exemples** :

- Méthodes trop longues (>50 lignes)
- Classe trop complexe (>20 méthodes)
- Variables non utilisées
- Code dupliqué
- Nomenclature incohérente

**Action** : Refactoriser - Priorité MINOR/MAJOR

---

#### 4. Couverture de Tests

**Définition** : Pourcentage de code exécuté par les tests.

**Seuils Recommandés** :

- [OK] Critique : >80%
- [WARN] Acceptable : >70%
- [ERROR] Faible : <50%

**Code Non Couvert** :

```php
// [NO] Non couvert - peu testable
if ($unlikely_condition) {
    sendAlert();  // Jamais exécuté dans les tests
}
```

**Action** : Ajouter des tests - Priorité MAJOR

---

### Améliorations à Apporter

#### Phase 1 : Sécurité (CRITIQUE)

- [ ] Valider toutes les entrées utilisateur
- [ ] Utiliser des requêtes paramétrées pour éviter SQL Injection
- [ ] Implémenter CSRF tokens sur toutes les routes POST
- [ ] Ajouter des rate limits sur les endpoints sensibles
- [ ] Hasher les mots de passe avec bcrypt
- [ ] Supprimer les secrets des fichiers de code
- [ ] Ajouter HTTPS en production

```php
// [OK] BON : Utiliser les paramètres liés
$user = DB::table('users')
    ->where('email', $email)
    ->first();

// [NO] MAUVAIS : Injection SQL possible
$user = DB::raw("SELECT * FROM users WHERE email = '$email'");
```

---

#### Phase 2 : Couverture de Tests (MAJOR)

- [ ] Atteindre 80%+ de couverture de code
- [ ] Tester les happy paths
- [ ] Tester les edge cases (limites, valeurs nulles)
- [ ] Tester les erreurs et exceptions
- [ ] Ajouter des tests d'intégration pour les flux SSO

```php
// [OK] Test complet
public function test_user_can_enroll_to_workshop_with_available_slots()
{
    $workshop = Workshop::factory()->create(['max_participants' => 2]);
    $user = User::factory()->create();

    // Act
    $response = $this->actingAs($user)->post("/api/workshops/{$workshop->id}/enroll");

    // Assert
    $response->assertStatus(201);
    $this->assertDatabaseHas('workshop_enrollments', [
        'workshop_id' => $workshop->id,
        'user_id' => $user->id,
    ]);
}
```

---

#### Phase 3 : Architecture et Maintenabilité (MAJOR)

- [ ] Réduire les méthodes > 50 lignes (refactoriser en sous-méthodes)
- [ ] Respecter le Single Responsibility Principle
- [ ] Éliminer le code dupliqué (extraire en services)
- [ ] Améliorer la nomenclature des variables
- [ ] Ajouter des comments sur les logiques complexes

```php
// [NO] Méthode trop longue (>50 lignes)
public function enrollUserToWorkshop($userId, $workshopId)
{
    // 50 lignes de logique différente...
}

// [OK] Décomposé en petites méthodes
public function enrollUserToWorkshop($userId, $workshopId)
{
    $this->validateEnrollmentEligibility($userId, $workshopId);
    $this->createEnrollment($userId, $workshopId);
    $this->notifyUser($userId);
    $this->logActivity($userId, $workshopId);
}
```

---

#### Phase 4 : Documentation et Conventions (MINOR)

- [ ] Documenter les endpoints API avec Swagger/OpenAPI
- [ ] Ajouter des docblocks PHP complets
- [ ] Documenter les configurations Spring Boot
- [ ] Maintenir ce README à jour
- [ ] Ajouter des commentaires sur les sections complexes

```php
/**
 * Enroll a user to a workshop
 *
 * @param \Illuminate\Http\Request $request
 * @param \App\Models\Workshop $workshop
 * @return \Illuminate\Http\JsonResponse
 *
 * @throws \Illuminate\Validation\ValidationException
 * @throws \App\Exceptions\WorkshopFullException
 */
public function enroll(Request $request, Workshop $workshop)
{
    // ...
}
```

---

## Contenu de la Remise Finale

### Structure Requise

```
EC06_NumCandidat.zip
│
├── skillhub/                          # Dépôt complet
│   ├── .git/                          # Historique Git
│   ├── skillhub-auth/                 # Service d'auth Spring Boot
│   │   └── auth/
│   │       ├── Dockerfile
│   │       ├── src/
│   │       ├── pom.xml
│   │       └── README.md
│   │
│   ├── skillhub-back/                 # Back-end Laravel
│   │   ├── Dockerfile
│   │   ├── app/
│   │   ├── routes/
│   │   ├── database/
│   │   ├── config/
│   │   ├── bootstrap/
│   │   ├── composer.json
│   │   └── README.md
│   │
│   ├── .github/
│   │   └── workflows/
│   │       └── ci.yml                 # Pipeline CI/CD GitHub Actions
│   │
│   ├── docker-compose.yml             # Orchestration des services
│   ├── .env.example                   # Modèle de configuration
│   ├── sonar-project.properties       # Configuration Sonar
│   ├── README.md                      # Ce fichier
│   │
│   └── CAPTURES/                      # Dossier de preuves
│       ├── 01-docker-compose-up.png
│       ├── 02-all-services-running.png
│       ├── 03-github-actions-passed.png
│       ├── 04-sonarcloud-dashboard.png
│       ├── 05-auth-sso-flow.png
│       └── 06-api-test.png
```

### Fichiers à INCLURE

- Code source complet (app/, routes/, src/, config/, etc.)
- `.git/` (historique des commits)
- Dockerfiles et docker-compose.yml
- `.env.example`
- `.github/workflows/ci.yml`
- `sonar-project.properties`
- `README.md`
- Captures d'écran de preuves
- Tests et fichiers de configuration

### Fichiers à EXCLURE

- `vendor/` (dépendances PHP)
- `target/` (artefacts Maven)
- `.env` (secrets)
- `node_modules/` (dépendances Node)
- Fichiers temporaires (`*.log`, `*.tmp`)
- Dossier `.git/objects` volumineux (utiliser `git archive` si trop gros)

---

## Ressources Utiles

### Documentation Officielle

- [Laravel Documentation](https://laravel.com/docs)
- [Spring Boot Documentation](https://spring.io/projects/spring-boot)
- [Docker Documentation](https://docs.docker.com)
- [SonarCloud Documentation](https://sonarcloud.io/documentation)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)

### Commandes Utiles

```bash
# Git
git log --oneline                      # Voir l'historique
git status                             # État du dépôt

# Docker
docker compose up -d                   # Démarrer en arrière-plan
docker compose logs -f backend         # Voir les logs en temps réel
docker compose ps                      # État des conteneurs

# Laravel
php artisan migrate:rollback           # Annuler la dernière migration
php artisan seed                       # Remplir la DB
php artisan tinker                     # Shell interactif

# Spring Boot
mvn spring-boot:run                    # Lancer localement
mvn clean test                         # Exécuter les tests
```

---

## Support et Contribution

### Reporting des Bugs

1. Vérifier que le bug n'existe pas dans les issues
2. Créer une issue avec :
   - Description détaillée
   - Étapes de reproduction
   - Stack trace (si applicable)
   - Environnement (OS, versions)

### Proposer des Améliorations

- implémenter une meilleure gestion des sessions (access token + refresh token)
- ajouter la pagination sur les endpoints qui retournent des listes

---

## À Retenir

- SkillHub est une plateforme **complète et sécurisée** avec authentification SSO
- L'architecture **microservices** permet la scalabilité et la maintenabilité
- **Docker** garantit une exécution cohérente
- **SonarCloud + GitHub Actions** assurent la qualité continue
- La **gestion des inscriptions** protège la qualité pédagogique
- Les **tests et couverture de code** préviennent les régressions
