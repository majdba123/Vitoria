# Vitoria — k6 load-test report (local development)

**Target:** local Laravel development environment  
**Date:** 2026-08-28  
**Scope:** four workflows, smoke validation, and one combined average-load run

## Summary

| Workflow | Description | Auth |
| --- | --- | --- |
| w1-browse-home | Home page | no |
| w2-vendor-browse | Home → vendor detail | no |
| w3-product-browse | Product-type gate → product list → product detail | no |
| w4-cart-checkout | Login → add to cart → checkout (COD) | yes |

The low-concurrency smoke run validated the intended HTTP flows. The combined average-load run saturated the local development server, so its throughput/latency results must not be presented as production capacity.

## Test thresholds

The thresholds used in this run were test defaults, not product-approved production SLOs:

- browse p95 target: < 500 ms
- selected detail-path p95 target: < 600 ms
- checkout p95 target: < 1000 ms
- HTTP error-rate target: < 1%

## Findings

### Local development server saturated under concurrent load

At low concurrency, request latency was already materially above the test thresholds. During the combined average-load scenario, requests queued until the HTTP timeout ceiling and the failure rate increased substantially.

The run used Laravel's built-in development server, which is not an appropriate basis for production concurrency conclusions. The result is therefore classified as an **environment limitation / incomplete performance experiment**, not as proof that the production application cannot scale.

**Recommended validation:** repeat the same scripts against a concurrency-capable, production-like runtime such as Nginx + PHP-FPM or another intentionally configured staging server. If latency still grows under that environment, profile database queries, external calls, cache behavior, and route-level application work.

### API route behavior observed during the run

- authentication used the application's `/api/auth/login` route;
- successful checkout returned `201 Created` in the observed flow;
- authenticated checkout requires a normal customer account under the current role model.

These observations should continue to be verified against current routes/tests before being treated as permanent API contracts.

## Test-account handling

Performance tests should use a dedicated disposable buyer account supplied through runtime environment variables. Do not commit personal phone numbers, real credentials, or reusable privileged accounts into test scripts/documentation.

## Scope limitations

This historical run did not constitute a complete production performance certification. It omitted or intentionally reduced areas such as:

- production-like infrastructure validation;
- browser/Web-Vitals testing;
- complete stress/spike/soak/breakpoint coverage;
- deep database/APM profiling under a concurrency-capable server.

## Evidence / artifacts

- `k6-perf-tests/runbook.md` — current safe run plan
- `k6-perf-tests/tests/w1-browse-home/smoke.js` … `w4-cart-checkout/smoke.js`
- `k6-perf-tests/tests/full-site-average.js` — combined average-load scenario

Run k6 from the environment PATH rather than documenting developer-specific executable paths.

## Next steps

1. Re-run against production-like concurrent infrastructure.
2. Profile the slowest routes if latency remains high outside the development-server constraint.
3. Add stress/spike/soak testing only in an isolated staging environment with controlled data.
4. Keep authenticated test accounts disposable and configuration-driven.
