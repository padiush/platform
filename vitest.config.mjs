import react from '@vitejs/plugin-react';
import path from 'node:path';
import { defineConfig } from 'vitest/config';

// Deliberately separate from vite.config.mjs: the laravel-vite-plugin
// expects an artisan context that doesn't exist under the test runner.
export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            '@': path.resolve(import.meta.dirname, 'resources/js'),
        },
    },
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['resources/js/tests/setup.js'],
        include: ['resources/js/**/*.test.{js,jsx}'],
    },
});
