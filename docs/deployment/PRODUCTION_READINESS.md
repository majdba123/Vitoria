# Vetora production readiness

**Audit date:** 2026-08-28
**Base commit audited:** `94f84e665399c0622b028b8d4466d3bac1489f4a` plus the hardening changes in this release
**Verdict:** **READY WITH NON-BLOCKING WARNINGS**

The application code, migration path, assets, browser flows and automated checks are locally verified. The repository has an active GitHub Actions deployment target for `/var/www/MSZ`; production secrets are configured in GitHub and the live health endpoint responded successfully before release. The remaining warning is that this workstation cannot directly query the production database, so no claim is made that existing live financial rows reconcile.

## Gate summary

| Domain | Result | Evidence / remaining gate |
|---|---|---|
| Backend correctness | PASS | 421 tests; Laravel 12.66 / PHP 8.2.12; routes compile and return expected contracts. |
| Financial integrity | PASS (synthetic fixtures) | Checkout, completion, refund, adjustment, settlement, immutable ledger and rollback tests pass. |
| Concurrency | PASS (covered paths) | Cart/checkout locks, coupon claims, return quantities, cancellation/receipt restoration, idempotent ledger and settlement row locks are tested. |
| Authorization | PASS (automated + smoke) | Admin/vendor/syndicate negative tests and scoped Vendor 360 tests pass. |
| Database migrations | PASS locally | Fresh seed and incremental legacy-row backfill passed on an isolated SQLite database. Validate MySQL execution during release rehearsal. |
| Performance | PASS with measured limits | Existing bounded-query tests pass. Build sizes are unchanged from baseline; no unsupported speed claim is made. |
| Redis/cache | PASS with deployment prerequisite | Local runtime uses file cache/file sessions/database queue. `.env.example` uses Composer-installed Predis for an optional production Redis cache. `Vary` isolation is regression-tested. |
| React | PASS | Abort cleanup/race fixes, JSON-LD corrections and admin commission localization build successfully. |
| UX/accessibility | PASS locally, caveat | 320/375/768/1024/1440/1920/3840 widths, EN/LTR and AR/RTL dark-mode smoke checks had no horizontal overflow. No dedicated axe/Playwright accessibility suite is configured. |
| AEO | PASS read-only audit | Public Home, category, product, FAQ and vendor surfaces contain factual semantic headings and scoped JSON-LD; no private dashboard data is exposed. |
| Data reconciliation | WARN | Tests reconcile multiple synthetic vendors (sales 800, commission 140, net 660; refund and settlement cases). Existing production financial rows were not independently queried from this workstation. |
| Security | PASS locally | CSRF exemption for API routes removed; uploads validate MIME/size; authorization, IDOR, rate-limit and sensitive-field tests pass. Set production HTTPS/cookie/CORS values. |
| Tests/build | PASS | `php artisan test --compact`: 421 passed / 2,988 assertions. `npm run build`: client + SSR passed. Pint: 549 files passed. |
| Server configuration | PASS with runtime checks | GitHub SSH secrets exist, `/up` and the public home returned HTTP 200 before release, and the deployment now requires `.env`, DB credentials, `mysqldump`, a non-empty backup, successful migrations/cache rebuilds and a final health check. Production secret values remain intentionally undisclosed. |

## Findings fixed in this pass

| Severity | Domain | Finding | Root cause | Fix | Evidence |
|---|---|---|---|---|---|
| P1 | Security | API routes were excluded from CSRF validation | `api/*` was in the CSRF exception list | Removed the exemption; Sanctum SPA cookie flow remains enabled | `ReliabilityAtomicityTest`, official Sanctum flow, browser checkout |
| P1 | Financial reliability | Completion/cancellation/return mutations could partially commit | Status/payment/ledger or stock restoration crossed transaction boundaries | Moved money and restoration state transitions into transactions; broadcasts dispatch after commit | `ReliabilityAtomicityTest`, lifecycle suites |
| P1 | Data correctness | Domain totals changed when a product category changed later | Analytics used current product/category relationships | Added checkout-time category/domain/commission snapshots and legacy backfill | `VendorAnalyticsTest`, isolated migration rehearsal |
| P2 | Cache isolation | Public responses did not vary by locale/cookie | Incomplete `Vary` header and Inertia overwrite | Preserve `Accept`, `Accept-Language`, and `Cookie` dimensions | `HttpCacheIsolationTest` |
| P2 | Checkout UX | Guest cart was lost during registration | Register did not merge the session cart | Merge guest cart after account creation, matching login | `ServerCartTest`, browser guest-to-checkout flow |
| P2 | UX/i18n | Admin commission page was hard-coded English | Page did not consume shared translations | Added EN/AR translation keys and locale-aware money formatting | Rebuilt browser smoke |

