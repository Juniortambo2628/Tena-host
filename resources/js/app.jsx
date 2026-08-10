import './bootstrap';
import '../css/app.css';

import { createRoot, hydrateRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ToastProvider, notify } from '@/Components/Toast';

const appName = import.meta.env.VITE_APP_NAME || 'Tena';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        if (import.meta.env.SSR) {
            hydrateRoot(el, <App {...props} />);
            return;
        }

        createRoot(el).render(
            <>
                <ToastProvider />
                <App {...props} />
            </>
        );
    },
    progress: {
        color: '#FFD300',
    },
});

router.on('error', (event) => {
    const errors = event.detail?.errors;
    if (errors && typeof errors === 'object') {
        const first = Object.values(errors)[0];
        const msg = Array.isArray(first) ? first[0] : (typeof first === 'string' ? first : null);
        if (msg) {
            notify.error(msg);
            return;
        }
    }
    const message = event.detail?.message || event.detail?.error;
    if (message) {
        notify.error(message);
    }
});

router.on('invalid', (event) => {
    event.preventDefault();
    notify.error('This page is no longer available. Redirecting...');
    setTimeout(() => {
        window.location.href = event.detail.url;
    }, 1500);
});
