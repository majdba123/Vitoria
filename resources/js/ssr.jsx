import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import ReactDOMServer from 'react-dom/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createServer((page) =>
    createInertiaApp({
        page,
        title: (title) => (title ? `${title} · Vetora` : 'Vetora'),
        render: ReactDOMServer.renderToString,
        resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
        setup: ({ App, props }) => <App {...props} />,
    }),
);