## Performance evidence

- Baseline and final production bundles are materially unchanged: CSS 234.66 kB (35.87 kB gzip), app vendor 336.08 kB (105.44 kB gzip), chart 312.39 kB (95.93 kB gzip), Home 22.14 kB (6.55 kB gzip), Vendor 360 29.14 kB (8.97 kB gzip).
- The map query-count regression test remains constant as vendor count grows.
- Dashboard analytics use SQL aggregates, bounded pagination and snapshot-aware joins. No production latency claim is made because no production-like load environment was available.

## Data reconciliation follow-up

For each representative agriculture, veterinary and mixed vendor, run authoritative queries against completed orders, order items, `vendor_ledger_entries`, refunds, adjustments and settlements. Verify:

`gross sales - commission - refunds +/- adjustments = net earnings`
`net earnings - settlements = outstanding`

Also compare the same totals in Admin Vendor 360, Vendor dashboard, scoped Syndicate Vendor 360 and exports. Confirm cancelled orders are excluded, date boundaries use the documented order-created timestamp, and mixed-domain orders are marked attribution-incomplete rather than estimated.

## Redis/cache decision

The checked-in local runtime is file cache/file session/database queue. Redis is not required for local correctness. Production may set `CACHE_STORE=redis` with `REDIS_CLIENT=predis`; Redis is never the financial source of truth. Keep cache TTLs bounded and flush `products`, `categories`, `vendors`, `orders` and `dashboard` tags after mutations. If Redis is unavailable, the application cache service falls back to uncached execution for tagged reads.

## A/B test readiness (read-only)

No experiment infrastructure, flags, analytics identifiers or tracking cookies were added. Future candidates only: product-discovery CTA (add-to-cart rate), category presentation (product-detail entry rate), vendor trust presentation (checkout start rate), and checkout friction (completed-order rate). Each requires stable event instrumentation, revenue/error guardrails and a precomputed sample-size plan before launch.

## Deployment commands

The checked-in GitHub workflow deploys the exact commit that passed CI. Its remote sequence serializes deployments, verifies `.env`, enters maintenance mode with an automatic recovery trap, requires a non-empty transactional MySQL backup, installs dependencies, builds assets, migrates, rebuilds caches, restarts workers/services, exits maintenance mode and checks `/up`.

For a manual recovery deployment, run from `/var/www/MSZ` only after verifying `.env` and taking a database backup:

```bash
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan storage:link
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

This repository uses database queues and has two daily scheduled commands. Run a long-lived worker (`php artisan queue:work database --sleep=3 --tries=3 --timeout=90`) under Supervisor/systemd and configure server cron:

```cron
* * * * * cd /var/www/MSZ && php artisan schedule:run >> /dev/null 2>&1
```

Reverb is configured in the application, but the checked-in frontend disables it (`VITE_REVERB_ENABLED=false`); run a Reverb process only when production sets valid Reverb credentials and enables realtime. There is no Horizon or SSR server requirement detected.

## Rollback

1. Stop or drain the queue worker and put the web release in maintenance mode.
2. Restore the previous application release (`git revert` the release commit or switch the release symlink); do not reset a shared branch.
3. Restore the database backup if the release migration or application writes require it. The snapshot migration is additive, so an older application can safely ignore its extra columns; never drop them until all old/new readers are out of service.
4. Clear and rebuild caches, restart workers, run `/up`, then smoke-test login, cart, checkout, ledger and settlement reads before reopening traffic.

## Local verification record

- `php artisan test --compact`: **421 passed, 2,988 assertions**.
- `npm run build`: **PASS** (client and SSR).
- `vendor/bin/pint --test`: **PASS, 549 files**.
- `git diff --check`: **PASS**.
- `composer audit --locked`: **0 advisories**.
- `npm audit --audit-level=high`: **0 vulnerabilities**.
- `php artisan migrate:fresh --seed` and incremental snapshot backfill: **PASS on isolated SQLite**.
- Browser smoke: guest public catalog/cart/login, customer checkout/order, admin dashboard/Vendor 360/products/order/commission, vendor dashboard/products/orders/finance, veterinary syndicate dashboard/vendors/Vendor 360: **HTTP 200, no failed API responses, no horizontal overflow**.
