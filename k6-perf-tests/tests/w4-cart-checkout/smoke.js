// k6 SMOKE test — w4-cart-checkout
// Login -> add to cart -> checkout (COD/"cash", no real payment gateway).
//
// Requires a dedicated k6 test buyer seeded in the local DB (phone_number /
// password below) with a default address. See runbook.md "Credentials".
// WRITE workflow: every iteration creates a real `orders` row (COD only, no
// money moves). Keep VUs/duration modest outside smoke to avoid DB bloat;
// re-seed with `php artisan migrate:fresh --seed` after heavy runs if needed.
//
// Run:
//   k6 run tests/w4-cart-checkout/smoke.js
//   BASE_URL=http://localhost:8020 k6 run tests/w4-cart-checkout/smoke.js

import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8020';
const PRODUCT_ID = __ENV.PRODUCT_ID || '1';
const PHONE_NUMBER = __ENV.TEST_PHONE || '0900000001';
const PASSWORD = __ENV.TEST_PASSWORD || 'k6-load-test-password';
const USER_AGENT =
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36';

export const options = {
  userAgent: USER_AGENT,
  vus: 1,
  iterations: 3,
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<800'],
    checks: ['rate>0.99'],
  },
};

function xsrfHeaderFrom(jar, url) {
  const cookies = jar.cookiesForURL(url);
  const raw = cookies['XSRF-TOKEN'] && cookies['XSRF-TOKEN'][0];
  return raw ? decodeURIComponent(raw) : '';
}

export default function () {
  const jar = http.cookieJar();

  // 1. Warm the session + get an XSRF-TOKEN cookie.
  let res = http.get(`${BASE_URL}/`, {
    headers: { Accept: 'text/html,application/xhtml+xml,*/*;q=0.8' },
    tags: { name: 'Home' },
  });
  check(res, { 'home 200': (r) => r.status === 200 });

  // 2. Login -- session cookie (for CSRF-protected cart routes) AND a
  //    Sanctum bearer token (required by checkout, which is auth:sanctum).
  let xsrf = xsrfHeaderFrom(jar, BASE_URL);
  res = http.post(
    `${BASE_URL}/api/auth/login`,
    JSON.stringify({ phone_number: PHONE_NUMBER, password: PASSWORD }),
    {
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-XSRF-TOKEN': xsrf,
        Referer: `${BASE_URL}/`,
      },
      tags: { name: 'Login' },
    },
  );
  check(res, {
    'login 200': (r) => r.status === 200,
    'login has token': (r) => r.json('data.token') != null,
  });
  const token = res.json('data.token');

  sleep(Math.random() * 1 + 0.5);

  // 3. Add product to cart (session-authenticated, CSRF-protected).
  xsrf = xsrfHeaderFrom(jar, BASE_URL);
  res = http.post(
    `${BASE_URL}/api/cart/items`,
    JSON.stringify({ product_id: Number(PRODUCT_ID), quantity: 1 }),
    {
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-XSRF-TOKEN': xsrf,
        Referer: `${BASE_URL}/`,
      },
      tags: { name: 'AddToCart' },
    },
  );
  check(res, {
    'cart add 200': (r) => r.status === 200,
    'cart has item': (r) => (r.json('data.items_count') || 0) >= 1,
  });

  sleep(Math.random() * 1 + 0.5);

  // 4. Checkout summary (bearer-token authenticated).
  res = http.get(`${BASE_URL}/api/checkout/summary`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    tags: { name: 'CheckoutSummary' },
  });
  check(res, {
    'summary 200': (r) => r.status === 200,
    'summary has address': (r) => (r.json('data.addresses') || []).length > 0,
  });
  const addressId = res.json('data.addresses.0.id');

  sleep(Math.random() * 1 + 0.5);

  // 5. Place the order (COD/"cash" -- no real payment gateway).
  xsrf = xsrfHeaderFrom(jar, BASE_URL);
  res = http.post(
    `${BASE_URL}/api/checkout`,
    JSON.stringify({ address_id: addressId, payment_method: 'cash' }),
    {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-XSRF-TOKEN': xsrf,
        Referer: `${BASE_URL}/`,
      },
      tags: { name: 'Checkout' },
    },
  );
  check(res, {
    'checkout 201': (r) => r.status === 201,
    'checkout created order': (r) => (r.json('data.orders_count') || 0) >= 1,
  });

  sleep(1);
}
