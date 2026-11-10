import React, { useMemo } from 'react';
import { Head } from '@inertiajs/react';
import {
    SiteLayout, PhotoHero, ImageCard, Container, PillLink, SectionHeading, SectionNav, SECTION_OFFSET,
    JourneyGlance, JourneyChapter,
} from '@okjt/site';
import V2Site from '@/Components/V2/V2Site';
import {
    Html, PHOTOS, screen, sectionId, navLabel, Gap, CompareTable, Credibility, Pricing, Faq, Trial,
} from '@/Components/V2/Sections';
import { PublicContext } from '@/Components/Public/PublicContext';
import SignupForm from '@/Components/Welcome/SignupForm';
import {
    getContent, getMedia, getUpload, extractItems, extractJsonItems, stripHtml, baseSectionKey, comingSoonBadge,
} from '@/lib/cms';

/**
 * A v2 audience page (/v2/hosts, /v2/business, /v2/events) on Naoa's journey
 * template: a photo hero with the page's headline numbers, then the journey
 * as numbered chapters (how it works, your first month, each feature row),
 * the rest of the page's sections in the order set in Admin -> Public Pages,
 * links to the other audiences, and the live page's own sign-up form.
 */
export default function V2Audience({ audience, sections = [], signup = {}, others = [], site = {}, seo = {} }) {
    const signupSection = (signup.sections || []).find((s) => s.section_key === 'signup');
    const signupId = (signupSection && stripHtml(getContent(signupSection, 'anchor', ''))) || 'join';
    const joinHref = signupSection ? `#${signupId}` : `/${audience}`;

    const hero = sections.find((s) => s.section_key === 'hero');
    const chapters = useChapters(sections, site, audience);
    const media = useMemo(() => Object.fromEntries(chapters.map((c) => [c.media, c.screen])), [chapters]);
    const glance = glanceNumbers(sections);

    // Body: every section the template has a version of, in CMS order, with all the chapters in one place.
    const body = [];
    sections.forEach((section) => {
        const type = baseSectionKey(section.section_key);
        if (CHAPTER_TYPES.includes(type)) {
            if (!body.some((b) => b.chapters)) body.push({ chapters: true, key: 'chapters' });
        } else if (BODY[type]) {
            body.push({ section, key: section.section_key, Section: BODY[type] });
        }
    });

    // "On this page", in page order. The trial band is a call to action, not a part to jump to.
    const nav = [
        ...body.flatMap((b) => {
            if (b.chapters) return chapters.map((c) => ({ id: c.id, label: c.label }));
            return baseSectionKey(b.section.section_key) === 'cta_banner' ? [] : [{ id: sectionId(b.section), label: navLabel(b.section) }];
        }),
        ...(others.length ? [{ id: 'other-places', label: 'Other places' }] : []),
        ...(signupSection ? [{ id: signupId, label: stripHtml(getContent(signupSection, 'menu_label', '')) || 'Book' }] : []),
    ];

    return (
        <V2Site media={media} joinHref={joinHref}>
            <Head title={seo.title || 'TenaFi'} />
            <SiteLayout overlay cta={false}>
                {hero && <AudienceHero hero={hero} glance={glance} joinHref={joinHref} audience={audience} ids={nav.map((n) => n.id)} />}
                {nav.length > 1 && <SectionNav label="On this page" numbered sections={nav} />}

                {body.map((b) => (b.chapters
                    ? (
                        <Container key={b.key} className="py-20">
                            {chapters.map((chapter, i) => <JourneyChapter key={chapter.id} chapter={chapter} index={i} detailsLabel="What’s included" />)}
                        </Container>
                    )
                    : <b.Section key={b.key} section={b.section} site={site} id={sectionId(b.section)} joinHref={joinHref} />))}

                {others.length > 0 && <OtherPlaces others={others} />}

                {signupSection && (
                    <section id={signupId} className={`${SECTION_OFFSET} v2-signup bg-white`}>
                        {/* The live page's form, with its own pages and plans, so it saves exactly as the live one does. */}
                        <PublicContext.Provider value={{ site, page: { slug: signup.page }, sections: signup.sections, joinHref }}>
                            <SignupForm section={signupSection} />
                        </PublicContext.Provider>
                    </section>
                )}
            </SiteLayout>
        </V2Site>
    );
}

/** Sections shown as chapters of the journey. */
const CHAPTER_TYPES = ['how_it_works', 'journey', 'detailed_features'];

