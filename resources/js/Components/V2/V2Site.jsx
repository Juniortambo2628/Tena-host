import React, { useMemo } from 'react';
import { usePage } from '@inertiajs/react';
import { InertiaRouter } from '@okjt/inertia';
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
 *    The kit's InertiaRouter (@okjt/inertia, made from this file's own bridge)
 *    joins them: a link to another page is an Inertia visit, a link to a
 *    section on this page just moves to it, and links to other sites stay
 *    plain links.
 * 2. The kit's words, links and logos come from the same CMS as the live
 *    pages (Admin -> Public Pages -> Site-wide), so header, footer and SEO are
 *    edited in one place for both.
 */
export default function V2Site({ places, media, joinHref = '#paths', children }) {
    const { url, props } = usePage();
    // Set before the sections render, so their links follow the current address.
    v2Base = props.v2Base ?? (url.startsWith('/v2') ? '/v2' : '');
    const site = useV2SiteConfig({ places, media, joinHref });

    return (
        <InertiaRouter>
            <SiteProvider site={site}>
                <div className="v2-site">{children}</div>
            </SiteProvider>
        </InertiaRouter>
    );
}

// "/v2" while previewing the v2 pages, "" once they're the live site (the page's v2Base prop).
let v2Base = '/v2';

/** A link to an audience page ("/hosts#join") stays on the v2 pages: /v2/hosts in the preview, /hosts live. */
export function v2Link(href) {
    return /^\/(hosts|business|events)(?=$|[#?])/.test(href || '') ? `${v2Base}${href}` : href;
}

/** Kept stable, so the site settings aren't rebuilt on every render. */
const NONE = {};

/** A photo "key" is its URL: the CMS stores uploaded pictures as paths. */
const PHOTOS = { url: (key) => key || '', alt: () => '' };

/**
 * The kit's `site` settings, from the CMS's site-wide sections. A page adds
 * its own pictures: `places` (photo by name, e.g. "group-reviews") and
 * `media` (screens shown in a phone or browser frame, by key).
 */
function useV2SiteConfig({ places = NONE, media = NONE, joinHref }) {
    const { site = {}, seo = {}, auth, pricingPage } = usePage().props;
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
            // The live header's links, pointing at the v2 pages (#anchors at the v2 homepage).
            nav: extractItems(header, 'links', ['label', 'href'])
                .map((l) => ({ label: stripHtml(l.label), to: stripHtml(l.href) }))
                .filter((l) => l.label && l.to)
                // Once /pricing is on (Admin -> Settings), the header's Pricing link goes there.
                .map((l) => (pricingPage && /^\/?#pricing$/.test(l.to) ? { ...l, to: pricingPage } : l))
                .map((l) => ({ ...l, to: l.to.startsWith('#') ? `${v2Base || '/'}${l.to}` : v2Link(l.to) })),
            useAccount: () => ({ signedIn }),
            // TenaFi has no public sign-in (it's fully managed): the quieter link is a WhatsApp chat.
            accountLinks: {
                signIn: whatsapp ? { to: `https://wa.me/${whatsapp}`, label: 'WhatsApp us' } : { to: '/hosts#faq', label: 'Questions' },
                signUp: { to: joinHref, label: stripHtml(getContent(header, 'join_label', 'Book now')) || 'Book now' },
                home: { to: '/dashboard', label: 'Your dashboard' },
            },
            usePhotos: () => PHOTOS,
            usePlaces: () => (place) => places[place] ?? place,
            media,
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
    }, [site, seo, brand, signedIn, places, media, joinHref, v2Base, pricingPage]);
}
