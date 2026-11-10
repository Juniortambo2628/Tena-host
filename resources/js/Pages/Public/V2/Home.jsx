import React, { useMemo } from 'react';
import { Head } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { SiteLayout, PhotoHero, Eyebrow, PillLink, SectionNav } from '@okjt/site';
import V2Site from '@/Components/V2/V2Site';
import {
    Html, PHOTOS, SCREENS, screen, featureRows, sectionId, navLabel,
    Partners, Audiences, Gap, Features, Showcase, HowItWorks, CompareTable, Credibility, Pricing, Faq, Trial,
} from '@/Components/V2/Sections';
import { getContent, getMedia, getUpload, extractItems, stripHtml, textLines, baseSectionKey } from '@/lib/cms';
import { useCheapestPrice, useDeviceLine } from '@/Components/Welcome/Hero';

/**
 * The v2 landing page (/v2), in the OKJT kit's design. Its sections are the
 * CMS page "Home (v2 design)" (Admin -> Public Pages), copied from the live
 * homepage and edited separately; they show in the order set there. Each
 * section type has a kit-styled version below; types without one are skipped.
 */
export default function V2Home({ sections = [], site = {}, seo = {} }) {
    const find = (type) => sections.find((s) => baseSectionKey(s.section_key) === type);
    const trial = find('cta_banner');
    const places = usePlaces(find('detailed_features'));
    const media = useScreens(find('detailed_features'), find('showcase'));
    const shown = sections.filter((s) => RENDERERS[baseSectionKey(s.section_key)]);
    const chips = shown
        .filter((s) => !['hero', 'partners', 'path_cards'].includes(baseSectionKey(s.section_key)))
        .map((s) => ({ id: sectionId(s), label: navLabel(s) }));
    const navAfter = shown.find((s) => baseSectionKey(s.section_key) === 'path_cards') ? 'path_cards' : 'hero';
    const paths = find('path_cards');
    const pathsHref = paths ? `#${sectionId(paths)}` : '/#choose';
    const joinHref = trial ? `#${sectionId(trial)}` : pathsHref;

    return (
        <V2Site places={places} media={media} joinHref={joinHref}>
            <Head title={seo.title || 'TenaFi'} />
            <SiteLayout overlay cta={false}>
                {shown.map((section) => {
                    const type = baseSectionKey(section.section_key);
                    const Section = RENDERERS[type];
                    return (
                        <React.Fragment key={section.id ?? section.section_key}>
                            <Section section={section} site={site} id={sectionId(section)} joinHref={joinHref} pathsHref={pathsHref} />
                            {type === navAfter && chips.length > 1 && <SectionNav sections={chips} label="On this page" />}
                        </React.Fragment>
                    );
                })}
            </SiteLayout>
        </V2Site>
    );
}

function usePlaces(features) {
    return useMemo(() => Object.fromEntries(featureRows(features).map(({ i, id }) => [
        `group-${id}`, getUpload(features, `section_${i}_image`) || PHOTOS[i % PHOTOS.length],
    ])), [features]);
}

function useScreens(features, showcase) {
    return useMemo(() => {
        const media = {};
        featureRows(features).forEach(({ i, id }) => {
            const shot = getUpload(features, `section_${i}_shot`) || (SCREENS[id] ? `/legacy/assets/v2/${SCREENS[id]}` : '');
            if (shot) media[`feature-${i}`] = screen(shot, stripHtml(getContent(features, `sections.${i}.label`, '')));
        });
        extractItems(showcase, 'items', ['title']).forEach((item, i) => {
            const shot = getUpload(showcase, `item_${i}_shot`);
            if (shot) media[`showcase-${i}`] = screen(shot, stripHtml(item.title));
        });
        return media;
    }, [features, showcase]);
}


/** The homepage hero's words over its picture, with an "about" panel overlapping it. */
function Hero({ section: hero, site, joinHref }) {
    const title = getContent(hero, 'title', 'Turn your existing WiFi into <span>growth.</span>');
    const ticks = textLines(getContent(hero, 'ticks', ''), site);
    // "From {price} a month": the cheapest live plan, as on the live hero.
    const cheapest = useCheapestPrice();
    const deviceLine = useDeviceLine(hero);
    const priceLine = stripHtml(getContent(hero, 'price_line', 'Book a demo. We install it for you.'));
    const primary = stripHtml(getContent(hero, 'cta_primary', 'Book a demo'));
    const primaryHref = stripHtml(getContent(hero, 'cta_primary_url', '')) || joinHref;

    return (
        // The wrapper darkens the photo top to bottom (V2Site.css .v2-hero), so the words read clearly.
        <div className="v2-hero">
            <PhotoHero
                tall
                image={getMedia(hero, 'main_image', '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg')}
                phoneImage={getUpload(hero, 'phone_image') || undefined}
                eyebrow={stripHtml(getContent(hero, 'badge', ''))}
                title={<Html html={title} className="v2-hero-title" />}
                text={<Html html={getContent(hero, 'subtitle', '')} />}
                after={(
                    <div className="grid gap-4 rounded-[2rem] bg-white/85 p-4 shadow-[0_40px_90px_-40px_color-mix(in_srgb,var(--color-ink)_60%,transparent)] backdrop-blur-xl sm:p-6 lg:grid-cols-5">
                        <div className="flex flex-col justify-between rounded-3xl bg-gradient-to-br from-white to-tint p-6 sm:p-8 lg:col-span-3">
                            <Eyebrow>About TenaFi</Eyebrow>
                            <p className="mt-5 !font-display text-3xl !font-light leading-tight tracking-tight !text-ink sm:text-4xl">
                                {stripHtml(getContent(hero, 'body', 'Guests log in, join your list, review you and come back. We install it and run it for you.'))}
                            </p>
                            {ticks.length > 0 && (
                                <ul className="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-sm text-body">
                                    {ticks.map((t, i) => <li key={i} className="inline-flex items-center gap-1.5"><Check className="h-4 w-4 text-positive" aria-hidden="true" />{t.text}</li>)}
                                </ul>
                            )}
                        </div>
                        <div className="flex flex-col justify-center gap-3 rounded-3xl bg-ink p-6 text-white sm:p-8 lg:col-span-2">
                            <Eyebrow light>Book a demo</Eyebrow>
                            <p className="!font-display text-2xl !font-light leading-snug !text-white">
                                {priceLine.includes('{price}') && !cheapest ? 'Book a demo. We install it for you.' : priceLine.replace('{price}', cheapest)}
                            </p>
                            {deviceLine && <p className="text-sm text-white/75">{deviceLine}</p>}
                            <div className="mt-2"><PillLink to={primaryHref} variant="light">{primary}</PillLink></div>
                        </div>
                    </div>
                )}
            >
                <PillLink to={primaryHref} variant="light" size="lg">{primary}</PillLink>
                <PillLink to={stripHtml(getContent(hero, 'cta_secondary_url', '')) || '#how'} variant="glass" size="lg" arrow>
                    {stripHtml(getContent(hero, 'cta_secondary', 'See how it works'))}
                </PillLink>
            </PhotoHero>
        </div>
    );
}

/** Section type (the part of its key before "__") => its v2 version. */
const RENDERERS = {
    hero: Hero,
    partners: Partners,
    path_cards: Audiences,
    comparison: Gap,
    detailed_features: Features,
    showcase: Showcase,
    how_it_works: HowItWorks,
    comparison_table: CompareTable,
    credibility: Credibility,
    pricing: Pricing,
    faq: Faq,
    cta_banner: Trial,
};