/** Section type => its v2 version, for the sections after the journey. */
const BODY = {
    stats: Numbers,
    comparison: Gap,
    comparison_table: CompareTable,
    credibility: Credibility,
    pricing: Pricing,
    cta_banner: Trial,
    faq: Faq,
};

/** Real screens shown beside each chapter until the admin uploads one, per audience. */
const SCREENS = {
    hosts: ['screen-wifi-page.webp', 'screen-whatsapp-campaign.webp', 'screen-reviews-dashboard.webp', 'screen-mpesa-extras.webp'],
    business: ['screen-wifi-page.webp', 'screen-reviews-dashboard.webp', 'screen-whatsapp-campaign.webp', 'screen-mpesa-extras.webp'],
    events: ['screen-event-wifi-page.webp', 'screen-event-attendees.webp', 'screen-whatsapp-campaign.webp', 'screen-wifi-page.webp'],
};

/**
 * The journey: one chapter for how it works, one for "your first month" and
 * one per feature row, in CMS order. Each has a screen: an upload in the
 * section (chapter_shot, or the row's section_N_shot), else a bundled one.
 */
function useChapters(sections, site, audience) {
    return useMemo(() => {
        const chapters = [];
        sections.forEach((section) => {
            const type = baseSectionKey(section.section_key);
            const text = (key, fallback = '') => stripHtml(getContent(section, key, fallback));

            if (type === 'how_it_works') {
                const steps = extractItems(section, 'steps', ['title', 'description', 'feature']).filter((s) => stripHtml(s.title));
                chapters.push({
                    section, upload: getUpload(section, 'chapter_shot'),
                    id: sectionId(section),
                    label: text('menu_label') || 'How it works',
                    when: text('menu_label') || 'Getting set up',
                    title: text('title'),
                    intro: text('subtitle'),
                    steps: steps.map((s) => ({ title: withBadge(stripHtml(s.title), comingSoonBadge(site, s.feature)), text: stripHtml(s.description) })),
                    details: extractItems(section, 'cards', ['title', 'text']).filter((c) => stripHtml(c.title))
                        .map((c) => `${stripHtml(c.title)}: ${stripHtml(c.text)}`),
                });
            }

            if (type === 'journey') {
                chapters.push({
                    section, upload: getUpload(section, 'chapter_shot') || getUpload(section, 'step_1_shot'),
                    id: sectionId(section),
                    label: text('menu_label') || 'Your first month',
                    when: text('menu_label') || 'Your first month',
                    title: text('title'),
                    intro: text('subtitle'),
                    steps: extractItems(section, 'steps', ['title', 'text']).filter((s) => stripHtml(s.title))
                        .map((s) => ({ title: stripHtml(s.title), text: stripHtml(s.text) })),
                    details: extractItems(section, 'outcomes', ['title', 'text']).filter((o) => stripHtml(o.title))
                        .map((o) => `${stripHtml(o.title)}: ${stripHtml(o.text)}`),
                    tip: text('example_note') || null,
                });
            }

            if (type === 'detailed_features') {
                const count = Object.keys(section.content || {}).filter((k) => /^sections\.\d+\.label$/.test(k)).length;
                for (let i = 0; i < count; i++) {
                    // Rows are often numbered in the CMS ("1. Your guest homepage"); the chapters number themselves.
                    const label = text(`sections.${i}.label`).replace(/^\d+[.)]\s*/, '');
                    if (!label) continue;
                    const ticks = extractJsonItems(section, `sections.${i}.features`).filter((f) => stripHtml(f.title));
                    chapters.push({
                        section, upload: getUpload(section, `section_${i}_shot`),
                        id: text(`sections.${i}.anchor`) || `${section.section_key}-${i}`,
                        label,
                        when: label,
                        title: text(`sections.${i}.heading`),
                        intro: text(`sections.${i}.description`),
                        steps: ticks.map((f) => ({ title: withBadge(stripHtml(f.title), comingSoonBadge(site, f.feature)), text: stripHtml(f.desc) })),
                        details: [],
                        tip: text(`sections.${i}.note`) || null,
                    });
                }
            }
        });

        const bundled = SCREENS[audience] || SCREENS.hosts;
        return chapters.map(({ section, upload, ...chapter }, i) => ({
            ...chapter,
            details: chapter.details || [],
            media: `chapter-${i}`,
            screen: screen(upload || `/legacy/assets/v2/${bundled[i % bundled.length]}`, chapter.label),
        }));
    }, [sections, site, audience]);
}

const withBadge = (title, badge) => (badge ? `${title} (${badge})` : title);

