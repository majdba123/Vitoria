// k6 SMOKE test — w2-vendor-browse
// Home page (vendor listing lives there) -> a vendor's show page.
//
// Run:
//   k6 run tests/w2-vendor-browse/smoke.js
//   BASE_URL=http://localhost:8020 VENDOR_ID=1 k6 run tests/w2-vendor-browse/smoke.js

import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8020';
const VENDOR_ID = __ENV.VENDOR_ID || '1';
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
    'http_req_duration{name:Home}': ['p(95)<500'],
    'http_req_duration{name:VendorShow}': ['p(95)<600'],
  },
};

export default function () {
  let res = http.get(`${BASE_URL}/`, {
    headers: { Accept: 'text/html,application/xhtml+xml,*/*;q=0.8' },
    tags: { name: 'Home' },
  });
  check(res, { 'home 200': (r) => r.status === 200 });

  sleep(Math.random() * 2 + 1);

  res = http.get(`${BASE_URL}/vendors/${VENDOR_ID}`, {
    headers: { Accept: 'text/html,application/xhtml+xml,*/*;q=0.8', Referer: `${BASE_URL}/` },
    tags: { name: 'VendorShow' },
  });
  check(res, { 'vendor show 200': (r) => r.status === 200 });

  sleep(Math.random() * 2 + 1);
}
