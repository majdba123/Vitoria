// k6 SMOKE test — w1-browse-home
// Visit the home page (protocol-level). Sanity check before scaling load.
//
// Run:
//   k6 run tests/w1-browse-home/smoke.js
//   BASE_URL=http://localhost:8020 k6 run tests/w1-browse-home/smoke.js

import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8020';
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
  },
};

export default function () {
  const res = http.get(`${BASE_URL}/`, {
    headers: { Accept: 'text/html,application/xhtml+xml,*/*;q=0.8' },
    tags: { name: 'Home' },
  });
  check(res, { 'home 200': (r) => r.status === 200 });

  sleep(Math.random() * 2 + 1);
}