/** Up to four headline numbers under the hero: the first-month example, else the page's stats. */
function glanceNumbers(sections) {
    const journey = sections.find((s) => baseSectionKey(s.section_key) === 'journey');
    const stats = sections.find((s) => baseSectionKey(s.section_key) === 'stats');
    const rows = [
        ...extractItems(journey, 'numbers', ['value', 'label']),
        ...extractItems(stats, 'stats', ['value', 'label']),
    ].map((r) => [stripHtml(r.value), stripHtml(r.label)]).filter(([value, label]) => value && label);
    // JourneyGlance keys rows by label.
    return rows.filter(([, label], i) => rows.findIndex(([, l]) => l === label) === i).slice(0, 4);
}

function AudienceHero({ hero, glance, joinHref, audience, ids }) {
    const primary = stripHtml(getContent(hero, 'cta_primary', 'Book my free trial'));
    // "#how": a part of this page when it has one, else that part of the live page.
    const secondaryRaw = stripHtml(getContent(hero, 'cta_secondary_url', ''));
    const secondaryHref = !secondaryRaw.startsWith('#') || ids.includes(secondaryRaw.slice(1)) ? secondaryRaw || `/${audience}` : `/${audience}${secondaryRaw}`;

    return (
        // The wrapper darkens the photo top to bottom (V2Site.css .v2-hero), so the words read clearly.
        <div className="v2-hero">
            <PhotoHero
                tall={glance.length > 0}
                image={getMedia(hero, 'main_image', PHOTOS[0])}
                phoneImage={getUpload(hero, 'phone_image') || undefined}
                eyebrow={stripHtml(getContent(hero, 'badge', ''))}
                title={<Html html={getContent(hero, 'title', '')} className="v2-hero-title" />}
                text={<Html html={getContent(hero, 'subtitle', '')} />}
                after={glance.length > 0 ? <JourneyGlance journey={{ glance }} /> : null}
            >
                <PillLink to={joinHref} variant="light" size="lg">{primary}</PillLink>
                {stripHtml(getContent(hero, 'cta_secondary', '')) && (
                    <PillLink to={secondaryHref} variant="glass" size="lg" arrow>
                        {stripHtml(getContent(hero, 'cta_secondary', ''))}
                    </PillLink>
                )}
            </PhotoHero>
        </div>
    );
}

/** A page's numbers (stats sections): big figures, and where they come from. */
function Numbers({ section, id }) {
    const stats = extractItems(section, 'stats', ['value', 'label']).filter((s) => stripHtml(s.value));
    const sources = stripHtml(getContent(section, 'sources', ''));
    if (!stats.length) return null;

    return (
        <section id={id} className={`${SECTION_OFFSET} py-24`}>
            <Container>
                <SectionHeading
                    eyebrow={stripHtml(getContent(section, 'badge', ''))}
                    title={stripHtml(getContent(section, 'title', ''))}
                    text={stripHtml(getContent(section, 'subtitle', ''))}
                />
                <dl className={`mx-auto mt-14 grid max-w-5xl gap-8 sm:grid-cols-2 ${stats.length > 3 ? 'lg:grid-cols-4' : 'lg:grid-cols-3'}`}>
                    {stats.map((s, i) => (
                        <div key={i} className="border-t border-line-strong pt-5">
                            <dd className="font-display text-5xl font-light tracking-tight text-ink">{stripHtml(s.value)}</dd>
                            <dt className="mt-2 text-sm text-soft">{stripHtml(s.label)}</dt>
                        </div>
                    ))}
                </dl>
                {stripHtml(getContent(section, 'note', '')) && <p className="mx-auto mt-8 max-w-5xl text-sm text-soft">{stripHtml(getContent(section, 'note', ''))}</p>}
                {sources && <p className="mx-auto mt-6 max-w-5xl text-xs text-faint">{sources}</p>}
            </Container>
        </section>
    );
}

/** The other two audience pages, as photo cards. */
function OtherPlaces({ others }) {
    return (
        <section id="other-places" className={`${SECTION_OFFSET} py-24`}>
            <Container>
                <SectionHeading eyebrow="Other places" title="TenaFi works there too" />
                <div className="mt-14 grid gap-5 md:grid-cols-2">
                    {others.map((o, i) => (
                        <ImageCard
                            key={o.to}
                            to={o.to}
                            image={o.image || PHOTOS[(i + 1) % PHOTOS.length]}
                            tags={[o.badge].filter(Boolean)}
                            title={o.title}
                            text={o.text}
                            className="min-h-[22rem]"
                        />
                    ))}
                </div>
            </Container>
        </section>
    );
}
