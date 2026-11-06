import React from 'react';
import { Head } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { SiteLayout, PhotoHero, ImageCard, Container, Eyebrow, PillLink, SectionHeading } from '@okjt/site';
import V2Site from '@/Components/V2/V2Site';
import { getContent, getMedia, extractItems, sanitizeHtml, stripHtml, textLines } from '@/lib/cms';
import { useCheapestPrice } from '@/Components/Welcome/Hero';

/**
 * The v2 landing page (/v2), in the OKJT kit's design. This first version
 * shows the frame (nav, footer), the hero and the three audiences, using the
 * live homepage's words and pictures from the CMS; the rest of the page
 * follows as its own CMS page.
 */
export default function V2Home({ sections = [], site = {}, seo = {} }) {
    const section = (key) => sections.find((s) => s.section_key === key);
    const hero = section('hero');
    const paths = section('path_cards');

    return (
        <V2Site>
            <Head title={seo.title || 'TenaFi'} />
            <SiteLayout overlay>
                <Hero hero={hero} site={site} />
                {paths && <Audiences section={paths} />}
            </SiteLayout>
        </V2Site>
    );
}

/** The homepage hero's words over its picture, with an "about" panel overlapping it. */
function Hero({ hero, site }) {
    const title = getContent(hero, 'title', 'Turn your existing WiFi into <span>growth.</span>');
    const ticks = textLines(getContent(hero, 'ticks', ''), site);
    // "From {price} a month": the cheapest live plan, as on the live hero.
    const cheapest = useCheapestPrice();
    const priceLine = stripHtml(getContent(hero, 'price_line', 'Book a free trial. We install it for you.'));

    return (
        <PhotoHero
            tall
            image={getMedia(hero, 'main_image', '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg')}
            eyebrow={stripHtml(getContent(hero, 'badge', ''))}
            title={<span className="v2-hero-title" dangerouslySetInnerHTML={{ __html: sanitizeHtml(title) }} />}
            text={<span dangerouslySetInnerHTML={{ __html: sanitizeHtml(getContent(hero, 'subtitle', '')) }} />}
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
                        <Eyebrow light>Free trial</Eyebrow>
                        <p className="!font-display text-2xl !font-light leading-snug !text-white">
                            {priceLine.includes('{price}') && !cheapest ? 'Book a free trial. We install it for you.' : priceLine.replace('{price}', cheapest)}
                        </p>
                        <div className="mt-2"><PillLink to="#paths" variant="light">{stripHtml(getContent(hero, 'cta_primary', 'Book my free trial'))}</PillLink></div>
                    </div>
                </div>
            )}
        >
            <PillLink to="#paths" variant="light" size="lg">{stripHtml(getContent(hero, 'cta_primary', 'Book my free trial'))}</PillLink>
            <PillLink to="/#how-it-works" variant="glass" size="lg" arrow>{stripHtml(getContent(hero, 'cta_secondary', 'See how it works'))}</PillLink>
        </PhotoHero>
    );
}

/** "Which one are you?": one photo card per audience page. */
function Audiences({ section }) {
    const cards = extractItems(section, 'cards', ['label', 'title', 'description', 'href', 'tags']);

    return (
        <section id="paths" className="scroll-mt-24 py-24">
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
                            to={stripHtml(card.href) || '#paths'}
                            image={getMedia(section, `card_${i}_image`, '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg')}
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
