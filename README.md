# Order Status Tracker

[![Checks](https://github.com/kaleemnadeem16/order-status-tracker/actions/workflows/test.yml/badge.svg)](https://github.com/kaleemnadeem16/order-status-tracker/actions/workflows/test.yml)

Plain PHP and MySQL application for staff to build order timelines and share customer tracking links and QR codes.

## Features

- Administrator and staff accounts; staff access their own orders, administrators access all orders.
- Reusable status templates, custom timeline steps, durations and dated progress.
- Customer tracking page with a random bearer token and a no-JavaScript fallback.
- QR link display/download/share and token regeneration to revoke an old link.
- Password changes, CSRF protection and account login lockouts.

## Run locally

Requires Docker Desktop with Compose. The included credentials are disposable local demo values. MySQL is not exposed on the host; the app binds only to loopback.

```sh
git clone https://github.com/kaleemnadeem16/order-status-tracker.git
cd order-status-tracker
docker compose -p tracker-demo up -d --build --wait
docker compose -p tracker-demo exec app php scripts/create-admin.php --email=admin@example.com --name="Demo Admin"
```

Open http://localhost:8092/login.php. Use the generated temporary password and change it under My Account. Create an order with synthetic values such as `DEMO-1024`, add timeline steps, save, then open its tracking link. The schema and six generic status templates load automatically on the first database start. No users or orders are seeded.

If port 8092 changes, change both the Compose port and APP_URL, then recreate the app container. To remove this demo and its database:

```sh
docker compose -p tracker-demo down -v
```

## Configuration and deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for the Apache installation guide.

Configuration is in `config/config.php`; environment variables override an ignored `config/config.local.php` copied from `config/config.example.php`. Set DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, APP_URL and APP_ENV explicitly for deployment. APP_URL must be a trusted canonical URL. Use HTTPS, a least-privilege database account, backups and a private database network. Do not deploy the local Compose credentials. Apache needs rewrite/headers and the supplied access restrictions; other servers need equivalent rules blocking internal directories and metadata. Never expose config, database, tests, vendor, scripts, dotfiles or build configuration.

Session cookies use HttpOnly, SameSite=Lax and Secure when HTTPS is configured. Enable TRUST_PROXY_HEADERS only behind a trusted proxy. The public token contains 32 random bytes; it grants access to that order without login. Anyone holding the link can read its timeline. Keep personal data out of timeline messages, revoke leaked links, and exclude tracking URLs/query strings from access logs, analytics and error reports. The included Apache image suppresses access logs. Referrer-Policy is no-referrer.

## Development and verification

```sh
npm ci
npm run build:css
docker compose -p tracker-demo exec app composer install --no-interaction --prefer-dist
docker compose -p tracker-demo exec app composer test
docker compose -p tracker-demo exec app php tests/http-smoke.php
```

Run tests only against the disposable local database. HTTP smoke tests create a temporary administrator through the CLI, log in, create an order, open/revoke its public link, check blocked files and CSRF/logout, and exercise the throttle. Test records are deleted afterward. The throttle test can temporarily return 429 to requests from the same peer; wait one minute afterward. GitHub Actions uses GitHub-hosted Ubuntu runners to build CSS, lint PHP and run these checks in Docker.

## Status and limitations

Portfolio/demo release. No production readiness or usage metrics are claimed. Public tracking is limited to 60 requests per peer IP per minute using locked files in the container temporary directory. This is a single-host throttle: reverse proxies share an IP bucket, restarts reset it, and multi-instance deployments need a shared edge limiter. Apply edge protection to login too; current login lockout is per account. Global status templates/settings are editable by authenticated staff. Date-driven completion currently uses Asia/Karachi. Password resets do not revoke other existing sessions. There is no email recovery, MFA, audit trail or automated backup/retention policy. Review these before storing real data.

The current Tailwind 3 build dependency tree has seven npm audit advisories (five high, two moderate), including braces and postcss-selector-parser. Compatible fixes were applied; the remaining automated fix requires a Tailwind 4 migration. These dependencies are build-only; do not process untrusted CSS/glob input. This migration remains follow-up work.

Browser QR download/share and mobile layout require manual acceptance testing. Browser sharing depends on platform support. Fonts are requested from Google Fonts; system fallbacks work offline. The checks badge links to the latest GitHub Actions results.

MIT © 2026 Kaleem Nadeem. Vendored QRCode.js retains its separate notice in THIRD_PARTY_NOTICES.md; dependency licenses remain their owners'.
