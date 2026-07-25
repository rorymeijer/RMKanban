import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright e2e voor de kritieke flow. Draait tegen een lokaal geserveerde
 * instantie (sqlite) — zie de webServer-configuratie hieronder.
 */
export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    fullyParallel: false,
    retries: process.env.CI ? 1 : 0,
    reporter: 'list',
    use: {
        baseURL: process.env.APP_URL ?? 'http://127.0.0.1:8000',
        trace: 'on-first-retry',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: process.env.PW_NO_SERVER
        ? undefined
        : {
              command: 'php artisan serve --port=8000',
              url: 'http://127.0.0.1:8000/api/health',
              reuseExistingServer: !process.env.CI,
              timeout: 60_000,
          },
});
