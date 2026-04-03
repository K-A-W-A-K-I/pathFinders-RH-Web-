# Projet Symfony — Gestion des Candidatures (PathFinders)

## Structure du projet

```
├── src/
│   ├── Controller/
│   │   ├── AuthController.php          # Connexion / Inscription / Déconnexion
│   │   ├── CandidatureController.php   # Liste des offres (candidat) + mes candidatures
│   │   ├── EntretienController.php     # Planification et gestion des entretiens
│   │   ├── OffreController.php         # CRUD offres + questions (admin)
│   │   └── QuizController.php          # Quiz de candidature
│   ├── Entity/
│   │   ├── Utilisateur.php             # Utilisateur (auth Symfony)
│   │   ├── Candidat.php                # Profil candidat lié à un utilisateur
│   │   ├── Offre.php                   # Offre d'emploi
│   │   ├── Question.php                # Question QCM liée à une offre
│   │   ├── Candidature.php             # Résultat du quiz / candidature
│   │   └── Entretien.php               # Entretien planifié
│   ├── Form/
│   │   ├── RegistrationFormType.php    # Formulaire d'inscription
│   │   ├── OffreType.php               # Formulaire création/édition offre
│   │   └── QuestionType.php            # Formulaire création/édition question
│   └── Repository/                     # Repositories Doctrine (requêtes custom)
├── templates/
│   ├── base.html.twig                  # Layout principal
│   ├── auth/                           # login.html.twig, register.html.twig
│   ├── offre/                          # index, show, form, question_form
│   ├── candidature/                    # offre_list, mes_candidatures
│   ├── entretien/                      # planifier, admin
│   └── quiz/                           # index, result
├── config/
│   ├── packages/security.yaml          # Auth Symfony (form_login)
│   ├── packages/doctrine.yaml          # Config Doctrine / PostgreSQL
│   └── services.yaml
├── compose.yaml                        # Docker — PostgreSQL
└── .env                                # Variables d'environnement
```

---

## Routes principales

| Route | URL | Rôle |
|---|---|---|
| `offre_list` | `/` | Liste des offres (candidat) |
| `candidature_mes` | `/mes-candidatures` | Mes candidatures |
| `quiz_start` | `/quiz/{id}` | Démarrer le quiz |
| `quiz_result` | `/quiz/{id}/result` | Résultat du quiz |
| `entretien_planifier` | `/entretien/planifier/{id}` | Planifier un entretien |
| `offre_index` | `/admin/offres` | Liste offres (admin) |
| `offre_new` | `/admin/offres/new` | Créer une offre |
| `offre_show` | `/admin/offres/{id}` | Détail offre + candidatures |
| `offre_edit` | `/admin/offres/{id}/edit` | Modifier une offre |
| `question_new` | `/admin/offres/{id}/questions/new` | Ajouter une question |
| `entretien_admin` | `/admin/entretiens` | Gestion entretiens (admin) |
| `auth_login` | `/connexion` | Page de connexion |
| `auth_register` | `/inscription` | Page d'inscription |
| `auth_logout` | `/deconnexion` | Déconnexion |

---

## Commandes pour créer le projet from scratch

### 1. Créer le projet Symfony

```bash
composer create-project symfony/skeleton:"6.4.*" nom-du-projet
cd nom-du-projet
```

### 2. Installer les dépendances

```bash
# ORM + base de données
composer require doctrine/doctrine-bundle doctrine/doctrine-migrations-bundle doctrine/orm

# Sécurité
composer require symfony/security-bundle symfony/security-csrf

# Formulaires + validation
composer require symfony/form symfony/validator

# Templates Twig
composer require symfony/twig-bundle twig/extra-bundle twig/twig

# Assets
composer require symfony/asset

# Outils dev
composer require --dev symfony/maker-bundle
```

### 3. Configurer la base de données (.env)

```bash
# MySQL via XAMPP (configuration utilisée dans ce projet)
DATABASE_URL="mysql://root:@127.0.0.1:3306/pathfinders?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
```

### 4. Lancer XAMPP

- Ouvrir XAMPP Control Panel
- Démarrer le module **MySQL** (et Apache si besoin)
- Créer la base de données `pathfinders` dans **phpMyAdmin** (http://localhost/phpmyadmin)

### 5. Créer les entités

```bash
php bin/console make:entity Utilisateur
php bin/console make:entity Candidat
php bin/console make:entity Offre
php bin/console make:entity Question
php bin/console make:entity Candidature
php bin/console make:entity Entretien
```

### 6. Créer les controllers

```bash
php bin/console make:controller AuthController
php bin/console make:controller OffreController
php bin/console make:controller CandidatureController
php bin/console make:controller QuizController
php bin/console make:controller EntretienController
```

### 7. Créer les formulaires

```bash
php bin/console make:form RegistrationFormType Utilisateur
php bin/console make:form OffreType Offre
php bin/console make:form QuestionType Question
```

### 8. Générer et exécuter les migrations

```bash
# Générer la migration depuis les entités
php bin/console make:migration

# Appliquer la migration
php bin/console doctrine:migrations:migrate
```

### 9. Vider le cache

```bash
php bin/console cache:clear
```

### 10. Lancer le serveur

```bash
# Avec Symfony CLI (recommandé)
symfony serve

# Ou avec PHP built-in
php -S localhost:8000 -t public/
```

L'application est disponible sur : **http://localhost:8000**

---

## Commandes utiles au quotidien

```bash
# Voir toutes les routes
php bin/console debug:router

# Voir les services disponibles
php bin/console debug:container

# Vérifier le schéma DB (différences entités vs DB)
php bin/console doctrine:schema:validate

# Mettre à jour le schéma sans migration (dev uniquement)
php bin/console doctrine:schema:update --force

# Créer un utilisateur admin manuellement (via console Doctrine)
php bin/console doctrine:query:sql "INSERT INTO utilisateurs ..."

# Voir les logs
tail -f var/log/dev.log
```

---

## Changer la version Symfony (ex: 7.1 → 6.4)

Modifier dans `composer.json` toutes les dépendances `symfony/*` de `7.1.*` vers `6.4.*`,
ainsi que `"require": "6.4.*"` dans la section `extra.symfony`, puis :

```bash
composer update
```
