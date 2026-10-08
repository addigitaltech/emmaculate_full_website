// Simple load test with k6 (https://k6.io). Run:  k6 run -e BASE=https://www.yourschool.com deploy/loadtest.js
// Start small (e.g. 50 users) and raise the numbers while watching the server's CPU and "entry processes".
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '1m', target: 100 },
    { duration: '3m', target: 500 },
    { duration: '1m', target: 0 },
  ],
  thresholds: { http_req_failed: ['rate<0.02'], http_req_duration: ['p(95)<2500'] },
};

const BASE = __ENV.BASE || 'http://localhost:8000';
const PAGES = ['/', '/about', '/academics', '/admissions', '/news', '/gallery', '/contact', '/check-result'];

export default function () {
  const page = PAGES[Math.floor(Math.random() * PAGES.length)];
  const res = http.get(BASE + page);
  check(res, { 'status is 200': (r) => r.status === 200 });
  sleep(1 + Math.random() * 3);
}
