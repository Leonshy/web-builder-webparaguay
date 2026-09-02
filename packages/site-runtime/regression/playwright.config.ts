import { defineConfig, devices } from '@playwright/test';

/**
 * Regresión visual de las 41 variantes de sección (§9 del Anexo A).
 * A mano no es viable mantenerlas.
 *
 *   npm run regression            corre la suite
 *   npm run regression:update     regenera las referencias
 */
export default defineConfig({
    testDir: '.',
    snapshotDir: './__screenshots__',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: 0,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        baseURL: 'http://127.0.0.1:8813',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 900 } },
        },
    ],
    webServer: {
        command: 'sh -c "cd .. && npm run build && php artisan serve --port=8813 --no-reload"',
        url: 'http://127.0.0.1:8813/variants/hero',
        timeout: 180_000,
        reuseExistingServer: true,
    },
});
