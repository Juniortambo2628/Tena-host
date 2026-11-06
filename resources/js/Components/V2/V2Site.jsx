import React, { useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Router, createPath } from 'react-router-dom';
import { SiteProvider } from '@okjt/site';
import { ShieldCheck, Wifi, Sparkles } from 'lucide-react';
import { getContent, extractItems, stripHtml } from '@/lib/cms';
import { useBrand } from '@/Components/BrandLogo';
import './V2Site.css';

/**
 * The frame for the v2 public pages (/v2…), built with the OKJT kit
 * (@okjt/site). Two jobs:
 *
 * 1. The kit's links and nav use React Router; TenaFi navigates with Inertia.
 *    A Router with our own navigator bridges them: a link to another page is
 *    an Inertia visit, a link to a section on this page just moves to it, and
 *    links to other sites stay plain links (React Router renders those itself).
 * 2. The kit's words, links and logos come from the same CMS as the live
 *    pages (Admin -> Public Pages -> Site-wide), so header, footer and SEO are
 *    edited in one place for both.
 */
export default function V2Site({ children }) {
    const { url } = usePage();
    const location = useInertiaLocation(url);
    const navigator = useMemo(() => ({
        createHref: (to) => (typeof to === 'string' ? to : createPath(to)),
        encodeLocation: (to) => to,
        go: (delta) => window.history.go(delta),
        push: (to) => navigate(to, false),
        replace: (to) => navigate(to, true),
    }), []);
    const site = useV2SiteConfig();

    return (
        <Router location={location} navigator={navigator}>
            <SiteProvider site={site}>
                <div className="v2-site">{children}</div>
            </SiteProvider>
        </Router>
    );
}

/** The current address as React Router expects it, following #section changes too. */
function useInertiaLocation(url) {
    const [hash, setHash] = useState(() => (typeof window === 'undefined' ? '' : window.location.hash));
    useEffect(() => {
        const sync = () => setHash(window.location.hash);
        sync();
        window.addEventListener('hashchange', sync);
        window.addEventListener('popstate', sync);
        return () => {
            window.removeEventListener('hashchange', sync);
            window.removeEventListener('popstate', sync);
        };
    }, [url]);

    const [path, search = ''] = url.split('#')[0].split('?');
    return { pathname: path || '/', search: search ? `?${search}` : '', hash, state: null, key: hash || 'default' };
}

/** A kit link was followed: stay on the page for #sections, otherwise visit through Inertia. */
function navigate(to, replace) {
    const target = typeof to === 'string' ? to : createPath(to);
    const next = new URL(target, window.location.href);

    if (next.origin !== window.location.origin) {
        window.location.assign(next.href);
        return;
    }
    if (next.pathname === window.location.pathname && next.search === window.location.search) {
        // Same page: change the #hash; the kit's layout scrolls to it.
        window.history[replace ? 'replaceState' : 'pushState'](window.history.state, '', next.hash || next.pathname);
        window.dispatchEvent(new HashChangeEvent('hashchange'));
        return;
    }
    router.visit(next.pathname + next.search + next.hash, { replace });
}

/** A photo "key" is its URL: the CMS stores uploaded pictures as paths. */
const PHOTOS = { url: (key) => key || '', alt: () => '' };

/** The kit's `site` settings, from the CMS's site-wide sections. */
function useV2SiteConfig() {
    const { site = {}, seo = {}, auth } = usePage().props;
    const brand = useBrand();
    const signedIn = Boolean(auth?.user);

    return useMemo(() => {
        const header = site.header;
        const footer = site.footer;
        const whatsapp = stripHtml(getContent(footer, 'whatsapp', '')).replace(/[^\d]/g, '');
        const email = stripHtml(getContent(footer, 'contact_email', ''));
        const groups = extractItems(footer, 'groups', ['title', 'links'])
            .map((g) => ({
                title: stripHtml(g.title),
                links: String(g.links || '').replace(/<br\s*\/?>|<\/p>/gi, '\n').split('\n')
                    .map((line) => line.split('|').map((part) => stripHtml(part)))
                    .filter(([label, href]) => label && href)
                    .map(([label, href]) => ({ label, to: href })),
            }))
            .filter((g) => g.title && g.links.length);
        const contact = [
            whatsapp && { label: 'WhatsApp us', to: `https://wa.me/${whatsapp}` },
            email && { label: email, to: `mailto:${email}` },
        ].filter(Boolean);

        return {
            brand: { name: brand.name, logo: { light: brand.dark || brand.footer, dark: brand.header } },
            // The live header's links, with on-page #anchors pointing at the live homepage.
            nav: extractItems(header, 'links', ['label', 'href'])
                .map((l) => ({ label: stripHtml(l.label), to: stripHtml(l.href) }))
                .filter((l) => l.label && l.to)
                .map((l) => ({ ...l, to: l.to.startsWith('#') ? `/${l.to}` : l.to })),
            useAccount: () => ({ signedIn }),
            // TenaFi has no public sign-in (it's fully managed): the quieter link is a WhatsApp chat.
            accountLinks: {
                signIn: whatsapp ? { to: `https://wa.me/${whatsapp}`, label: 'WhatsApp us' } : { to: '/hosts#faq', label: 'Questions' },
                signUp: { to: '#paths', label: stripHtml(getContent(header, 'join_label', 'Book now')) || 'Book now' },
                home: { to: '/dashboard', label: 'Your dashboard' },
            },
            usePhotos: () => PHOTOS,
            usePlaces: () => (place) => place,
            media: {},
            footer: {
                about: stripHtml(getContent(footer, 'description', 'Africa’s guest relationship platform.')),
                columns: [...groups, ...(contact.length ? [{ title: 'Talk to us', links: contact }] : [])].slice(0, 3),
                promises: [
                    { icon: Wifi, text: 'Works on the WiFi you already have' },
                    { icon: ShieldCheck, text: 'Guests agree before they’re added' },
                    { icon: Sparkles, text: 'Installed and run for you' },
                ],
                cta: null,
                watermark: 'tenafi.',
                // The kit adds "All rights reserved." after it.
                copyright: brand.name,
                madeBy: { label: 'Built by', name: 'OKJ Technologies', href: 'https://okjtech.co.ke', logo: '/brand/okjt-logo.png' },
            },
            seo: {
                origin: typeof window === 'undefined' ? '' : window.location.origin,
                siteName: seo.site_name || brand.name,
                locale: 'en_KE',
                defaultTitle: seo.title || brand.name,
                defaultDescription: seo.description || '',
                // No pages listed: every v2 page stays noindex while it's a preview.
                pages: {},
            },
        };
    }, [site, seo, brand, signedIn]);
}
