# Dev Container Setup — Farmers IMS
_Last verified working: April 28, 2026_

---

## Overview

The project uses a **VS Code Dev Container** backed by Docker Compose.  
There are **two** docker-compose files that are merged at container startup:

| File | Purpose |
|---|---|
| `docker-compose.yml` (root) | mysql + phpmyadmin services |
| `.devcontainer/docker-compose.yml` | php service (the container you live in) |

`devcontainer.json` references both:
```json
"dockerComposeFile": [
    "../docker-compose.yml",
    "docker-compose.yml"
]
```

**This split is intentional and required.** Do not consolidate into one file.

---

## Current Working Versions

| Component | Version |
|---|---|
| PHP | **8.5.5** (NTS, cli) — `FROM php:latest` resolved to this |
| Xdebug | 3.5.1 |
| Zend OPcache | 8.5.5 |
| MySQL | 8.4 (`FROM mysql:8.4`) |
| phpMyAdmin | latest |
| Composer | 2 (copied from `composer:2` multi-stage build) |
| OS (container) | Debian GNU/Linux 13 (trixie) |

---

## PHP Extensions Installed

Built into the devcontainer PHP image (`FROM php:latest`):

- `pdo_mysql` — database access
- `intl` — internationalisation
- `zip` — archive support
- `xdebug` — debugging (enabled via `docker-php-ext-enable xdebug`)
- `pdo_sqlite` — built into php:latest base
- `opcache` — built into php:latest base

**NOT installed in devcontainer**: `true_async` / `php-async` extension.  
TrueAsync is only available in `trueasync/php-true-async:latest` Docker image,  
used separately for async server work — NOT the devcontainer.

---

## Devcontainer PHP Dockerfile

`.devcontainer/docker/php/Dockerfile`:
```dockerfile
FROM composer:2 AS composer

FROM php:latest AS base

RUN apt-get update -qq && apt-get install -y --no-install-recommends \
    libicu-dev libzip-dev zip unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql intl zip \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY docker/php/conf.d/error_reporting.ini /usr/local/etc/php/conf.d/error_reporting.ini
COPY docker/php/conf.d/upload.ini /usr/local/etc/php/conf.d/upload.ini

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /workspaces/farmers-store-inventory
```

**Key points:**
- `COPY` paths for `conf.d/*.ini` are relative to the **root** build context (`.`), not `.devcontainer/`.
  The ini files live at `docker/php/conf.d/` in the repo root.
- Composer binary is pulled from the official `composer:2` image via multi-stage build.
- `mbstring` is NOT explicitly installed — it's bundled in `php:latest`.

---

## Devcontainer docker-compose.yml

`.devcontainer/docker-compose.yml`:
```yaml
services:
  php:
    build:
      context: .
      dockerfile: .devcontainer/docker/php/Dockerfile
    command: ["sleep", "infinity"]
    volumes:
      - .:/workspaces/farmers-store-inventory
  mysql:
    build:
      context: .
      dockerfile: .devcontainer/docker/database/mysql/Dockerfile
  phpmyadmin:
    image: phpmyadmin:latest
```

**Key points:**
- `context: .` is the **repo root** — not `.devcontainer/`. This is required so `COPY docker/php/conf.d/...` works.
- `command: ["sleep", "infinity"]` keeps the container alive (VS Code attaches to it).
- Volume mounts repo root to `/workspaces/farmers-store-inventory`.
- mysql and phpmyadmin here override/extend the root `docker-compose.yml` services.

---

## Root docker-compose.yml

`docker-compose.yml`:
```yaml
services:
    mysql:
        build: docker/database/mysql
        ports:
            - 3306:3306
        environment:
            - MYSQL_DATABASE=${MYSQL_DB:-farmers_store}
            - MYSQL_USER=${MYSQL_USER:-farmers}
            - MYSQL_PASSWORD=${MYSQL_PASSWORD:-farmers}
            - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD:-root}
        healthcheck:
            test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-uroot", "-p${MYSQL_ROOT_PASSWORD:-root}"]
            interval: 30s
            timeout: 60s
            retries: 5
            start_period: 80s

    phpmyadmin:
        image: phpmyadmin:latest
        ports:
            - 8082:80
        environment:
            - PMA_HOST=mysql
            - PMA_USER=root
            - PMA_PASSWORD=${MYSQL_ROOT_PASSWORD:-root}
        depends_on:
            - mysql
```

---

## devcontainer.json

