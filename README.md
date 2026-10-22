# Order Status Tracker

[![Checks](https://github.com/kaleemnadeem16/order-status-tracker/actions/workflows/test.yml/badge.svg)](https://github.com/kaleemnadeem16/order-status-tracker/actions/workflows/test.yml) [![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Staff build order timelines in a plain PHP and MySQL app and share a private tracking link or QR code with each customer.

**Status:** Beta. A reference implementation: tested in CI, not run in production. See Known limitations before storing real data. Run locally with Docker; there is no hosted demo.

## Features

- Administrator and staff accounts: staff see their own orders, administrators see all.
- Reusable status templates, custom timeline steps, durations and dated progress.
- Customer tracking page behind a random bearer token, with a no-JavaScript fallback.
- QR code display, download and share; token regeneration revokes an old link.
- Password changes, CSRF protection and account login lockouts.

## Stack

| Layer | Tech |
|---|---|
| App | Plain PHP (no framework), Apache |
| Data | MySQL |
| Frontend | Tailwind CSS (built with npm), vendored QRCode.js |
| Tooling | Docker Compose, Composer tests, GitHub Actions |

## Run locally

Requires Docker Desktop with Compose. The included credentials are disposable local demo values. MySQL is not exposed on the host and the app binds only to loopback.

```sh
git clone https://github.com/kaleemnadeem16/order-status-tracker.git
cd order-status-tracker
docker compose -p tracker-demo up -d --build --wait
docker compose -p tracker-demo exec app php scripts/create-admin.php --email=admin@example.com --name="Demo Admin"
```

Open http://localhost:8092/login.php and sign in with the generated temporary password, then change it under My Account. Create an order with a synthetic value such as `DEMO-1024`, add timeline steps, save, and open its tracking link. The schema and six generic status templates load on first database start; no users or orders are seeded.

If you change port 8092, change both the Compose port and `APP_URL`, then recreate the app container. To remove the demo and its database:

```sh
docker compose -p tracker-demo down -v
```

## Configuration

Settings are in `config/config.php`. Environment variables override an ignored `config/config.local.php` (copy `config/config.example.php`). For deployment set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL` and `APP_ENV` explicitly. `APP_URL` must be a trusted canonical URL. The Apache installation guide is [DEPLOYMENT.md](DEPLOYMENT.md).

## Security notes

- Use HTTPS, a least-privilege database account, backups and a private database network. Do not deploy the local Compose credentials.
- Apache needs rewrite and headers modules and the supplied access restrictions. Other servers need equivalent rules blocking config, database, tests, vendor, scripts, dotfiles and build files.
- Session cookies are HttpOnly, SameSite=Lax, and Secure when HTTPS is configured. Enable `TRUST_PROXY_HEADERS` only behind a trusted proxy.
- The public token holds 32 random bytes and grants read access to that order without login. Keep personal data out of timeline messages, revoke leaked links, and keep tracking URLs out of access logs, analytics and error reports. The bundled Apache image suppresses access logs; Referrer-Policy is `no-referrer`.

## Development and verification

```sh
npm ci
npm run build:css
docker compose -p tracker-demo exec app composer install --no-interaction --prefer-dist
docker compose -p tracker-demo exec app composer test
docker compose -p tracker-demo exec app php tests/http-smoke.php
```

Run tests only against the disposable local database. The HTTP smoke test creates a temporary administrator through the CLI, logs in, creates an order, opens and revokes its public link, checks blocked files, CSRF and logout, and exercises the throttle; its records are deleted afterwards. The throttle check can briefly return 429 to requests from the same peer, so wait a minute afterwards. GitHub Actions builds the CSS, lints PHP and runs these checks in Docker on GitHub-hosted Ubuntu runners.

## Known limitations

- Public tracking is limited to 60 requests per peer IP per minute using locked files in the container temp directory. It is a single-host throttle: proxies share an IP bucket, restarts reset it, and multi-instance setups need a shared edge limiter. Login lockout is per account, so add edge protection to login too.
- Global status templates and settings are editable by authenticated staff.
- Date-driven completion uses Asia/Karachi.
- Password resets do not revoke other sessions. There is no email recovery, MFA, audit trail or automated backup and retention policy.
- Browser QR download/share and the mobile layout still need manual acceptance testing. Sharing depends on platform support.
- Fonts load from Google Fonts; system fallbacks work offline.
- The Tailwind 3 build tree has seven npm audit advisories (five high, two moderate). Compatible fixes are applied; the remainder needs a Tailwind 4 migration. They are build-only, so do not feed untrusted CSS or glob input to the build.

## Roadmap

- Migrate to Tailwind 4 to clear the remaining advisories.
- Manual QR and mobile acceptance pass.
- Shared (edge) rate limiting and session revocation on password reset.

## License

MIT, see [LICENSE](LICENSE). Vendored QRCode.js keeps its own notice in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md); dependency licences remain with their owners.
