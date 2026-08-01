import http from 'node:http';
import https from 'node:https';
import { performance } from 'node:perf_hooks';

const baseUrl = (process.env.LOAD_BASE_URL ?? 'http://127.0.0.1:8000').replace(/\/$/, '');
const targetHost = process.env.LOAD_HOST
    ?? (/^http:\/\/(127\.0\.0\.1|localhost)(:\d+)?$/.test(baseUrl) ? 'smartlms.io.vn' : null);
const users = Math.max(1, Number.parseInt(process.env.LOAD_USERS ?? '50', 10));
const durationSeconds = Math.max(5, Number.parseInt(process.env.LOAD_DURATION ?? '30', 10));
const username = process.env.LOAD_USERNAME;
const password = process.env.LOAD_PASSWORD;
const p95Limit = Number.parseInt(process.env.LOAD_P95_LIMIT_MS ?? '1500', 10);
const errorRateLimit = Number.parseFloat(process.env.LOAD_ERROR_RATE_LIMIT ?? '0.01');
const paths = (process.env.LOAD_PATHS ?? '/dashboard,/courses,/assignments,/notifications')
    .split(',')
    .map((path) => path.trim())
    .filter(Boolean);

const metrics = {
    latencies: [],
    requests: 0,
    failures: 0,
    statuses: new Map(),
};

function mergeCookies(jar, response) {
    const values = typeof response.headers.getSetCookie === 'function'
        ? response.headers.getSetCookie()
        : [response.headers.get('set-cookie')].filter(Boolean);

    for (const value of values) {
        const [pair] = value.split(';');
        const separator = pair.indexOf('=');
        if (separator > 0) jar.set(pair.slice(0, separator), pair.slice(separator + 1));
    }
}

function cookieHeader(jar) {
    return [...jar.entries()].map(([key, value]) => `${key}=${value}`).join('; ');
}

function csrfToken(html) {
    return html.match(/name="_token"\s+value="([^"]+)"/)?.[1]
        ?? html.match(/value="([^"]+)"\s+name="_token"/)?.[1];
}

async function measuredRequest(url, options, jar) {
    const startedAt = performance.now();
    try {
        const target = new URL(url);
        const body = options?.body ?? null;
        const headers = {
            'User-Agent': 'SmartLMS-Load-Test/1.0',
            'Accept-Language': 'vi-VN,vi;q=0.9',
            ...(targetHost ? { Host: targetHost } : {}),
            ...(jar.size ? { Cookie: cookieHeader(jar) } : {}),
            ...options?.headers,
            ...(body ? { 'Content-Length': Buffer.byteLength(body) } : {}),
        };
        const response = await new Promise((resolve, reject) => {
            const client = target.protocol === 'https:' ? https : http;
            const request = client.request({
                hostname: target.hostname,
                port: target.port || undefined,
                path: `${target.pathname}${target.search}`,
                method: options?.method ?? 'GET',
                headers,
                rejectUnauthorized: process.env.LOAD_INSECURE_TLS !== '1',
            }, (incoming) => {
                const chunks = [];
                incoming.on('data', (chunk) => chunks.push(chunk));
                incoming.on('end', () => {
                    const content = Buffer.concat(chunks);
                    resolve({
                        status: incoming.statusCode ?? 0,
                        headers: {
                            get: (name) => incoming.headers[name.toLowerCase()] ?? null,
                            getSetCookie: () => incoming.headers['set-cookie'] ?? [],
                        },
                        text: async () => content.toString('utf8'),
                        arrayBuffer: async () => content,
                    });
                });
            });
            request.on('error', reject);
            if (body) request.write(body);
            request.end();
        });
        mergeCookies(jar, response);
        const elapsed = performance.now() - startedAt;
        metrics.latencies.push(elapsed);
        metrics.requests++;
        metrics.statuses.set(response.status, (metrics.statuses.get(response.status) ?? 0) + 1);

        const expectedStatuses = options?.expectedStatuses ?? [200];
        if (!expectedStatuses.includes(response.status)) metrics.failures++;
        return response;
    } catch (error) {
        metrics.latencies.push(performance.now() - startedAt);
        metrics.requests++;
        metrics.failures++;
        metrics.statuses.set('network', (metrics.statuses.get('network') ?? 0) + 1);
        return null;
    }
}

async function authenticate(jar) {
    if (!username || !password) return true;

    const loginPage = await measuredRequest(`${baseUrl}/login`, {}, jar);
    if (!loginPage || loginPage.status !== 200) return false;
    const token = csrfToken(await loginPage.text());
    if (!token) return false;

    const response = await measuredRequest(`${baseUrl}/login`, {
        method: 'POST',
        expectedStatuses: [302],
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ _token: token, login: username, password }).toString(),
    }, jar);

    return response?.status === 302 && response.headers.get('location')?.includes('/dashboard');
}

async function virtualUser(index, deadline) {
    const jar = new Map();
    const authenticated = await authenticate(jar);
    if (!authenticated) {
        metrics.failures++;
        return;
    }

    const targets = username && password ? paths : ['/', '/login'];
    let cursor = index % targets.length;
    while (performance.now() < deadline) {
        const response = await measuredRequest(`${baseUrl}${targets[cursor]}`, {}, jar);
        if (response) await response.arrayBuffer();
        cursor = (cursor + 1) % targets.length;
    }
}

function percentile(values, ratio) {
    if (values.length === 0) return 0;
    const sorted = [...values].sort((a, b) => a - b);
    return sorted[Math.min(sorted.length - 1, Math.ceil(sorted.length * ratio) - 1)];
}

console.log(`Bắt đầu kiểm thử tải: ${users} người dùng, ${durationSeconds} giây, ${baseUrl}`);
console.log(username && password ? `Luồng đã đăng nhập, ${paths.length} trang đọc.` : 'Luồng công khai; đặt LOAD_USERNAME và LOAD_PASSWORD để kiểm thử sau đăng nhập.');

const startedAt = performance.now();
const deadline = startedAt + durationSeconds * 1000;
await Promise.all(Array.from({ length: users }, (_, index) => virtualUser(index, deadline)));
const elapsedSeconds = (performance.now() - startedAt) / 1000;
const errorRate = metrics.requests > 0 ? metrics.failures / metrics.requests : 1;
const p50 = percentile(metrics.latencies, 0.5);
const p95 = percentile(metrics.latencies, 0.95);
const p99 = percentile(metrics.latencies, 0.99);

console.log('\nKết quả:');
console.log(`- Tổng yêu cầu: ${metrics.requests}`);
console.log(`- Tốc độ: ${(metrics.requests / elapsedSeconds).toFixed(1)} yêu cầu/giây`);
console.log(`- Độ trễ p50/p95/p99: ${p50.toFixed(0)}/${p95.toFixed(0)}/${p99.toFixed(0)} ms`);
console.log(`- Tỷ lệ lỗi: ${(errorRate * 100).toFixed(2)}%`);
console.log(`- Mã phản hồi: ${[...metrics.statuses.entries()].map(([status, count]) => `${status}=${count}`).join(', ')}`);

if (p95 > p95Limit || errorRate > errorRateLimit) {
    console.error(`Không đạt ngưỡng: p95 ≤ ${p95Limit} ms và tỷ lệ lỗi ≤ ${(errorRateLimit * 100).toFixed(2)}%.`);
    process.exitCode = 1;
} else {
    console.log('Đạt ngưỡng hiệu năng đã cấu hình.');
}
