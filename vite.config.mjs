import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.jsx',
                'resources/js/legacy.js',
            ],
            refresh: true,
        }),
        react(),
    ],
    // Set by docker-compose: listen on all interfaces inside the container,
    // but point the browser's HMR websocket at the published localhost port.
    server: process.env.VITE_DOCKER
        ? {
              host: '0.0.0.0',
              port: 5173,
              strictPort: true,
              hmr: { host: 'localhost' },
              watch: { usePolling: true },
          }
        : undefined,
});
