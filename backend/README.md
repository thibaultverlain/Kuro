# BManager — Backend Symfony

Gestionnaire de tâches collaboratif : projets, tâches, membres, notifications.

## Stack

- PHP 8.2+
- Symfony 7
- Doctrine ORM
- PostgreSQL
- Bootstrap 5

## Installation

### 1. Cloner le projet

```bash
git clone https://github.com/thibltl/BManager.git
cd BManager/backend
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer l'environnement

```bash
cp .env .env.local
```

Éditer `.env.local` et renseigner :

```env
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/bmanager"
MAILER_DSN=smtp://localhost
APP_SECRET=votre_secret_ici
```

### 4. Créer la base de données et appliquer les migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Lancer le serveur de développement

```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

## Rôles

| Rôle | Accès |
|------|-------|
| `ROLE_USER` | Ses propres projets et tâches |
| `ROLE_ADMIN` | Tous les projets, tous les utilisateurs |

## Lancer les tests

```bash
php bin/phpunit
```

## Structure src/

```
src/
├── Controller/
│   ├── Admin/          # CRUD admin (ROLE_ADMIN uniquement)
│   ├── Front/          # Interface utilisateur
│   ├── NotificationController.php
│   ├── SecurityController.php
│   └── UserController.php
├── Entity/             # Task, Project, User, Notification, TaskHistory…
├── Form/               # TaskType, ProjectType…
├── Repository/         # Requêtes Doctrine
├── Security/
│   ├── ProjectVoter.php        # Contrôle d'accès aux projets
│   └── UserAuthenticator.php
└── Service/
    ├── NotificationService.php
    └── TaskHistoryService.php
```
