// k6 AVERAGE load test — full site, all 4 workflows combined.
//
// Runs w1-browse-home, w2-vendor-browse, w3-product-browse (read-only) as
// weighted concurrent scenarios, plus w4-cart-checkout (write) at low volume
// since every iteration creates a real `orders` row locally.
//
// Run:
//   k6 run tests/full-site-average.js
//   BASE_URL=http://localhost:8020 k6 run tests/full-site-average.js

import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8020';
const VENDOR_ID = __ENV.VENDOR_ID || '1';
const PRODUCT_ID = __ENV.PRODUCT_ID || '1';
const PHONE_NUMBER = __ENV.TEST_PHONE || '0900000001';
const PASSWORD = __ENV.TEST_PASSWORD || 'k6-load-test-password';
const USER_AGENT =
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36';

export const options = {
  userAgent: USER_AGENT,
  scenarios: {
    browse_home: {
      executor: 'ramping-vus',
      exec: 'browseHome',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 15 },
        { duration: '2m', target: 15 },
        { duration: '30s', target: 0 },
      ],
    },
    vendor_browse: {
      executor: 'ramping-vus',
      exec: 'vendorBrowse',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 10 },
        { duration: '2m', target: 10 },
        { duration: '30s', target: 0 },
      ],
    },
    product_browse: {
      executor: 'ramping-vus',
      exec: 'productBrowse',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 10 },
        { duration: '2m', target: 10 },
        { duration: '30s', target: 0 },
      ],
    },
    cart_checkout: {
      executor: 'ramping-vus',
      exec: 'cartCheckout',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 2 },
        { duration: '2m', target: 2 },
        { duration: '30s', target: 0 },
      ],
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    checks: ['rate>0.99'],
    'http_req_duration{name:Home}': ['p(95)<500'],
    'http_req_duration{name:VendorShow}': ['p(95)<600'],
    'http_req_duration{name:ProductsList}': ['p(95)<500'],
    'http_req_duration{name:ProductShow}': ['p(95)<600'],
    'http_req_duration{name:Checkout}': ['p(95)<1000'],
  },
};

function xsrfHeaderFrom(jar, url) {
  const cookies = jar.cookiesForURL(url);
  const raw = cookies['XSRF-TOKEN'] && cookies['XSRF-TOKEN'][0];
  return raw ? decodeURIComponent(raw) : '';
}

export function browseHome() {
  const res = http.get(`${BASE_URL}/`, { tags: { name: 'Home' } });
  check(res, { 'home 200': (r) => r.status === 200 });
  sleep(Math.random() * 3 + 1);
}

export function vendorBrowse() {
  let res = http.get(`${BASE_URL}/`, { tags: { name: 'Home' } });
  check(res, { 'home 200': (r) => r.status === 200 });
  sleep(Math.random() * 2 + 1);

  res = http.get(`${BASE_URL}/vendors/${VENDOR_ID}`, {
    headers: { Referer: `${BASE_URL}/` },
    tags: { name: 'VendorShow' },
  });
  check(res, { 'vendor show 200': (r) => r.status === 200 });
  sleep(Math.random() * 2 + 1);
}

export function productBrowse() {
  let res = http.get(
    `${BASE_URL}/product-type/select?preferred_product_type=agriculture&redirect_to=home`,
    { tags: { name: 'ProductTypeSelect' } },
  );
  check(res, { 'product-type select 200': (r) => r.status === 200 });
  sleep(Math.random() * 1 + 0.5);

  res = http.get(`${BASE_URL}/products`, {
    headers: { Referer: `${BASE_URL}/` },
    tags: { name: 'ProductsList' },
  });
  check(res, { 'products list 200': (r) => r.status === 200 });
  sleep(Math.random() * 2 + 1);

  res = http.get(`${BASE_URL}/products/${PRODUCT_ID}`, {
    headers: { Referer: `${BASE_URL}/products` },
    tags: { name: 'ProductShow' },
  });
  check(res, { 'product show 200': (r) => r.status === 200 });
  sleep(Math.random() * 2 + 1);
}

export function cartCheckout() {
  const jar = http.cookieJar();

  let res = http.get(`${BASE_URL}/`, { tags: { name: 'Home' } });
  check(res, { 'home 200': (r) => r.status === 200 });

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
  check(res, { 'login 200': (r) => r.status === 200 });
  const token = res.json('data.token');
  if (!token) return;

  sleep(Math.random() * 1 + 0.5);

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
  check(res, { 'cart add 200': (r) => r.status === 200 });

  sleep(Math.random() * 1 + 0.5);

  res = http.get(`${BASE_URL}/api/checkout/summary`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    tags: { name: 'CheckoutSummary' },
  });
  check(res, { 'summary 200': (r) => r.status === 200 });
  const addressId = res.json('data.addresses.0.id');
  if (!addressId) return;

  sleep(Math.random() * 1 + 0.5);

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
  check(res, { 'checkout 201': (r) => r.status === 201 });

  sleep(1);
}
