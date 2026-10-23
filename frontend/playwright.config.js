import { defineConfig, devices } from '@playwright/test'

/**
 * End-to-end tests run against the Docker stack.
 * Production stack: http://localhost:8080 (default). Development: E2E_BASE_URL=http://localhost:5173
 *
 * Tests sign in through e2e/fixtures.js (a fresh session per test). Accounts default to the local
 * demo users; override them with E2E_EMAIL / E2E_PASSWORD (admin) and E2E_VIEWER_EMAIL / E2E_VIEWER_PASSWORD.
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  retries: process.env.CI ? 2 : 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8080',
    locale: 'es-CO',
    timezoneId: 'America/Bogota',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'mobile', use: { ...devices['Pixel 7'] }, testMatch: /responsive\.spec\.js/ },
  ],
})
