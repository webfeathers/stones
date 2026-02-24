# Gem & Mineral Collection Tracker — Project Plan

## Overview

A PHP/MySQL web application for cataloging a large gem and mineral collection (500+ items). Features a **public gallery** for browsing and a **password-protected admin panel** for managing entries, uploading photos, and configuring which fields are tracked — without touching code.

**Hosting:** Dreamhost shared hosting (PHP 8.x + MySQL)
**Domain:** Custom domain via Dreamhost

---

## Key Design Principles

1. **Dynamic fields** — The admin can add, rename, reorder, and retire data fields from the UI. No code changes needed when you want to start tracking "fluorescence" or "acquisition date" next year.
2. **Multi-photo support** — Each specimen gets a sortable photo gallery (drag to reorder, set a primary/thumbnail).
3. **Thumbnail generation** — Automatic resizing on upload so the public gallery loads fast even at 500+ items.
4. **Mobile-friendly** — Responsive design for both admin and public views.
5. **No framework bloat** — Lightweight vanilla PHP with a thin routing layer. Easy to understand, easy to deploy on Dreamhost via FTP/SFTP. No Composer dependencies required on the server (we'll vendor anything needed).

---

## Database Schema

### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT | PK |
| username | VARCHAR(100) | |
| password_hash | VARCHAR(255) | bcrypt |
| role | ENUM('admin','editor') | Future-proofing for multi-user |
| created_at | DATETIME | |

### `specimens`
Core table — intentionally lean. Most descriptive data lives in the EAV (entity-attribute-value) pattern via `specimen_field_values`.

| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT | PK |
| name | VARCHAR(255) | Display name (always required) |
| slug | VARCHAR(255) | URL-friendly, unique |
| description | TEXT | Rich-text description |
| is_published | TINYINT(1) | 0 = draft, 1 = public |
| sort_order | INT | For manual ordering |
| created_at | DATETIME | |
| updated_at | DATETIME | |

### `custom_fields`
Defines what fields exist. Admin manages these from the UI.

| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT | PK |
| field_name | VARCHAR(100) | Internal key (e.g. `hardness`) |
| label | VARCHAR(150) | Display label (e.g. "Mohs Hardness") |
| field_type | ENUM('text','textarea','number','select','multi_select','date','url','color') | |
| options_json | JSON | For select/multi_select — stores choices |
| is_required | TINYINT(1) | |
| is_filterable | TINYINT(1) | Show in public gallery filters? |
| is_visible_public | TINYINT(1) | Show on public detail page? |
| sort_order | INT | Order on forms and detail pages |
| created_at | DATETIME | |

### `specimen_field_values`
EAV table linking specimens to their custom field data.

| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT | PK |
| specimen_id | INT | FK → specimens |
| field_id | INT | FK → custom_fields |
| value | TEXT | Stored as text, cast by field_type |

**Indexes:** Composite index on `(specimen_id, field_id)` for fast lookups.

### `photos`
| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT | PK |
| specimen_id | INT | FK → specimens |
| filename | VARCHAR(255) | Stored filename |
| original_name | VARCHAR(255) | User's original filename |
| caption | VARCHAR(500) | Optional |
| sort_order | INT | Drag-to-reorder |
| is_primary | TINYINT(1) | Used as thumbnail in gallery |
| created_at | DATETIME | |

### `categories` *(optional, phase 2)*
| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT | PK |
| name | VARCHAR(100) | e.g. "Quartz family", "Carbonates" |
| slug | VARCHAR(100) | |
| parent_id | INT NULL | For nested categories |

### `specimen_categories` *(optional, phase 2)*
| Column | Type | Notes |
|--------|------|-------|
| specimen_id | INT | FK |
| category_id | INT | FK |

---

## Default Custom Fields (Pre-seeded)

These will be created during setup so you have a useful starting point. All can be renamed, reordered, or removed later.

| Label | Type | Filterable | Notes |
|-------|------|-----------|-------|
| Mineral Type | select | Yes | e.g. Quartz, Feldspar, Calcite... |
| Chemical Formula | text | No | e.g. SiO₂ |
| Color | multi_select | Yes | e.g. Red, Blue, Green... |
| Mohs Hardness | number | Yes | 1-10 scale |
| Crystal System | select | Yes | Cubic, Hexagonal, etc. |
| Luster | select | Yes | Vitreous, Metallic, etc. |
| Transparency | select | Yes | Transparent, Translucent, Opaque |
| Locality / Origin | text | Yes | Where it was found |
| Weight (carats) | number | No | |
| Dimensions | text | No | e.g. "3.2 × 2.1 × 1.5 cm" |
| Acquisition Date | date | No | When you got it |
| Acquisition Source | text | No | Dealer, show, mine, etc. |
| Estimated Value | text | No | Not publicly visible by default |
| Notes | textarea | No | Private notes |

---

## Application Structure

```
gem-tracker/
├── public/                  ← Dreamhost document root points here
│   ├── index.php            ← Front controller (all requests route through here)
│   ├── .htaccess            ← URL rewriting
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/          ← Site chrome (logo, icons)
│   └── uploads/
│       ├── originals/       ← Full-size photos
│       └── thumbs/          ← Auto-generated thumbnails
│
├── app/
│   ├── config.php           ← DB credentials, site settings
│   ├── database.php         ← PDO connection helper
│   ├── router.php           ← Lightweight URL router
│   ├── auth.php             ← Session-based login
│   ├── helpers.php          ← Utility functions
│   │
│   ├── controllers/
│   │   ├── PublicController.php    ← Gallery, search, detail pages
│   │   └── AdminController.php     ← CRUD for specimens, fields, photos
│   │
│   ├── models/
│   │   ├── Specimen.php
│   │   ├── CustomField.php
│   │   └── Photo.php
│   │
│   └── views/
│       ├── layouts/
│       │   ├── public.php          ← Public site wrapper
│       │   └── admin.php           ← Admin panel wrapper
│       ├── public/
│       │   ├── gallery.php         ← Grid/list browse view
│       │   ├── detail.php          ← Single specimen page
│       │   └── search.php          ← Filter/search results
│       └── admin/
│           ├── login.php
│           ├── dashboard.php
│           ├── specimens/
│           │   ├── list.php
│           │   ├── form.php        ← Add/edit (dynamically renders custom fields)
│           │   └── photos.php      ← Photo manager with drag-reorder
│           └── fields/
│               ├── list.php        ← Manage custom fields
│               └── form.php        ← Add/edit a field definition
│
├── migrations/
│   └── 001_initial_schema.sql
│
└── README.md
```

---

## Feature Roadmap

### Phase 1 — MVP
- [ ] Database setup + migration script
- [ ] Admin: Login / logout (bcrypt passwords, session auth)
- [ ] Admin: CRUD specimens (name, description, published status)
- [ ] Admin: Dynamic custom fields rendered on specimen form
- [ ] Admin: Photo upload with auto-thumbnail generation (GD library)
- [ ] Admin: Drag-to-reorder photos, set primary photo
- [ ] Admin: Manage custom fields (add/edit/reorder/retire)
- [ ] Public: Gallery grid with thumbnails + pagination
- [ ] Public: Detail page with photo lightbox + all visible fields
- [ ] Public: Basic search (name + text fields)
- [ ] Responsive CSS (mobile-friendly)
- [ ] Dreamhost deployment guide

### Phase 2 — Enhancements
- [ ] Public: Filter sidebar (checkboxes for filterable select fields)
- [ ] Public: Sort by name, date added, hardness, etc.
- [ ] Categories / tags with nested hierarchy
- [ ] Bulk import from CSV/spreadsheet
- [ ] Bulk photo upload (ZIP or multi-file)
- [ ] Image optimization (WebP conversion)

### Phase 3 — Nice-to-haves
- [ ] Public: Map view (if locality has coordinates)
- [ ] Public: "Random specimen" feature
- [ ] Admin: Activity log / revision history
- [ ] Admin: Multi-user with roles
- [ ] API endpoint (JSON) for future mobile app or integrations
- [ ] SEO: Open Graph tags, structured data (Schema.org)

---

## Tech Decisions & Rationale

| Decision | Why |
|----------|-----|
| **Vanilla PHP (no framework)** | Dreamhost shared hosting just works. No CLI access needed, no Composer on server, no build step. You can edit files over SFTP and changes are instant. |
| **EAV for custom fields** | This is the key to "easy to maintain." Adding a new field is a row insert, not a schema migration. Trade-off: slightly more complex queries, but at 500-1000 items the performance is fine. |
| **GD for thumbnails** | Available on Dreamhost by default. No ImageMagick dependency. |
| **No JavaScript framework** | Vanilla JS + a small library for the lightbox and drag-reorder. Keeps things simple and fast. |
| **Single entry point (front controller)** | Clean URLs like `/gallery/amethyst-cluster-42` instead of `detail.php?id=42`. Standard `.htaccess` rewrite. |

---

## Deployment on Dreamhost

1. Create a MySQL database in the Dreamhost panel
2. Point your custom domain to a directory (e.g. `gem-tracker/public/`)
3. Upload files via SFTP
4. Edit `app/config.php` with your DB credentials
5. Run the migration SQL (via phpMyAdmin or the built-in installer)
6. Create your admin account via a one-time setup script
7. Done — start adding specimens!

---

## Questions / Decisions for You

1. **Screenshots & default fields** — You mentioned you have these. Want to share them so I can incorporate your exact field names and any UI preferences?
2. **Visual style** — Any preference on colors/theme? Dark background to make mineral photos pop? Clean white gallery?
3. **Photo sizes** — Any max upload size preference? I'm thinking 2048px max for originals, 400px thumbnails.
4. **Authentication** — Single admin user for now, or do you want multi-user from the start?
