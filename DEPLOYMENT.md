# Deployment

The application supports a dedicated domain such as
`https://tracker.example.com`, a different domain, or a
subfolder installation. No URL is hard-coded into the application.

## Server requirements

- PHP 8.3 or newer with PDO MySQL and mbstring
- MySQL 8.4 (tested in Docker)
- Apache with `mod_rewrite` and `mod_headers` when using the included `.htaccess`
- HTTPS in production

The compiled stylesheet is committed, so Node.js is not required on the live
server.

## Install

1. Point the domain's document root at this project directory and upload all
   tracked files, including `.htaccess`.
2. Create an empty database and import `database/schema.sql`, followed once by
   `database/seed.sql`.
3. Copy `config/config.example.php` to `config/config.local.php` and enter the
   database credentials. This local file is ignored by Git.
4. Set `APP_URL` to the canonical HTTPS URL. Do not rely on request Host detection in production.
5. From the project directory, create the first administrator:

   ```sh
   php scripts/create-admin.php --email=admin@example.com --name="Administrator"
   ```

   Sign in with the generated temporary password and change it immediately.
6. Confirm that `/login.php` loads over HTTPS and that a generated customer
   tracking link opens at `/track/{token}`.

## Reverse proxies and CDNs

If Cloudflare or another trusted proxy terminates HTTPS before forwarding to
the server, set `TRUST_PROXY_HEADERS` to `true` in `config.local.php`. Leave it
`false` on a server that receives HTTPS directly.

## Source-control safety

Before the first push, run `git status --ignored` and confirm that
`config/config.local.php`, `.env*`, keys, logs, database dumps, dependencies,
and local editor/agent settings are ignored. Never commit a production database
export or a modified configuration containing credentials.
