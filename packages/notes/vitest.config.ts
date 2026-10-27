import path from 'path';
import { defineConfig } from 'vitest/config';

/**
 * Unit tests for client-side logic (editor helpers, formatters, composables).
 * Kept apart from vite.config.ts so tests don't load the Laravel/Nuxt UI plugins.
 */
export default defineConfig({
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    test: {
        include: ['resources/js/**/*.test.ts'],
        environment: 'node',
        css: false,
    },
});
