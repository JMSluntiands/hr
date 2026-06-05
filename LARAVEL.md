# Luntian HR — Laravel

The HR system runs on **Laravel 12** with your existing MySQL database and data unchanged.

## Requirements

- PHP 8.2+ (XAMPP)
- MySQL on `127.0.0.1` (database `hr`)
- Apache `mod_rewrite` only if you still use the XAMPP `/hr/public/` URL

## First-time setup

1. Copy env (if needed):
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

2. Set database in `.env` (defaults match XAMPP):
   ```
   DB_HOST=127.0.0.1
   DB_DATABASE=hr
   DB_USERNAME=root
   DB_PASSWORD=
   APP_URL=http://127.0.0.1:8000
   ```

3. **Do not run** `php artisan migrate` on production — it would create Laravel default tables. Your HR tables already exist.

4. Google OAuth (optional): copy `legacy/config/google-oauth.example.php` to `legacy/config/google-oauth.php` and set:
   ```php
   'redirect_uri' => 'http://127.0.0.1:8000/auth/google/callback',
   ```
   Add the same URI in [Google Cloud Console](https://console.cloud.google.com/) → OAuth client → Authorized redirect URIs.

5. Slack time-in (optional): `legacy/config/slack.php` as before.

## How to open the app (recommended)

Start the Laravel dev server on **127.0.0.1**:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Or double-click **`serve.bat`** in the project folder.

Then open:

```
http://127.0.0.1:8000/
```

Login, admin, and employee routes all use this base URL (no `/hr/public/` path).

## Alternative: XAMPP Apache

If you prefer Apache instead of `artisan serve`:

```
http://localhost/hr/public/
```

Set `APP_URL=http://localhost/hr/public` in `.env` and run `php artisan config:clear`.

## Architecture

| Layer | Location |
|--------|----------|
| Laravel entry | `public/index.php` |
| Auth, dashboards, routing | `app/`, `routes/web.php`, `resources/views/` |
| Legacy PHP (unmigrated pages) | `legacy/` — served via `/admin/...`, `/employee/...`, etc. |
| Database credentials | `database/db.php` (gitignored) + `.env` |
| Uploads / assets | `uploads/`, `assets/` (junctions under `public/`) |

## What is already in Laravel

- Login + Time In (Slack)
- Google OAuth (`/auth/google`)
- Session timeout (5 minutes)
- Admin / employee module selection
- **Admin:** staff list, **add** (`/admin/staff/create`), **edit** (`/admin/staff/{id}/edit`), **view** (`/admin/staff/{id}`), **leave request** (`/admin/leave-requests`), **leave history** (`/admin/leaves/history`), reimbursement review, document requests, activity log, leave allocation, leave summary, dashboard
- **Employee:** dashboard, profile, leave credits + submit leave, reimbursements, request hub
- Legacy proxy for unmigrated pages
- Old `.php` URLs redirect to new Laravel routes

## Migrating more pages to Laravel

1. Add a route + controller + Blade view in Laravel.
2. Replace links in sidebars to the new route.
3. When done, remove the legacy PHP file.

## Troubleshooting

- **404 on all routes:** use `http://127.0.0.1:8000/` with `serve.bat` / `php artisan serve`; or enable `mod_rewrite` for XAMPP.
- **CSRF / 419 on login:** hard-refresh; `APP_URL` must match the URL in the browser exactly (`127.0.0.1` vs `localhost` are different).
- **Assets 404:** ensure `public/assets` and `public/uploads` junctions exist (see project `public/` folder).
- **Google login fails:** update `redirect_uri` in `google-oauth.php` and Google Console to `http://127.0.0.1:8000/auth/google/callback`.
- **Legacy page broken paths:** report the URL; includes use `legacy/database/db.php` bridge.
