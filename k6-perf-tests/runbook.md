# Run plan — Vitoria (local dev)

## Workflows
1. **w1-browse-home** — visit home page, view featured vendors/products (read-only, auth: no)
2. **w2-vendor-browse** — vendor listing → open a vendor's show page (read-only, auth: no)
3. **w3-product-browse** — category/product listing → product detail page (read-only, auth: no)
4. **w4-cart-checkout** — log in (seeded account), add a product to cart, complete checkout via COD (write, auth: yes)

## Credentials
- w4: seeded demo accounts from `database/seeders/DatabaseSeeder.php` / `ArabicVendorSeeder.php`.
  Login uses `phone_number` + `password` (NOT email).
  - Admin: phone `0935027218`, password `password`
  - Vendor: `agriculture.vendor@vetora.test` (see seeder for phone number), password `password`
  - No dedicated non-admin "buyer" seed found — using admin account for checkout flow since it's local-only demo data. Flag: confirm with user if a dedicated buyer account should be seeded instead.

## Destructive actions
- w4 checkout creates real rows in `orders`/`order_items` on each iteration. No real payment gateway
  is wired up — `CheckoutService::availablePaymentMethods()` only supports Cash on Delivery (COD), so
  no money moves. Still, repeated runs will bloat the local DB.
  - Mitigation: cap iterations in soak/stress; skip w4 in breakpoint runs unless requested; recommend
    re-seeding the DB (`php artisan migrate:fresh --seed`) after heavy runs.

## Worry list
- Not yet specified by user — defaulting to w4-cart-checkout (write path, most business-critical) as
  the workflow to spend the most tuning time on. Flag as default pending confirmation.

## SLOs
- Latency: p(95) < 500ms (default)
- Error rate: < 1% (default)
- Throughput: not specified — will observe under average/stress and report
- Flag: defaults used, pending user confirmation.

## Backend ownership
- Owns backend: yes (this is the user's own Laravel app, local dev)
- Grafana: none configured — skipping §9 Grafana investigation; will use local Laravel logs
  (`storage/logs/laravel.log`) and `php artisan` tooling if issues are found instead.
- Datasources: none

## MCP tools planned for this project
- HAR → k6 protocol conversion: `npx har-to-k6` (CLI)
- Playwright recorder → k6/browser migration: hand-written per `references/functional-tests.md`
  (mcp-k6 not detected as connected in this session)
- k6 script authoring / validation: local `k6` binary (`C:\Users\Yusuf\AppData\Local\k6\k6.exe`)
- Backend investigation: local Laravel logs only (no Grafana)

## Run matrix
| Test type   | Where  |
|-------------|--------|
| smoke       | local  |
| average     | local  |
| stress      | local  |
| spike       | local  |
| soak        | local (reduced VUs/duration to limit DB bloat) |
| breakpoint  | local, protocol-only, w1–w3 only (skip w4 writes) |

All local-only — this is a local dev server (`http://localhost:8000`), not production. Results are
bounded by laptop LG capacity; not a substitute for a cloud-scale run against staging/production.

## Constraints
- IP allow-list: none (localhost)
- Rate limiters: none known
- Maintenance windows: n/a (local dev)
- Target: `http://localhost:8000` (confirmed responding, HTTP 200)
