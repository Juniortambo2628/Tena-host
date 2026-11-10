import React, { useState } from 'react';
import { usePage } from '@inertiajs/react';
import { ArrowUpRight, Check, ChevronLeft, ChevronRight, X } from 'lucide-react';
import {
    ImageCard, Photo, Container, Eyebrow, PillLink, SectionHeading, SECTION_OFFSET,
    FeatureExplorer, ShowcaseCarousel, PricingSection, FaqSection, MediaFrame,
} from '@okjt/site';
import {
    getContent, getMedia, getUpload, getText, extractItems, extractJsonItems, sanitizeHtml, stripHtml,
    textLines, baseSectionKey, comingSoonBadge,
} from '@/lib/cms';
import { v2Link } from '@/Components/V2/V2Site';

/**
 * The v2 public pages' sections (OKJT kit design), shared by the v2 homepage
 * and the audience pages. Each takes a CMS section (the same content keys as
 * the live page's version of it) and the id its in-page links use.
 */
/** Where a section's in-page links point: its CMS anchor, or its type. */
export const DEFAULT_IDS = { path_cards: 'paths', detailed_features: 'features', comparison_table: 'compare', credibility: 'about', faq: 'faq', comparison: 'gap' };
export function sectionId(section) {
    const type = baseSectionKey(section.section_key);
    return stripHtml(getContent(section, 'anchor', '')) || DEFAULT_IDS[type] || type;
}

/** The section's chip in the "On this page" bar. */
export const NAV_LABELS = {
    comparison: 'The gap', detailed_features: 'Features', showcase: 'In action', how_it_works: 'How it works',
    comparison_table: 'Compared', credibility: 'Who we are', pricing: 'Pricing', faq: 'FAQ', cta_banner: 'Book a demo',
};
export function navLabel(section) {
    const type = baseSectionKey(section.section_key);
    return stripHtml(getContent(section, 'menu_label', '')) || NAV_LABELS[type] || stripHtml(section.title);
}

/** Bundled photos used until a section's own photo is uploaded. */
export const PHOTOS = [
    '/legacy/assets/Tena-Landing/Tena-Welcome-Divine.jpg',
    '/legacy/assets/Tena-Landing/Clients-view.jpg',
    '/legacy/assets/Tena-Landing/Tena-Features-1.jpg',
    '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg',
    '/legacy/assets/Tena-Landing/Tena-Welcome-Divine-1.jpg',
];

/** Real screens shipped with the app (public/legacy/assets/v2), by the feature row they show. */
export const SCREENS = {
    'wifi-page': 'screen-wifi-page.webp',
    reviews: 'screen-reviews-dashboard.webp',
    campaigns: 'screen-whatsapp-campaign.webp',
    extras: 'screen-mpesa-extras.webp',
    events: 'screen-event-wifi-page.webp',
};
// The bundled screens taller than a phone's width x 1.6 sit in a phone frame; the rest in a browser frame.
export const WIDE_SCREENS = /screen-(mpesa-extras|reviews-dashboard)/;
export const screen = (src, alt) => ({ src, alt, url: 'tena-fi.com', phone: !WIDE_SCREENS.test(src) });

/** Feature rows: rows (and their photos) by index, for FeatureExplorer's place(`group-…`). */
export function featureRows(section) {
    const count = Object.keys(section?.content || {}).filter((k) => /^sections\.\d+\.label$/.test(k)).length;
    return Array.from({ length: count }, (_, i) => ({
        i,
        id: stripHtml(getContent(section, `sections.${i}.anchor`, '')) || `feature-${i}`,
    }));
}

export const Html = ({ html, as: Tag = 'span', className }) => (
    <Tag className={className} dangerouslySetInnerHTML={{ __html: sanitizeHtml(html) }} />
);

/**
 * "Trusted by": the clients down the left (logo and name), and the selected
 * client's WiFi page in a phone on the right, laid out like "What you get".
 * On phones the clients are a row of logos to swipe, with the page under it.
 * Pictures come from the section's Media slots: partner_{i}_logo is the WiFi
 * page (splash) design, partner_{i}_mark the client's logo.
 */
