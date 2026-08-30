// k6 SMOKE test — w3-product-browse
// Product-type preference (required gate) -> product listing -> product detail.
//
// Run:
//   k6 run tests/w3-product-browse/smoke.js
//   BASE_URL=http://localhost:8020 PRODUCT_ID=1 k6 run tests/w3-product-browse/smoke.js

import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8020';
const PRODUCT_ID = __ENV.PRODUCT_ID || '1';
const USER_AGENT =
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36';

export const options = {
  userAgent: USER_AGENT,
  vus: 3,
  duration: '1m',
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<500'],
    checks: ['rate>0.99'],
    'http_req_duration{name:ProductTypeSelect}': ['p(95)<500'],
    'http_req_duration{name:ProductsList}': ['p(95)<500'],
    'http_req_duration{name:ProductShow}': ['p(95)<600'],
  },
};

export default function () {
  // Gate: browsing products/categories redirects to /product-type/select until
  // a preference cookie is set. Set it once per VU iteration like a real visitor.
  let res = http.get(
    `${BASE_URL}/product-type/select?preferred_product_type=agriculture&redirect_to=home`,
    {
      headers: { Accept: 'text/html,application/xhtml+xml,*/*;q=0.8' },
      tags: { name: 'ProductTypeSelect' },
    },
  );
  check(res, { 'product-type select 200': (r) => r.status === 200 });

  sleep(Math.random() * 1 + 0.5);

  res = http.get(`${BASE_URL}/products`, {
    headers: { Accept: 'text/html,application/xhtml+xml,*/*;q=0.8', Referer: `${BASE_URL}/` },
    tags: { name: 'ProductsList' },
  });
  check(res, { 'products list 200': (r) => r.status === 200 });

  sleep(Math.random() * 2 + 1);

  res = http.get(`${BASE_URL}/products/${PRODUCT_ID}`, {
    headers: { Accept: 'text/html,application/xhtml+xml,*/*;q=0.8', Referer: `${BASE_URL}/products` },
    tags: { name: 'ProductShow' },
  });
  check(res, { 'product show 200': (r) => r.status === 200 });

  sleep(Math.random() * 2 + 1);
}
