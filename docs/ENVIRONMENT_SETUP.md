# Environment Setup Guide

Karbon uses a unified environment configuration system.

## Configuration Structure

- **Project Root (.env)**: Central configuration for Docker and all services.
- **Service-specific (.env)**: Cloned or linked from the root for individual service needs.

## Setup Steps

1. Create and configure the root `.env`:
   ```bash
   cp .env.example .env
   # Edit .env with your specific settings
   ```

2. Distribute the configuration:
   ```bash
   ./setup-env.sh
   ```

3. (Optional) Choose your Web Server in `.env`:
   - Set `WEB_SERVER=nginx` (Default, recommended for performance)
   - Set `WEB_SERVER=apache` (Legacy compatibility)

## Docker Configuration

The `gnu5_api/docker-compose.yml` uses the variables defined in `.env`.
If you change database passwords or ports, ensure you run:
```bash
docker-compose down
docker-compose up -d
```

## Application Integration

- **PHP (Gnuboard)**: Uses `vlucas/phpdotenv`. Configuration is loaded in `common.php` via `_env_init.php`. Use the `env('KEY', 'default')` function to access variables.
- **Frontend / Manager**: Uses Vite's environment handling. Variables must be prefixed with `VITE_`.

## Sensitive Files

The following files are now configured to use environment variables and should NOT be committed to version control:
- `.env`
- `gnu5_api/gnuboard/data/dbconfig.php` (already ignored in many cases, but now sanitized)
- `gnu5_api/gnuboard/api/.env`
