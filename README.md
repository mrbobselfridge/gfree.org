# gFree.org CMS

This repository contains the Laravel and Filament application that powers the public gFree.org website, the admin content system, file and media libraries, analytics, backups, workflow notifications, and slide deck import tooling.

## Stack

- PHP 8.3
- Laravel 13
- Filament 5
- SQLite by default for local development
- Vite and Tailwind CSS
- Laravel queues for slide deck processing, workflow notifications, and other background work
- Spatie Laravel Backup for database, file, and archive backup profiles

## Local Setup

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Create the local environment file and app key:

```bash
cp .env.example .env
php artisan key:generate
```

Create the local SQLite database if it does not exist:

```bash
touch database/database.sqlite
php artisan migrate
```

Build frontend assets:

```bash
npm run build
```

Run the full local development stack:

```bash
composer dev
```

That starts the Laravel server, queue listener, log tail, and Vite dev server.

## Common Commands

Run the PHP test suite:

```bash
composer test
```

Run the frontend production build:

```bash
npm run build
```

Run migrations:

```bash
php artisan migrate
```

Clear application caches after environment or config changes:

```bash
php artisan optimize:clear
```

## Admin Areas

The Filament admin panel is mounted at `/admin`.

Primary content tools:

- Homepage content and rotating homepage banners
- Site alerts
- Pages and page redirects
- Navigation links
- Media library
- File library and file categories
- Slide deck import

Primary site tools:

- Site settings
- Analytics
- Backup profiles
- Workflow notification rules
- User management and editor permissions

Admin access is role-based. Admin users have full access. Editor users can be assigned tool-level access, and page records can also be assigned individually.

## Public Content Model

The public site is built from these main pieces:

- `/` renders the configured homepage, homepage banners, site alerts, navigation, social links, and homepage content blocks.
- `/{slug}` renders active page records managed in Filament.
- `/files/{fileName}` serves published file-library documents by stable file path.
- `/manual` serves the local admin manual.

Pages can be normal content pages or redirects. Page content is composed with reusable blocks, including text, image/text, button + text, process lists, link cards, info strips, related content, YouTube feeds, embed blocks, and code blocks for approved admins.

## Environment Notes

Important environment variables:

- `APP_URL`: canonical site URL used by generated links and local redirect checks.
- `QUEUE_CONNECTION`: should be backed by a running worker in production.
- `FILESYSTEM_DISK`: default Laravel filesystem disk.
- `OPENAI_CONTENT_MODEL`: model used by AI page/content tools.
- `OPENAI_FILE_EXTRACTION_MODEL`: model used by file-library extraction.
- `UNSPLASH_ACCESS_KEY`: enables Unsplash image search/import.
- `MAXMIND_LICENSE_KEY` and `MAXMIND_USER_ID`: used when updating the local MaxMind database.
- `LOCATION_TESTING`: defaults to `false`; set to `true` only for local geolocation testing.
- `BACKUP_*`: controls backup profile schedules and archive encryption.

The committed `database/maxmind/GeoLite2-City.mmdb` file is intentionally left in place for now. If this changes later, update both deployment notes and analytics/geolocation setup instructions.

## Queue Worker Dependencies

Slide deck import requires external binaries on the queue worker `PATH`:

- LibreOffice: `libreoffice` or `soffice`
- ImageMagick: `magick` or `convert`

See [docs/operations-notes.md](docs/operations-notes.md) for server setup details.

## Backups

The app includes separate backup profiles for database, full, and archive backups. Configure backup disks and notification settings through environment variables and the admin backup page. If archive encryption is enabled, keep `BACKUP_ARCHIVE_PASSWORD` in the server environment or secret manager, not in source control.

## Security Notes

- `.env`, local build output, local storage links, vendor dependencies, and node modules are ignored by Git.
- Public custom HTML, CSS, and JavaScript fields are admin-trusted features. Limit code-block and site-setting access to trusted users.
- OpenAI API keys are currently stored in `site_settings`. See the review notes for encryption options before broadening database or backup access.

## Documentation

- [docs/operations-notes.md](docs/operations-notes.md): production operations notes, currently focused on slide deck import dependencies.
- [docs/site-structure-recommendations.md](docs/site-structure-recommendations.md): current content architecture and cleanup recommendations.
- [taxonomy-naming-recommendations.md](taxonomy-naming-recommendations.md): editor-facing naming guidance.
