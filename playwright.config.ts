import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 2 : undefined,
    reporter: [
        ['line'],
        ['html', { outputFolder: 'storage/framework/testing/playwright-report', open: 'never' }],
    ],
    outputDir: 'storage/framework/testing/playwright-results',
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'https://smartlms.io.vn',
        locale: 'vi-VN',
        timezoneId: 'Asia/Ho_Chi_Minh',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'off',
    },
    projects: [
        {
            name: 'desktop',
            use: { ...devices['Desktop Chrome'], channel: 'chrome' },
        },
        {
            name: 'mobile',
            use: { ...devices['Pixel 7'], channel: 'chrome' },
        },
    ],
});
