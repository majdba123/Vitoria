# Run plan — Vitoria performance tests

## Workflows

1. **w1-browse-home** — visit the home page and browse featured content (read-only, unauthenticated)
2. **w2-vendor-browse** — vendor listing → vendor detail (read-only, unauthenticated)
3. **w3-product-browse** — product/category listing → product detail (read-only, unauthenticated)
4. **w4-cart-checkout** — authenticate as a dedicated test buyer, add a product to cart, and complete COD checkout (write path)

## Test credentials

Do not commit real or personally identifying account credentials into performance scripts or documentation.

Provide dedicated disposable test-buyer credentials through environment variables when running authenticated scenarios, for example:

```powershell
$env:K6_BUYER_PHONE = '0990000002'
$env:K6_BUYER_PASSWORD = '<local-test-password>'
```

The authenticated workflow should use a normal customer account, not an admin/vendor/syndicate/employee account, because privileged roles are not valid buyers in the current authorization model.

## Destructive actions

The checkout workflow creates order/order-item rows on every successful iteration. The current checkout flow supports Cash on Delivery for this test path, so no external payment charge is expected, but repeated runs will still mutate and grow the test database.

Mitigations:

- run authenticated write scenarios only against disposable local/staging data;
- cap iterations/duration for soak and stress runs;
- reset/reseed the disposable database after heavy test runs when appropriate;
- never target production with destructive load-test scenarios.

## Default performance objectives

Unless a real product SLO is supplied for the test environment, treat thresholds in scripts as test assumptions rather than production promises.

Example starting points:

- p95 latency target: < 500 ms for read paths
- error rate target: < 1%

Actual production SLOs should be derived from product requirements and measured on production-like infrastructure.

## Tooling

- HAR/protocol conversion may use `har-to-k6` where useful.
- Browser flows can be translated into k6/browser or protocol-level tests depending on the objective.
- Run k6 from PATH (`k6 run ...`) rather than documenting a developer-specific executable path.
- Use Laravel logs and application observability available in the target environment for backend investigation.

## Run matrix

| Test type | Recommended target |
| --- | --- |
| smoke | local / disposable staging |
| average | staging or concurrency-capable local server |
| stress | staging only |
| spike | staging only |
| soak | staging only |
| breakpoint | isolated staging environment |

Do not use `php artisan serve` results to infer production concurrency capacity. Use a concurrency-capable server/runtime before making application performance claims.

## Environment constraints

The target must be explicitly configured for each run. Do not commit private hosts, raw production IPs, credentials, or tokens into the repository.
