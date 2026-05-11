# PathFinders - HR Management System (Symfony)

![Symfony](https://img.shields.io/badge/Symfony-6.4-000000?style=flat&logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-10.4-4479A1?style=flat&logo=mysql)
![License](https://img.shields.io/badge/License-MIT-green.svg)

A comprehensive Human Resources Management System built with Symfony 6.4, designed to streamline recruitment, employee management, training, payroll, and event coordination.

## 📋 Table of Contents

- [Features](#features)
- [Technologies](#technologies)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [API Routes](#api-routes)
- [Database Schema](#database-schema)
- [Testing](#testing)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [License](#license)

## ✨ Features

### 🎯 Recruitment Management
- **Job Offers**: Create, edit, and manage job postings
- **Candidate Applications**: Track and review candidate submissions
- **Quiz System**: Automated candidate assessment with custom questions
- **Interview Scheduling**: Plan and manage interview appointments
- **Application Tracking**: Monitor candidate progress through hiring pipeline

### 👥 Employee Management
- **User Authentication**: Secure login with role-based access control
- **Profile Management**: Employee information and document storage
- **Role Synchronization**: Seamless integration with Java application

### 💰 Payroll Management
- **Payslip Generation**: Automated payslip creation and PDF export
- **Bonus Management**: Track and distribute employee bonuses
- **Salary History**: Maintain comprehensive payment records

### 📚 Training & Development
- **Training Programs**: Create and manage training sessions
- **Registration System**: Employee enrollment and tracking
- **Training Calendar**: Schedule and organize training events

### 📅 Event Management
- **Event Creation**: Organize company events and activities
- **Calendar Integration**: Visual event calendar with Tattali Calendar Bundle
- **Participant Management**: Track event registrations and attendance

### 📊 Analytics & Reporting
- **Dashboard**: Real-time HR metrics and KPIs
- **Chart Visualization**: Interactive charts with Chart.js
- **Statistical Reports**: Comprehensive HR analytics

### 🔐 Security Features
- **Role-Based Access Control**: Admin, Worker, Candidate roles
- **CSRF Protection**: Secure form submissions
- **Password Hashing**: Bcrypt password encryption
- **Database Triggers**: Automatic role normalization

## 🛠 Technologies

### Backend
- **Framework**: Symfony 6.4
- **PHP**: 8.1+
- **ORM**: Doctrine 2.19
- **Database**: MySQL 10.4 (MariaDB)

### Frontend
- **Template Engine**: Twig 3.0
- **Charts**: Chart.js (via Mukadi Bundle)
- **Calendar**: Tattali Calendar Bundle
- **CSS**: Custom stylesheets

### Development Tools
- **Static Analysis**: PHPStan
- **Testing**: PHPUnit
- **Code Generation**: Symfony Maker Bundle
- **PDF Generation**: DomPDF

## 📦 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP** >= 8.1
  ```bash
  php -v
  ```

- **Composer** (PHP dependency manager)
  ```bash
  composer --version
  ```

- **MySQL** >= 5.7 or MariaDB >= 10.4
  ```bash
  mysql --version
  ```

- **Symfony CLI** (recommended)
  ```bash
  symfony version
  ```

- **XAMPP** (optional, for local MySQL)
  - Download from [https://www.apachefriends.org](https://www.apachefriends.org)

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/pathfinders-rh-web.git
cd pathfinders-rh-web
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

Copy the `.env` file and configure your database:

```bash
cp .env .env.local
```

Edit `.env.local`:

```env
# Database Configuration
DATABASE_URL="mysql://root:@127.0.0.1:3306/pathfinders?serverVersion=10.4.32-MariaDB&charset=utf8mb4"

# App Environment
APP_ENV=dev
APP_SECRET=your-secret-key-here
```

### 4. Create Database

```bash
# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# (Optional) Load fixtures
php bin/console doctrine:fixtures:load
```

### 5. Install Role Synchronization (if using with Java app)

```bash
# Windows
.\install_role_sync.ps1

# Linux/Mac
./install_role_sync.sh
```

### 6. Clear Cache

```bash
php bin/console cache:clear
```

### 7. Start Development Server

```bash
# Using Symfony CLI (recommended)
symfony serve

# Or using PHP built-in server
php -S localhost:8000 -t public/
```

Visit: **http://localhost:8000**

## ⚙️ Configuration

### Database Configuration

Edit `config/packages/doctrine.yaml`:

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
    orm:
        auto_generate_proxy_classes: true
        auto_mapping: true
```

### Security Configuration

Edit `config/packages/security.yaml`:

```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
    
    providers:
        app_user_provider:
            entity:
                class: App\Entity\Utilisateur
                property: email
    
    firewalls:
        main:
            form_login:
                login_path: auth_login
                check_path: auth_login
            logout:
                path: auth_logout
```

### File Upload Configuration

Create upload directories:

```bash
mkdir -p public/uploads/cv
mkdir -p public/uploads/categories
mkdir -p public/uploads/events
```

## 📖 Usage

### Creating a New User

```bash
# Via command line
php bin/console app:create-user admin@example.com password123 ROLE_ADMIN

# Or register via web interface
# Navigate to: http://localhost:8000/inscription
```

### Default Roles

- `ROLE_ADMIN`: Full system access
- `ROLE_WORKER`: Employee access (HR, Trainer)
- `ROLE_CANDIDAT`: Candidate access (job applications)

### Common Commands

```bash
# Clear cache
php bin/console cache:clear

# View all routes
php bin/console debug:router

# Create new entity
php bin/console make:entity EntityName

# Create new controller
php bin/console make:controller ControllerName

# Generate migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate

# Validate database schema
php bin/console doctrine:schema:validate
```

## 📁 Project Structure

```
pathFinders-RH-Web-/
├── bin/                        # Console commands
├── config/                     # Configuration files
│   ├── packages/              # Bundle configurations
│   ├── routes/                # Route definitions
│   └── services.yaml          # Service container
├── migrations/                 # Database migrations
│   └── role_normalization_trigger.sql
├── public/                     # Web root
│   ├── css/                   # Stylesheets
│   ├── uploads/               # User uploads
│   └── index.php              # Front controller
├── src/
│   ├── Controller/            # Controllers
│   │   ├── Client/           # Public-facing controllers
│   │   ├── Dashboard/        # Admin dashboard
│   │   ├── Evenement/        # Event management
│   │   ├── FichePaie/        # Payroll
│   │   ├── Formation/        # Training
│   │   ├── Offre/            # Job offers & recruitment
│   │   ├── Reclamation/      # Complaints
│   │   └── User/             # User management
│   ├── Entity/                # Doctrine entities
│   ├── Form/                  # Form types
│   ├── Repository/            # Database repositories
│   └── Kernel.php             # Application kernel
├── templates/                  # Twig templates
│   ├── base.html.twig         # Base layout
│   ├── auth/                  # Authentication views
│   ├── dashboard/             # Dashboard views
│   ├── offre/                 # Job offer views
│   └── ...
├── tests/                      # PHPUnit tests
├── var/                        # Cache, logs
├── vendor/                     # Composer dependencies
├── .env                        # Environment variables
├── composer.json               # PHP dependencies
└── README.md                   # This file
```

## 🛣 API Routes

### Public Routes

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/` | Homepage - Job offers list |
| GET | `/connexion` | Login page |
| POST | `/connexion` | Login submission |
| GET | `/inscription` | Registration page |
| POST | `/inscription` | Registration submission |
| GET | `/deconnexion` | Logout |

### Candidate Routes

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/mes-candidatures` | My applications |
| GET | `/quiz/{id}` | Start quiz for job offer |
| POST | `/quiz/{id}` | Submit quiz answers |
| GET | `/quiz/{id}/result` | View quiz results |

### Admin Routes - Job Offers

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/admin/offres` | List all job offers |
| GET | `/admin/offres/new` | Create new offer |
| POST | `/admin/offres/new` | Save new offer |
| GET | `/admin/offres/{id}` | View offer details |
| GET | `/admin/offres/{id}/edit` | Edit offer |
| POST | `/admin/offres/{id}/edit` | Update offer |
| DELETE | `/admin/offres/{id}` | Delete offer |

### Admin Routes - Questions

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/admin/offres/{id}/questions/new` | Add question to offer |
| POST | `/admin/offres/{id}/questions/new` | Save question |
| GET | `/admin/questions/{id}/edit` | Edit question |
| DELETE | `/admin/questions/{id}` | Delete question |

### Admin Routes - Interviews

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/admin/entretiens` | List all interviews |
| GET | `/entretien/planifier/{id}` | Schedule interview |
| POST | `/entretien/planifier/{id}` | Save interview |

### Dashboard Routes

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/dashboard` | Main dashboard |
| GET | `/dashboard/statistiques` | HR statistics |
| GET | `/dashboard/categories` | Manage categories |
| GET | `/dashboard/formations` | Manage training |

## 🗄 Database Schema

### Main Tables

#### `utilisateurs`
- `id_utilisateur` (PK)
- `email` (unique)
- `mot_de_passe` (hashed)
- `nom`, `prenom`
- `role` (ROLE_ADMIN, ROLE_WORKER, ROLE_CANDIDAT)
- `statut` (actif, inactif)

#### `offres`
- `id_offre` (PK)
- `titre`
- `description`
- `type_contrat`
- `salaire`
- `date_publication`
- `date_expiration`
- `statut`

#### `questions`
- `id_question` (PK)
- `offre_id` (FK)
- `texte_question`
- `reponse_correcte`
- `points`

#### `candidatures`
- `id_candidature` (PK)
- `offre_id` (FK)
- `candidat_id` (FK)
- `score`
- `statut`
- `date_candidature`

#### `entretiens`
- `id_entretien` (PK)
- `candidature_id` (FK)
- `date_entretien`
- `lieu`
- `statut`

#### `formations`
- `id_formation` (PK)
- `titre`
- `description`
- `date_debut`
- `date_fin`
- `capacite`

#### `evenements`
- `id_evenement` (PK)
- `titre`
- `description`
- `date_debut`
- `date_fin`
- `lieu`

#### `fiches_paie`
- `id_fiche` (PK)
- `employe_id` (FK)
- `mois`
- `annee`
- `salaire_base`
- `primes`
- `deductions`
- `net_a_payer`

### Database Triggers

The system includes automatic role normalization triggers:

```sql
-- Normalizes roles on INSERT
CREATE TRIGGER normalize_user_role_before_insert
BEFORE INSERT ON utilisateurs
FOR EACH ROW
BEGIN
    SET NEW.role = CASE
        WHEN LOWER(NEW.role) = 'admin' THEN 'ROLE_ADMIN'
        WHEN LOWER(NEW.role) = 'candidat' THEN 'ROLE_CANDIDAT'
        WHEN LOWER(NEW.role) IN ('employe', 'rh', 'formateur') THEN 'ROLE_WORKER'
        ELSE NEW.role
    END;
END;

-- Normalizes roles on UPDATE
CREATE TRIGGER normalize_user_role_before_update
BEFORE UPDATE ON utilisateurs
FOR EACH ROW
BEGIN
    SET NEW.role = CASE
        WHEN LOWER(NEW.role) = 'admin' THEN 'ROLE_ADMIN'
        WHEN LOWER(NEW.role) = 'candidat' THEN 'ROLE_CANDIDAT'
        WHEN LOWER(NEW.role) IN ('employe', 'rh', 'formateur') THEN 'ROLE_WORKER'
        ELSE NEW.role
    END;
END;
```

## 🧪 Testing

### Run PHPUnit Tests

```bash
# Run all tests
php bin/phpunit

# Run specific test
php bin/phpunit tests/Controller/OffreControllerTest.php

# Run with coverage
php bin/phpunit --coverage-html coverage/
```

### Run PHPStan Analysis

```bash
# Analyze code
vendor/bin/phpstan analyse src

# With specific level
vendor/bin/phpstan analyse src --level=8
```

### Manual Testing Checklist

- [ ] User registration works
- [ ] User login works
- [ ] Job offer creation works
- [ ] Quiz submission works
- [ ] Interview scheduling works
- [ ] Payslip generation works
- [ ] Role synchronization works (if using Java app)

## 🚢 Deployment

### Production Environment Setup

1. **Update Environment Variables**

```env
APP_ENV=prod
APP_DEBUG=0
DATABASE_URL="mysql://user:password@host:3306/database"
```

2. **Install Production Dependencies**

```bash
composer install --no-dev --optimize-autoloader
```

3. **Clear and Warm Cache**

```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

4. **Run Migrations**

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

5. **Set Permissions**

```bash
chmod -R 755 var/
chmod -R 755 public/uploads/
```

6. **Configure Web Server**

**Apache (.htaccess)**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

**Nginx**
```nginx
server {
    listen 80;
    server_name pathfinders.example.com;
    root /var/www/pathfinders/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}
```

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Coding Standards

- Follow PSR-12 coding standards
- Write PHPDoc comments for all methods
- Add unit tests for new features
- Run PHPStan before committing

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Authors

- **PathFinders Team** - *Initial work* - Esprit School of Engineering

## 🙏 Acknowledgments

- Symfony Framework
- Doctrine ORM
- Twig Template Engine
- Chart.js
- DomPDF
- All open-source contributors

## 📞 Support

For support, email support@pathfinders.com or open an issue in the repository.

## 🔗 Related Documentation

- [COMMANDES.md](COMMANDES.md) - Detailed command reference
- [INSTALLATION_CHECKLIST.md](INSTALLATION_CHECKLIST.md) - Installation verification
- [ROLE_SYNC_README.md](ROLE_SYNC_README.md) - Role synchronization guide
- [ROLE_MAPPING_GUIDE.md](ROLE_MAPPING_GUIDE.md) - Role mapping documentation

---

**Made with ❤️ by PathFinders Team**
