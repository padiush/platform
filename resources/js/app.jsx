// Self-hosted, so no third party sees who opens the application. Imported
// here rather than from app.css, where Tailwind would inline it and break the
// relative URLs of its font files.
import '@fontsource-variable/montserrat';
import 'driver.js/dist/driver.css';
import '../css/app.css';
import './bootstrap';
import './i18n.js';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.APP_NAME || 'Padiush';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob(['./Pages/**/*.jsx', '!./Pages/**/*.test.jsx']),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
