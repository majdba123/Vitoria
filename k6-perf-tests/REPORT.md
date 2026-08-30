# Vitoria — k6 load test report (local dev)

**Target:** `http://localhost:8020` (local `php artisan serve`, SQLite)
**Date:** 2026-08-28
**Scope:** 4 workflows, smoke test (all) + combined average-load test

## Summary

| Workflow | Description | Auth |
|---|---|---|
| w1-browse-home | Home page | no |
| w2-vendor-browse | Home → vendor show page | no |
| w3-product-browse | Product-type gate → product list → product detail | no |
| w4-cart-checkout | Login → add to cart → checkout (COD) | yes |

All 4 workflows are **functionally correct** — every request returns the right
status/body under low concurrency (smoke: 3 VUs). Under moderate concurrency
(average-load: ramping to 37 VUs total across workflows), the site **collapses**.

## SLOs (defaults used, not user-specified)
- Latency: p(95) < 500ms (browse) / < 600ms (detail pages) / < 1000ms (checkout)
- Error rate: < 1%
- Checks: > 99% pass

## Findings

### 1. CRITICAL — Server can't handle concurrency; requests queue until 60s timeout
At 3 VUs (smoke), every page load already took **2.8s–8.8s** (p95 ~7.5-8.8s) —
10-15x over the 500ms default SLO — despite each *individual* request being
simple (no heavy queries observed in the flows tested). At ~37 concurrent VUs
(average-load test, `tests/full-site-average.js`), **68.8% of requests failed**
outright, with p95 latency pinned at the 60s default HTTP timeout
(`http_req_duration{name:Home}` p95 = 1m0s, `ProductShow`/`ProductsList`/
`VendorShow` likewise saturated at 60s).

This pattern — latency scaling almost linearly with VU count even at very low
concurrency, converging on the timeout ceiling — is the signature of a
**single-threaded/synchronous request handler**: requests aren't processed in
parallel, they queue and are served one at a time. `php artisan serve` (Laravel's
built-in dev server) is single-threaded by design; it is explicitly documented
as unsuitable for anything beyond one request at a time locally.

**Evidence:** `k6-perf-tests/tests/w1-browse-home/smoke.js` run (p95=8.02s @ 3 VUs)
and `k6-perf-tests/tests/full-site-average.js` run (68.80% http_req_failed,
p95=60s @ up to 37 VUs). Raw k6 output captured in this session's transcript.

**Recommendation:** This is a testing-environment artifact, not necessarily an
app bug — re-run against a real concurrent server (`php artisan octane:start`,
or `nginx + php-fpm`, or `laravel/valet`) before drawing conclusions about the
application code itself. If a proper concurrent server *also* shows p95 growth
under load, then dig into slow queries/N+1s per route next.

### 2. NOTE — Login route path differs from typical convention
`POST /api/login` returns 405; the real route is `POST /api/auth/login`. Not a
bug, just flagging in case other tooling/docs assume `/api/login`.

### 3. NOTE — Checkout returns 201, not 200
`POST /api/checkout` (order placement) returns HTTP 201 Created, while most
other write endpoints in this app return 200. Worth confirming this is
intentional/documented for API consumers.

### 4. NOTE — No dedicated buyer test account in seed data
Only admin and vendor accounts exist in seeders. A dedicated k6 test buyer was
created directly in the local DB for w4 (`phone_number=0900000001`,
`password=k6-load-test-password`, `email=k6.buyer@vetora.test`, with a seeded
default address). Consider adding a "buyer" seed account for future testing
if this is useful beyond k6.

## Scope reductions vs. the full k6-perf-test-website skill process
Given the request to move fast end-to-end, this run skipped:
- Playwright/HAR recording pipeline (scripts were hand-written directly against
  routes found by reading the Laravel codebase, then verified against the live
  server with curl before being encoded as k6 checks).
- The `k6/browser` layer (Web Vitals / LCP / INP / CLS) — only protocol-level
  (HTTP) testing was done.
- stress / spike / soak / breakpoint test types — only smoke (all 4 workflows)
  and one combined average-load run were executed, since the concurrency
  ceiling was found immediately and further scaling won't reveal more until
  the server-concurrency issue (Finding 1) is addressed.

## Evidence / artifacts
- `k6-perf-tests/runbook.md` — run plan
- `k6-perf-tests/tests/w1-browse-home/smoke.js` … `w4-cart-checkout/smoke.js`
- `k6-perf-tests/tests/full-site-average.js` — combined load test
- k6 CLI: `C:\Users\Yusuf\AppData\Local\k6\k6.exe` v2.2.0

## Suggested next steps
1. Re-run against a concurrency-capable server (Octane/php-fpm) to separate
   "dev-server artifact" from real app performance.
2. If latency persists under a real server, profile the slowest routes
   (`ProductShow`, `ProductsList` were consistently the slowest in this run).
3. Add stress/spike/soak once (1) is resolved, to find the real ceiling.
4. Consider a dedicated buyer seed account for repeatable testing.
