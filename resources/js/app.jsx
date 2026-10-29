import './bootstrap';
import '../css/app.css';

import { createRoot, hydrateRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ToastProvider, notify } from '@/Components/Toast';
import { BRAND_COLORS } from '@/constants';

// Pages in these folders share one persistent DashboardLayout, so navigating between
// them swaps only the page. A page can opt out with `Page.layout = null`.
const DASHBOARD_PAGES = /^(Host|Admin|Staff|Profile)\//;

// APP_NAME is often left as Laravel's default; fall back to the product name.
const appName = [undefined, '', 'Laravel'].includes(import.meta.env.VITE_APP_NAME) ? 'TenaFi' : import.meta.env.VITE_APP_NAME;

createInertiaApp({
    // Public pages pass full CMS titles ("TenaFi | ..."); don't suffix those.
    title: (title) => (!title ? appName : title.includes(appName) ? title : `${title} - ${appName}`),
    resolve: async (name) => {
        const page = await resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx'));
        if (DASHBOARD_PAGES.test(name) && page.default.layout === undefined) {
            // Loaded on demand so public pages don't download the dashboard shell.
            const { default: DashboardLayout } = await import('./Layouts/DashboardLayout.jsx');
            page.default.layout = (child) => <DashboardLayout>{child}</DashboardLayout>;
        }
        return page;
    },
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
        color: BRAND_COLORS.primary,
    },
});

// Sidebar links prefetch on hover; drop those cached pages once anything is saved.
router.on('before', (event) => {
    if (event.detail.visit.method !== 'get') {
        router.flushAll();
    }
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