`.devcontainer/devcontainer.json`:
```json
{
    "name": "Farmers Store Inventory",
    "dockerComposeFile": [
        "../docker-compose.yml",
        "docker-compose.yml"
    ],
    "service": "php",
    "workspaceFolder": "/workspaces/${localWorkspaceFolderBasename}",
    "overrideCommand": false,
    "features": {
        "ghcr.io/devcontainers/features/git:1": {}
    },
    "forwardPorts": [8080, 8082],
    "customizations": {
        "vscode": {
            "extensions": [
                "xdebug.php-debug",
                "bmewburn.vscode-intelephense-client",
                "jaguadoromero.vscode-php-create-class"
            ],
            "launch.configurations": [
                {
                    "name": "Listen for Xdebug",
                    "type": "php",
                    "request": "launch",
                    "port": 9003
                }
            ]
        }
    },
    "postCreateCommand": "bash .devcontainer/scripts/post-create.sh"
}
```

---

## Post-Create Script

`.devcontainer/scripts/post-create.sh`:
```bash
#!/bin/bash
set -e

git config --global --add safe.directory /workspaces/farmers-store-inventory

cd /workspaces/farmers-store-inventory
composer update --no-interaction --ignore-platform-req=php
```

**`--ignore-platform-req=php` is REQUIRED.**  
`composer.json` declares `php: ^8.6` (for TrueAsync compatibility) but the devcontainer  
runs PHP 8.5.5. Without this flag, `composer install/update` fails with a platform requirement  
error. The flag is safe here because 8.5 → 8.6 compatibility is not an issue for this project.

---

## Xdebug Config

`docker/php/conf.d/xdebug.ini`:
```ini
zend_extension = xdebug

[xdebug]
xdebug.mode = debug
xdebug.client_host = 127.0.0.1
xdebug.client_port = 9003
xdebug.start_with_request = yes
xdebug.log_level = 7
xdebug.log = /tmp/xdebug.log
```

`client_host = 127.0.0.1` works because VS Code Dev Containers forward the debug port  
from the container back to the host automatically. Do not change to `host.docker.internal`.

---

## Forwarded Ports

| Port | Service |
|---|---|
| 8080 | PHP built-in server (app) |
| 8082 | phpMyAdmin |
| 9003 | Xdebug (VS Code listens, container connects) |

---

## Tracy vs Xdebug — Disabling Development Mode

Tracy registers its own error/exception handlers via `Debugger::enable()`. When development
mode is active, Tracy intercepts exceptions before Xdebug can break on them, causing
breakpoints to be silently skipped.

**To use Xdebug breakpoints, disable development mode first:**

```bash
php bin/development-mode disable
```

**To re-enable Tracy / development mode:**

```bash
php bin/development-mode enable
```

Do NOT manually edit `config/autoload/development.local.php` — use the script.  
The script creates/removes that file, which is what toggles the `debug` flag and Tracy.

---

## Running the App (Dev Server)

Inside the devcontainer terminal — PHP built-in server, **no nginx needed** for dev:

```bash
# Main app
php -S 0.0.0.0:8080 -t public/

# v2 UI mockup (if needed)
php -S 0.0.0.0:7655 -t resources/ui-mockup/v2/
```

The nginx config at `docker/nginx/nginx.conf` exists for **production/staging deployment**  
(the root `docker-compose.yml` production stack). It is NOT used in the devcontainer.

---

## MySQL Connection (inside container)

```
host: mysql       (Docker service name — NOT localhost)
port: 3306
database: farmers_store
user: farmers
password: farmers
root password: root
```

Config file: `config/autoload/mysql.local.php` (gitignored).

---

## Database / MySQL Dockerfile

`.devcontainer/docker/database/mysql/Dockerfile`:
```dockerfile
FROM mysql:8.4
```

---

## What NOT to Do

- **Do not** add an nginx service to the devcontainer — it's not needed; PHP built-in server is sufficient.
- **Do not** remove `--ignore-platform-req=php` from the post-create script.
- **Do not** change `context: .` to `context: .devcontainer` in `.devcontainer/docker-compose.yml` —  
  the build context must be the repo root so `COPY docker/php/conf.d/...` resolves correctly.
- **Do not** merge the two docker-compose files — devcontainer overrides must stay separate.
- **Do not** switch `xdebug.client_host` to `host.docker.internal` — `127.0.0.1` works correctly  
  in the devcontainer port-forwarding model.
- **Do not** add `mbstring` to the `docker-php-ext-install` list — it is already bundled in `php:latest`.
