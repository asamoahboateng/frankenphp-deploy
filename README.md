# FrankenPHP Deploy

FrankenPHP + Traefik Docker deployment scaffolding for Laravel Octane projects.

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x
- Laravel Octane
- Docker & Docker Compose

## Installation

```bash
composer require asamoahboateng/frankenphp-deploy --dev
```

## Quick Start

```bash
# Install the scaffolding (interactive prompts for domain and project name)
php artisan frankenphp:install

# Or with options
php artisan frankenphp:install --domain=myapp.test --project=myapp
```

This will create:

- `frankenphp_server/` directory with Docker configuration files
- `pha` CLI script in your project root

## Setup SSL (Local Development)

```bash
cd frankenphp_server
./setup-ssl.sh
```

This uses `mkcert` to generate trusted local SSL certificates.

## Starting Your Application

```bash
# Start with Traefik reverse proxy (recommended for multiple projects)
./pha start

# Or start standalone (FrankenPHP handles SSL directly)
./pha standalone
```

## Pha CLI Commands

### Lifecycle

| Command            | Description                                    |
| ------------------ | ---------------------------------------------- |
| `./pha start`      | Start with Traefik reverse proxy               |
| `./pha standalone` | Start without Traefik (FrankenPHP handles SSL) |
| `./pha stop`       | Stop all containers                            |
| `./pha reboot`     | Restart all services                           |
| `./pha reload`     | Reload Octane workers (zero downtime)          |
| `./pha fresh`      | Wipe and rebuild (local only)                  |
| `./pha delete`     | Remove all containers, images, volumes         |

### Scaling

| Command           | Description                                 |
| ----------------- | ------------------------------------------- |
| `./pha scale [n]` | Scale to n replicas (e.g., `./pha scale 4`) |

### Development

| Command           | Description          |
| ----------------- | -------------------- |
| `./pha art [cmd]` | Run artisan command  |
| `./pha composer`  | Run composer command |
| `./pha npm`       | Run npm command      |
| `./pha tinker`    | Open Laravel tinker  |
| `./pha ssh`       | Shell into container |

### Monitoring

| Command        | Description             |
| -------------- | ----------------------- |
| `./pha ls`     | List running containers |
| `./pha logs`   | Tail FrankenPHP logs    |
| `./pha status` | Show detailed status    |

## Configuration

After installation, edit `frankenphp_server/.env` to customize:

```env
APP_URL=https://myapp.test/
APP_DOMAIN=myapp.test
APP_PORT=8000
APP_ENV=local
COMPOSE_PROJECT_NAME=myapp
TRAEFIK_HOST=traefik.myapp.test
ADMINER_DOMAIN=adminer.myapp.test
```

## Architecture

### With Traefik (Multi-Project Setup)

```
┌─────────────┐
│   Traefik   │ ← SSL termination, routing
│ (port 443)  │
└──────┬──────┘
       │
┌──────┴──────┐
│ FrankenPHP  │ ← Laravel Octane
└──────┬──────┘
       │
┌──────┴──────┐
│    Init     │ ← composer install, migrations
└──────┬──────┘
       │
┌──────┴──────┐
│  Services   │ ← PostgreSQL, Redis, Queue Worker
└─────────────┘
```

### Standalone (Single Project)

```
┌─────────────┐
│ FrankenPHP  │ ← SSL via Caddy + Laravel Octane
│ (port 443)  │
└──────┬──────┘
       │
┌──────┴──────┐
│    Init     │ ← composer install, migrations
└──────┬──────┘
       │
┌──────┴──────┐
│  Services   │ ← PostgreSQL, Redis, Queue Worker
└─────────────┘
```

## Services Included

- **Init** - Runs before the app starts to install Composer dependencies and execute database migrations
- **FrankenPHP** - High-performance PHP application server with Caddy
- **PostgreSQL 16** - Database
- **Redis** - Cache and session storage
- **Queue Worker** - Laravel queue processing
- **Adminer** - Database management UI
- **Typesense** - Search engine (optional)

## Init Service

The `init` service is a one-shot container that runs **before** the main FrankenPHP application starts. It ensures your environment is ready by performing:

1. **Composer install** - installs/updates PHP dependencies (`composer install --optimize-autoloader`)
2. **Database migrations** - runs `php artisan migrate --force`
3. **Storage link** - creates the public storage symlink

The startup order is: `db/redis → init → frankenphp → worker`

The init container uses the same Docker image as the app, exits after completing its tasks, and will not restart. If it fails (e.g., a migration error), the main app will not start — allowing you to fix the issue before the app serves traffic.

To customize what the init service runs, edit `frankenphp_server/init.sh`.

## Optional Services

### Typesense (Search Engine)

Typesense is included by default but is optional. It's useful for Laravel Scout integration.

#### To use Typesense:

1. Set your API key in `frankenphp_server/.env`:

   ```env
   TYPESENSE_API_KEY=your-secure-api-key
   ```

2. Add to your Laravel `.env`:

   ```env
   SCOUT_DRIVER=typesense
   TYPESENSE_HOST=typesense
   TYPESENSE_PORT=8108
   TYPESENSE_PROTOCOL=http
   TYPESENSE_API_KEY=your-secure-api-key
   ```

3. Install Laravel Scout with Typesense:
   ```bash
   composer require laravel/scout typesense/typesense-php
   ```

#### To remove Typesense:

If you don't need search functionality, comment out or delete the Typesense service in your docker-compose files:

**In `frankenphp_server/docker-compose-traefik.yml` and `docker-compose-standalone.yml`:**

```yaml
# Comment out or delete this entire block:
# typesense:
#   image: typesense/typesense:27.1
#   container_name: myapp_typesense_franken
#   ...
```

Also remove the Typesense environment variables from the `frankenphp` service:

```yaml
# Remove these lines:
# - TYPESENSE_HOST=typesense
# - TYPESENSE_PORT=8108
# - TYPESENSE_PROTOCOL=http
# - TYPESENSE_API_KEY=${TYPESENSE_API_KEY:-xyz123}
```

## Publishing Config

To customize the package configuration:

```bash
php artisan vendor:publish --tag=frankenphp-config
```

This publishes `config/frankenphp.php` where you can set default values for Traefik network names and other settings.

## Force Overwrite

To reinstall and overwrite existing files:

```bash
php artisan frankenphp:install --force
```

## License

MIT
