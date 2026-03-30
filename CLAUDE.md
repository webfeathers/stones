# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PHP/MySQL web app for cataloging a gem and mineral collection. Public gallery + password-protected admin panel. Hosted on Dreamhost shared hosting at `stones.webfeathers.com`.

**Stack:** Vanilla PHP 8.x, MySQL/MariaDB (PDO), GD library for images, no framework, no Composer.

## Architecture

**Front controller pattern:** All requests route through `public/index.php` via `.htaccess` rewrite. The router (`app/router.php`) matches URL patterns to controller methods.

**Entity-Attribute-Value (EAV) for dynamic fields:** The core `specimens` table is intentionally lean. All descriptive data (location, fluorescence, dimensions, etc.) is stored via `custom_fields` + `specimen_field_values` tables. Admins manage field definitions through the UI — no migrations needed to add new trackable properties.

**Key directories:**
- `public/` — Document root (index.php, assets, uploads)
- `app/controllers/` — PublicController (gallery/search/detail) and AdminController (CRUD + auth)
- `app/models/` — Specimen, CustomField, Photo (all use PDO prepared statements)
- `app/views/` — PHP templates split into `public/`, `admin/`, and `layouts/`
- `migrations/` — SQL migration files (run manually via phpMyAdmin or CLI)

**Image pipeline:** On upload, originals are resized to max 2048px, thumbnails generated at 400x400 fit-within. Files stored in `public/uploads/originals/` and `public/uploads/thumbs/`.

## Development

**No build step.** Edit PHP/CSS/JS files directly. Changes are live immediately on the server.

**Local development:** Requires PHP 8.x with GD extension and a MySQL database. Update `app/config.php` with local DB credentials.

**Database setup:** Run SQL files in `migrations/` directory in order (001, 002, etc.). First admin account is created via `/admin/setup` in the browser.

**Thumbnail regeneration:** `php regenerate_thumbs.php` rebuilds all thumbnails from originals.

## Deployment

Auto-deploy via GitHub Actions on push to `main`. The workflow SSHs into Dreamhost and runs `git pull`. See `.github/workflows/deploy.yml`.

## Key Patterns

- **Auth:** Session-based with CSRF tokens on all forms. Helper functions in `app/auth.php`.
- **Output escaping:** Use `e()` helper (wraps `htmlspecialchars`) for all user-facing output.
- **Database:** PDO singleton via `app/database.php`. Always use prepared statements.
- **Slugs:** Auto-generated from specimen name via `slugify()` in `app/helpers.php`.
- **Photo ordering:** Drag-reorder via AJAX. Primary photo flag determines gallery thumbnail.
- **Custom field types:** text, textarea, number, select, url, checkbox — defined in `custom_fields.field_type`.