export function Partners({ section }) {
    const partners = extractItems(section, 'partners', ['name', 'url', 'type_area'])
        .map((p, i) => ({
            i,
            name: stripHtml(p.name),
            url: stripHtml(p.url),
            area: stripHtml(p.type_area),
            shot: getUpload(section, `partner_${i}_logo`),
            logo: getUpload(section, `partner_${i}_mark`),
        }))
        .filter((p) => p.name);
    const [activeIndex, setActiveIndex] = useState(0);
    if (!partners.length) return null;

    const active = partners[Math.min(activeIndex, partners.length - 1)];
    const step = (by) => setActiveIndex((current) => (current + by + partners.length) % partners.length);

    return (
        <section className="v2-trusted py-20">
            <Container>
                <div className="grid gap-8 lg:grid-cols-12 lg:gap-12">
                    {/* min-w-0: lets the row of logos scroll inside the page on phones instead of widening it. */}
                    <div className="min-w-0 lg:col-span-5">
                        <SectionHeading
                            align="left"
                            as="h2"
                            title={stripHtml(getContent(section, 'title', 'Trusted by'))}
                            text={stripHtml(getContent(section, 'subtitle', ''))}
                            className="v2-trusted__heading"
                        />
                        <div role="tablist" aria-label="Clients" aria-orientation="vertical" className="v2-trusted__tabs">
                            {partners.map((p, n) => {
                                const selected = p === active;
                                return (
                                    <button
                                        key={p.i}
                                        type="button"
                                        role="tab"
                                        id={`trusted-tab-${p.i}`}
                                        aria-selected={selected}
                                        aria-controls="trusted-panel"
                                        onClick={() => setActiveIndex(n)}
                                        className={`v2-trusted__tab ${selected ? 'v2-trusted__tab--active' : ''}`}
                                    >
                                        <ClientLogo client={p} />
                                        <span className="min-w-0">
                                            <span className="v2-trusted__name">{p.name}</span>
                                            {p.area && <span className="v2-trusted__area">{p.area}</span>}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    <div id="trusted-panel" role="tabpanel" aria-labelledby={`trusted-tab-${active.i}`} className="v2-trusted__panel min-w-0 lg:col-span-7">
                        <div className="v2-trusted__card">
                            <ClientLogo client={active} large />
                            <div className="min-w-0">
                                <h3 className="!font-display text-2xl !font-normal tracking-tight !text-ink">{active.name}</h3>
                                {active.area && <p className="mt-1 text-sm text-soft">{active.area}</p>}
                                {active.url && (
                                    <a href={active.url} target="_blank" rel="noopener noreferrer" className="mt-3 inline-flex items-center gap-1 text-xs font-bold uppercase tracking-[0.15em] text-ink hover:text-accent">
                                        Visit {active.name} <ArrowUpRight className="h-3.5 w-3.5" aria-hidden="true" />
                                    </a>
                                )}
                            </div>
                        </div>
                        <div className="v2-trusted__phone">
                            {active.shot ? (
                                <MediaFrame key={active.i} media={{ src: active.shot, alt: `${active.name}’s TenaFi WiFi page`, phone: true }} variant="phone" />
                            ) : (
                                <div className="v2-trusted__empty">
                                    <ClientLogo client={active} large />
                                    <span>{active.name}’s WiFi page</span>
                                </div>
                            )}
                        </div>
                        {partners.length > 1 && (
                            <div className="v2-trusted__arrows">
                                <button type="button" onClick={() => step(-1)} aria-label="Previous client"><ChevronLeft className="h-5 w-5" aria-hidden="true" /></button>
                                <span aria-live="polite">{partners.indexOf(active) + 1} / {partners.length}</span>
                                <button type="button" onClick={() => step(1)} aria-label="Next client"><ChevronRight className="h-5 w-5" aria-hidden="true" /></button>
                            </div>
                        )}
                    </div>
                </div>
            </Container>
        </section>
    );
}

/** A client's logo on a white tile, or their initial until a logo is uploaded. */
function ClientLogo({ client, large = false }) {
    return (
        <span className={`v2-trusted__logo ${large ? 'v2-trusted__logo--large' : ''}`} aria-hidden="true">
            {client.logo ? <img src={client.logo} alt="" loading="lazy" decoding="async" /> : client.name.trim().charAt(0).toUpperCase()}
        </span>
    );
}

/** "Which one are you?": one photo card per audience page. */
export function Audiences({ section, id }) {
    const cards = extractItems(section, 'cards', ['label', 'title', 'description', 'href']);

    return (
        <section id={id} className={`${SECTION_OFFSET} py-24`}>
            <Container>
                <SectionHeading
                    eyebrow="Who it’s for"
                    title={stripHtml(getContent(section, 'title', 'One platform, three kinds of places'))}
                    text={stripHtml(getContent(section, 'subtitle', ''))}
                />
                <div className="mt-14 grid gap-5 lg:grid-cols-3">
                    {cards.map((card, i) => (
                        <ImageCard
                            key={i}
                            to={v2Link(stripHtml(card.href)) || `#${id}`}
                            image={getMedia(section, `card_${i}_image`, PHOTOS[i % PHOTOS.length])}
                            tags={[stripHtml(card.label)].filter(Boolean)}
                            title={stripHtml(card.title)}
                            text={stripHtml(card.description)}
                            className="min-h-[26rem]"
                        />
                    ))}
                </div>
            </Container>
        </section>
    );
}

/** The gap: today vs with TenaFi, side by side, and the numbers behind it. */
export function Gap({ section, id }) {
    const columns = extractItems(section, 'columns', ['label', 'points', 'text', 'highlight']);
    const stats = extractItems(section, 'stats', ['value', 'label']).filter((s) => stripHtml(s.value));
    const image = getUpload(section, 'image');
    const sources = stripHtml(getContent(section, 'sources', ''));

    return (
        <section id={id} className={`${SECTION_OFFSET} py-24`}>
            <Container>
                <SectionHeading
                    eyebrow={stripHtml(getContent(section, 'badge', ''))}
                    title={stripHtml(getContent(section, 'title', ''))}
                    text={stripHtml(getContent(section, 'subtitle', ''))}
                />
                {image && (
                    <div className="relative mt-12 aspect-[21/9] overflow-hidden rounded-[2rem]">
                        <Photo image={image} />
                    </div>
                )}
                <div className="mx-auto mt-12 grid max-w-5xl gap-5 md:grid-cols-2">
                    {columns.map((col, i) => {
                        const dark = String(col.highlight).trim() === '1';
                        const Icon = dark ? Check : X;
                        const points = textLines(col.points);
                        return (
                            <div key={i} className={`rounded-[2rem] p-8 sm:p-10 ${dark ? 'bg-ink text-white' : 'border border-line bg-white'}`}>
                                <div className={`text-xs font-semibold uppercase tracking-[0.2em] ${dark ? 'text-accent-soft' : 'text-muted'}`}>{stripHtml(col.label)}</div>
                                {stripHtml(col.text) && <p className={`mt-4 text-sm ${dark ? 'text-white/80' : 'text-soft'}`}>{stripHtml(col.text)}</p>}
                                <ul className="mt-6 space-y-4 text-sm leading-relaxed">
                                    {points.map((p, j) => (
                                        <li key={j} className="flex gap-3">
                                            <span className={`mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full ${dark ? 'bg-white/10 text-accent-soft' : 'bg-tint text-faint'}`}>
                                                <Icon className="h-3 w-3" aria-hidden="true" />
                                            </span>
                                            <span className={dark ? 'text-white/90' : 'text-soft'}>{p.text}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        );
                    })}
                </div>
                {stats.length > 0 && (
                    <dl className="mx-auto mt-14 grid max-w-5xl gap-8 sm:grid-cols-3">
                        {stats.map((s, i) => (
                            <div key={i} className="border-t border-line-strong pt-5">
                                <dd className="font-display text-5xl font-light tracking-tight text-ink">{stripHtml(s.value)}</dd>
                                <dt className="mt-2 text-sm text-soft">{stripHtml(s.label)}</dt>
                            </div>
                        ))}
                    </dl>
                )}
                {sources && <p className="mx-auto mt-10 max-w-5xl text-xs text-faint">{sources}</p>}
            </Container>
        </section>
    );
}

/** Feature rows as one explorer: a tab per row, its photo, its screen and its ticks. */
export function Features({ section, site, id, pathsHref }) {
    const groups = featureRows(section).map(({ i, id: rowId }) => {
        const ticks = extractJsonItems(section, `sections.${i}.features`)
            .map((f) => ({ text: stripHtml(f.title), badge: comingSoonBadge(site, f.feature) }))
            .filter((f) => f.text);
        const note = stripHtml(getContent(section, `sections.${i}.note`, ''));
        return {
            id: rowId,
            eyebrow: stripHtml(getContent(section, `sections.${i}.label`, '')),
            title: stripHtml(getContent(section, `sections.${i}.heading`, '')),
            to: v2Link(stripHtml(getContent(section, `sections.${i}.cta_href`, ''))) || pathsHref,
            media: `feature-${i}`,
            stats: [],
            features: ticks,
            summary: (
                <>
                    <p>{stripHtml(getContent(section, `sections.${i}.description`, ''))}</p>
                    {ticks.length > 0 && (
                        <ul className="mt-4 space-y-2">
                            {ticks.map((t, j) => (
                                <li key={j} className="flex gap-2">
                                    <Check className="mt-0.5 h-4 w-4 shrink-0 text-positive" aria-hidden="true" />
                                    <span>{t.text}{t.badge && <span className="ml-2 rounded-full bg-tint px-2 py-0.5 text-xs font-semibold text-accent">{t.badge}</span>}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                    {note && <p className="mt-4 text-xs text-muted">{note}</p>}
                </>
            ),
        };
    }).filter((g) => g.eyebrow);
    if (!groups.length) return null;

    const title = stripHtml(getContent(section, 'title', ''));
    return (
        <section id={id} className={`${SECTION_OFFSET} py-24`}>
            <Container>
                <SectionHeading
                    eyebrow="What you get"
                    title={title || 'Everything a WiFi login can do for you.'}
                    text={stripHtml(getContent(section, 'subtitle', ''))}
                />
                <div className="mt-14">
                    <FeatureExplorer groups={groups} href={(g) => g.to} linkLabel="Find your plan" countLabel="included" label="What TenaFi does" />
                </div>
            </Container>
        </section>
    );
}

/** "See it in action": real screens, one at a time. */
export function Showcase({ section, id }) {
    const items = extractItems(section, 'items', ['title', 'text', 'tags'])
        .map((item, i) => ({
            media: `showcase-${i}`,
            title: stripHtml(item.title),
            text: stripHtml(item.text),
            tags: stripHtml(item.tags).split(',').map((t) => t.trim()).filter(Boolean),
        }))
        .filter((item, i) => item.title && getUpload(section, `item_${i}_shot`));
    if (!items.length) return null;

    return (
        <section id={id} className={`${SECTION_OFFSET} bg-white py-24`}>
            <Container>
                <ShowcaseCarousel
                    eyebrow={stripHtml(getContent(section, 'badge', ''))}
                    title={stripHtml(getContent(section, 'title', ''))}
                    text={stripHtml(getContent(section, 'subtitle', ''))}
                    items={items}
                />
            </Container>
        </section>
    );
}

/** How it works: four numbered steps, and the cards under them. */
export function HowItWorks({ section, site, id }) {
    const steps = extractItems(section, 'steps', ['step', 'title', 'description', 'feature']).filter((s) => stripHtml(s.title));
    const cards = extractItems(section, 'cards', ['title', 'text']).filter((c) => stripHtml(c.title));

    return (
        <section id={id} className={`${SECTION_OFFSET} py-24`}>
            <Container>
                <SectionHeading
                    eyebrow={stripHtml(getContent(section, 'badge', '')) || 'How it works'}
                    title={stripHtml(getContent(section, 'title', 'How TenaFi works'))}
                    text={stripHtml(getContent(section, 'subtitle', ''))}
                />
                <ol className="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {steps.map((s, i) => {
                        const image = getUpload(section, `step_${i}_image`);
                        const badge = comingSoonBadge(site, s.feature);
                        return (
                            <li key={i} className="flex flex-col overflow-hidden rounded-[1.75rem] border border-line bg-white">
                                {image && <img src={image} alt="" loading="lazy" className="aspect-[4/3] w-full object-cover" />}
                                <div className="flex flex-1 flex-col p-6 sm:p-7">
                                    <span className="font-display text-5xl font-extralight tabular-nums text-accent">{String(stripHtml(s.step) || i + 1).padStart(2, '0')}</span>
                                    <h3 className="mt-4 !font-display text-xl !font-normal tracking-tight !text-ink">{stripHtml(s.title)}</h3>
                                    <p className="mt-2 text-sm leading-relaxed text-soft">{stripHtml(s.description)}</p>
                                    {badge && <span className="mt-4 self-start rounded-full bg-tint px-3 py-1 text-xs font-semibold text-accent">{badge}</span>}
                                </div>
                            </li>
                        );
                    })}
                </ol>
                {cards.length > 0 && (
                    <div className="mt-5 grid gap-5 md:grid-cols-2">
                        {cards.map((c, i) => (
                            <div key={i} className="rounded-[1.75rem] bg-ink p-7 text-white sm:p-8">
                                <h3 className="!font-display text-xl !font-normal !text-white">{stripHtml(c.title)}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-white/75">{stripHtml(c.text)}</p>
                            </div>
                        ))}
                    </div>
                )}
            </Container>
        </section>
    );
}

/** Free WiFi vs TenaFi, row by row (stacked on phones). */
export function CompareTable({ section, id }) {
    const rows = extractItems(section, 'rows', ['label', 'without', 'with']).filter((r) => stripHtml(r.label));
    const without = stripHtml(getContent(section, 'col_without', 'Free WiFi'));
    const withLabel = stripHtml(getContent(section, 'col_with', 'With TenaFi'));
    if (!rows.length) return null;

    return (
        <section id={id} className={`${SECTION_OFFSET} bg-white py-24`}>
            <Container>
                <SectionHeading
                    eyebrow={stripHtml(getContent(section, 'badge', ''))}
                    title={stripHtml(getContent(section, 'title', ''))}
                    text={stripHtml(getContent(section, 'subtitle', ''))}
                />
                <div className="mx-auto mt-14 max-w-5xl overflow-hidden rounded-[2rem] border border-line bg-paper">
                    <div className="hidden grid-cols-3 gap-6 border-b border-line px-8 py-4 text-xs font-semibold uppercase tracking-[0.2em] text-muted md:grid">
                        <span>{stripHtml(getContent(section, 'col_label', ''))}</span>
                        <span>{without}</span>
                        <span className="text-accent">{withLabel}</span>
                    </div>
                    {rows.map((r, i) => (
                        <div key={i} className="grid gap-3 border-b border-line px-6 py-5 last:border-b-0 md:grid-cols-3 md:gap-6 md:px-8">
                            <span className="font-medium text-ink">{stripHtml(r.label)}</span>
                            <span className="flex gap-2 text-sm text-soft">
                                <X className="mt-0.5 h-4 w-4 shrink-0 text-faint" aria-hidden="true" />
                                <span><span className="sr-only md:hidden">{without}: </span>{stripHtml(r.without)}</span>
                            </span>
                            <span className="flex gap-2 text-sm text-ink">
                                <Check className="mt-0.5 h-4 w-4 shrink-0 text-positive" aria-hidden="true" />
                                <span><span className="sr-only md:hidden">{withLabel}: </span>{stripHtml(r.with)}</span>
                            </span>
                        </div>
                    ))}
                </div>
            </Container>
        </section>
    );
}

/** Who we are: the founder's words, and the numbers behind them. */
export function Credibility({ section, id }) {
    const stats = extractItems(section, 'stats', ['value', 'label']).filter((s) => stripHtml(s.value));
    const quote = stripHtml(getContent(section, 'quote', ''));
    const image = getUpload(section, 'main_image');
    const sources = stripHtml(getContent(section, 'sources', ''));

    return (
        <section id={id} className={`${SECTION_OFFSET} py-24`}>
            <Container className="grid items-stretch gap-8 lg:grid-cols-2 lg:gap-14">
                <div className="group relative isolate flex min-h-[24rem] flex-col justify-end overflow-hidden rounded-[2rem] bg-ink p-8 text-white sm:p-10">
                    {image && <><Photo image={image} className="-z-10" /><div className="absolute inset-0 -z-10 bg-gradient-to-t from-ink-deep/90 via-ink-deep/40 to-transparent" /></>}
                    {quote && <blockquote className="!font-display text-3xl !font-light leading-snug tracking-tight !text-white sm:text-4xl">{quote}</blockquote>}
                    {stripHtml(getContent(section, 'quote_name', '')) && <p className="mt-6 text-sm text-white/70">{stripHtml(getContent(section, 'quote_name', ''))}</p>}
                </div>
                <div className="flex flex-col justify-center">
                    <SectionHeading
                        align="left"
                        eyebrow={stripHtml(getContent(section, 'badge', ''))}
                        title={stripHtml(getContent(section, 'title', ''))}
                        text={stripHtml(getContent(section, 'subtitle', ''))}
                    />
                    {stats.length > 0 && (
                        <dl className="mt-10 grid grid-cols-2 gap-6">
                            {stats.map((s, i) => (
                                <div key={i} className="border-t border-line-strong pt-4">
                                    <dd className="font-display text-4xl font-light tracking-tight text-ink">{stripHtml(s.value)}</dd>
                                    <dt className="mt-1 text-sm text-soft">{stripHtml(s.label)}</dt>
                                </div>
                            ))}
                        </dl>
                    )}
                    {sources && <p className="mt-8 text-xs text-faint">{sources}</p>}
                </div>
            </Container>
        </section>
    );
}

/** A page's plans (on /v2, the rentals plans copied from the live /hosts page). */
export function Pricing({ section, site, id, joinHref, devicesLink = true }) {
    // Once /pricing is switched on (Admin -> Settings), each page's plans link to it.
    const { pricingPage: devicesPage } = usePage().props;
    const pricingPage = devicesLink ? devicesPage : null;
    const plans = extractItems(section, 'plans', ['id', 'label', 'tagline', 'price', 'unit', 'features', 'cta', 'variant'])
        .filter((p) => stripHtml(p.label))
        .map((p, i) => ({
            id: stripHtml(p.id) || String(i),
            name: stripHtml(p.label),
            price: (
                <>
                    {stripHtml(p.price)}
                    {stripHtml(p.unit) && <span className="mt-1 block font-sans text-sm font-normal tracking-normal opacity-70">{stripHtml(p.unit)}</span>}
                </>
            ),
            tagline: stripHtml(p.tagline),
            lines: textLines(p.features, site).map((l) => (l.badge ? `${l.text} (${l.badge})` : l.text)),
            cta: { to: joinHref, label: stripHtml(p.cta) || 'Book a demo' },
            highlight: stripHtml(p.variant) === 'dark',
        }));
    if (!plans.length) return null;

    return (
        <div className={plans.length > 2 ? 'v2-pricing v2-pricing--wide' : 'v2-pricing'}>
            <PricingSection
                id={id}
                eyebrow={stripHtml(getContent(section, 'menu_label', '')) || 'Pricing'}
                title={stripHtml(getContent(section, 'title', ''))}
                text={stripHtml(getContent(section, 'subtitle', ''))}
                plans={plans}
                footnote={stripHtml(getContent(section, 'footnote', ''))}
            />
            {pricingPage && (
                <div className="v2-pricing__devices">
                    <PillLink to={pricingPage} variant="outline" arrow>See the devices and both ways to pay</PillLink>
                </div>
            )}
        </div>
    );
}

export function Faq({ section, id }) {
    const items = extractItems(section, 'items', ['question', 'answer'])
        .filter((item) => stripHtml(item.question))
        .map((item) => ({ q: stripHtml(item.question), a: <Html html={item.answer} /> }));
    if (!items.length) return null;

    return (
        <div className="bg-white">
            <FaqSection
                id={id}
                items={items}
                title={stripHtml(getContent(section, 'title', 'Questions, answered'))}
                text={stripHtml(getContent(section, 'subtitle', '')) || undefined}
            />
        </div>
    );
}

/** The "Book a demo" band: words and ticks over a photo, a button per audience. */
export function Trial({ section, site, id, joinHref }) {
    const whatsapp = getText(site.whatsapp_button, 'number').replace(/\D/g, '');
    const ticks = textLines(getContent(section, 'ticks', ''), site);
    const buttons = extractItems(section, 'buttons', ['label', 'href'])
        .map((b) => ({ label: stripHtml(b.label), href: stripHtml(b.href) }))
        .filter((b) => b.label)
        // An empty link goes to this page's sign-up, as on the live pages.
        .map((b) => ({ ...b, href: v2Link(b.href) || joinHref || '' }))
        .map((b) => (b.href.startsWith('whatsapp:')
            ? { ...b, href: whatsapp ? `https://wa.me/${whatsapp}?text=${encodeURIComponent(b.href.slice(9).trim())}` : '' }
            : b))
        .filter((b) => b.href);

    return (
        <section id={id} className={`${SECTION_OFFSET} py-16`}>
            <Container>
                <div className="group relative isolate overflow-hidden rounded-[2rem] px-6 py-16 text-center sm:px-12 lg:py-24">
                    <Photo image={getUpload(section, 'image') || '/legacy/assets/Tena-Landing/Tena-Welcome-Divine.jpg'} width={1800} className="-z-20" />
                    <div className="absolute inset-0 -z-10 bg-gradient-to-r from-tint via-tint/90 to-tint/40" />
                    <Eyebrow>{stripHtml(getContent(section, 'badge', '')) || 'Book a demo'}</Eyebrow>
                    <h2 className="mx-auto mt-4 max-w-3xl !font-display text-4xl !font-light leading-tight tracking-tight !text-ink sm:text-5xl lg:text-6xl">
                        {stripHtml(getContent(section, 'title', ''))}
                    </h2>
                    <p className="mx-auto mt-4 max-w-xl text-soft">{stripHtml(getContent(section, 'body', ''))}</p>
                    {ticks.length > 0 && (
                        <ul className="mt-6 flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm text-body">
                            {ticks.map((t, i) => <li key={i} className="inline-flex items-center gap-1.5"><Check className="h-4 w-4 text-positive" aria-hidden="true" />{t.text}</li>)}
                        </ul>
                    )}
                    {buttons.length > 0 && (
                        <div className="mx-auto mt-9 flex max-w-3xl flex-col flex-wrap justify-center gap-3 sm:flex-row">
                            {buttons.map((b, i) => (b.href.startsWith('https://wa.me/')
                                ? (
                                    <a key={i} href={b.href} target="_blank" rel="noopener noreferrer" className="inline-flex items-center justify-center gap-2 rounded-full bg-white px-7 py-3.5 text-sm font-semibold uppercase tracking-[0.08em] text-ink shadow-[0_10px_30px_-12px_color-mix(in_srgb,var(--color-ink)_35%,transparent)] hover:bg-tint">
                                        {b.label} <ArrowUpRight className="h-4 w-4" aria-hidden="true" />
                                    </a>
                                )
                                : <PillLink key={i} to={b.href} size="lg">{b.label}</PillLink>))}
                        </div>
                    )}
                </div>
            </Container>
        </section>
    );
}

