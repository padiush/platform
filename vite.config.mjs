import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

// Where the browser reaches the dev server. On a plain checkout that is the
// published localhost port; behind a reverse proxy a compose override supplies
// the public URL, so module requests and the HMR socket go through it too.
const devOrigin = process.env.VITE_DEV_ORIGIN ?? 'http://localhost:5173';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        react(),
    ],
    // Set by docker-compose: listen on all interfaces inside the container,
    // but point the browser at the dev origin and its HMR websocket.
    server: process.env.VITE_DOCKER
        ? {
              host: '0.0.0.0',
              port: 5173,
              strictPort: true,
              origin: devOrigin,
              allowedHosts: [new URL(devOrigin).hostname],
              hmr: {
                  host: process.env.VITE_HMR_HOST ?? 'localhost',
                  clientPort: Number(process.env.VITE_HMR_CLIENT_PORT ?? 5173),
                  protocol: process.env.VITE_HMR_PROTOCOL ?? 'ws',
              },
              watch: { usePolling: true },
          }
        : undefined,
});
