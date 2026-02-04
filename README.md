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
- `zd_server_franken/` directory with Docker configuration files
- `pha` CLI script in your project root

## Setup SSL (Local Development)

```bash
cd zd_server_franken
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
| Command | Description |
|---------|-------------|
| `./pha start` | Start with Traefik reverse proxy |
| `./pha standalone` | Start without Traefik (FrankenPHP handles SSL) |
| `./pha stop` | Stop all containers |
| `./pha reboot` | Restart all services |
| `./pha reload` | Reload Octane workers (zero downtime) |
| `./pha fresh` | Wipe and rebuild (local only) |
| `./pha delete` | Remove all containers, images, volumes |

### Scaling
| Command | Description |
|---------|-------------|
| `./pha scale [n]` | Scale to n replicas (e.g., `./pha scale 4`) |

### Development
| Command | Description |
|---------|-------------|
| `./pha art [cmd]` | Run artisan command |
| `./pha composer` | Run composer command |
| `./pha npm` | Run npm command |
| `./pha tinker` | Open Laravel tinker |
| `./pha ssh` | Shell into container |

### Monitoring
| Command | Description |
|---------|-------------|
| `./pha ls` | List running containers |
| `./pha logs` | Tail FrankenPHP logs |
| `./pha status` | Show detailed status |

## Configuration

After installation, edit `zd_server_franken/.env` to customize:

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
│  Services   │ ← PostgreSQL, Redis, Queue Worker
└─────────────┘
```

## Services Included

- **FrankenPHP** - High-performance PHP application server with Caddy
- **PostgreSQL 16** - Database
- **Redis** - Cache and session storage
- **Queue Worker** - Laravel queue processing
- **Adminer** - Database management UI

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
