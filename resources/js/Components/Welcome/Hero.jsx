import React from 'react';
import { usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { getContent, getMedia, extractItems, sanitizeHtml, stripHtml, getText, isTruthy, textLines } from '@/lib/cms';
import { usePublic } from '@/Components/Public/PublicContext';
import TrialSlots from '@/Components/Public/TrialSlots';
import Mockup from '@/Components/Public/Mockup';
import CallbackForm from './CallbackForm';
import { SkeletonHero } from './Skeleton';
import CtaLink from '@/Components/Public/CtaLink';
import FitText from '@/Components/Public/FitText';
import './Hero.css';

/** "From KES 3,000": the cheapest plan on the live /hosts pricing section. */
export function useCheapestPrice() {
    const { plans = [] } = usePage().props;
    const priced = plans.filter(p => p.price_kes > 0).sort((a, b) => a.price_kes - b.price_kes);
    return priced[0]?.price || '';
}

/** The drawn hero picture: a WiFi page on a phone with floating cards around it. */
function HeroVisual({ section }) {
    const cards = extractItems(section, 'visual_cards', ['icon', 'title', 'text']).filter(c => stripHtml(c.title));
    return (
        <div className="hero-visual" aria-hidden="true">
            <Mockup type="splash" screen={getContent(section, 'visual_screen', '')} className="hero-visual__phone" />
            {cards.slice(0, 3).map((card, i) => (
                <div key={i} className={`hero-float hero-float--${i}`}>
                    <span className="hero-float__ic">{stripHtml(card.icon)}</span>
                    <span><b>{stripHtml(card.title)}</b><small>{stripHtml(card.text)}</small></span>
                </div>
            ))}
        </div>
    );
}

export default function Hero({ section }) {
    const { site } = usePublic();
    const cheapest = useCheapestPrice();
    if (!section) return <SkeletonHero />;

    const badge = getText(section, 'badge', "Africa's guest relationship platform");
    const title = getContent(section, 'title', 'Turn your existing WiFi into <span class="text-primary">growth</span>.');
    const subtitle = getContent(section, 'subtitle', '');
    const ctaPrimary = getText(section, 'cta_primary', 'Join');
    // Empty URL = this page's "Join" target (its sign-up form).
    const ctaPrimaryUrl = getContent(section, 'cta_primary_url', '');
    const ctaSecondary = getText(section, 'cta_secondary', 'How it works');
    const body = getContent(section, 'body', '');
    const note = stripHtml(getContent(section, 'note', ''));
    const ctaSecondaryUrl = getContent(section, 'cta_secondary_url', '#how-it-works');
    const mainImage = getMedia(section, 'main_image', '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg');

    // Optional extras (the homepage uses them; other pages leave them empty).
    const audiences = extractItems(section, 'audiences', ['label', 'href']).filter(a => stripHtml(a.label));
    const priceLine = stripHtml(getContent(section, 'price_line', ''));
    const priceLink = stripHtml(getContent(section, 'price_link', ''));
    const ticks = textLines(getContent(section, 'ticks', ''), site);
    const showSlots = isTruthy(getContent(section, 'show_slots', '0'));
    const drawn = getText(section, 'visual', 'image') === 'splash';
    // "dark": the dark hero card (events page).
    const dark = getText(section, 'style', '') === 'dark';

    // Optional feature cards under the hero (none on the relaunch pages).
    const features = extractItems(section, 'features', ['icon', 'title', 'description']).map((f, i) => ({
        ...f,
        image: getMedia(section, `feature_${i}_image`, ''),
    }));

    return (
        <section className="hero-wrapper">
            <div className="hero-container">
                <div className={`hero-main-card ${dark ? 'hero-main-card--dark' : ''}`}>
                    <div className="hero-content">
                        <div className="hero-text">
                            <div className="hero-badge-wrap">
                                <FitText className="hero-badge" max={14} min={10}>{badge}</FitText>
                            </div>
                            <h1 className="hero-title" dangerouslySetInnerHTML={{ __html: sanitizeHtml(title) }} />
                            <p className="hero-subtitle" dangerouslySetInnerHTML={{ __html: sanitizeHtml(subtitle) }} />
                            {stripHtml(body) && <p className="hero-body" dangerouslySetInnerHTML={{ __html: sanitizeHtml(body) }} />}
                            {audiences.length > 0 && (
                                <nav className="hero-audiences" aria-label="Who it's for">
                                    {audiences.map((a, i) => <a key={i} href={stripHtml(a.href)}>{stripHtml(a.label)}</a>)}
                                </nav>
                            )}
                            <div className="hero-cta-group">
                                <CtaLink href={ctaPrimaryUrl} className="hero-btn-primary">
                                    {ctaPrimary}
                                </CtaLink>
                                {ctaSecondary && (
                                    <CtaLink href={ctaSecondaryUrl} className="hero-btn-secondary">
                                        {ctaSecondary}
                                    </CtaLink>
                                )}
                            </div>
                            {note && <p className="hero-note">{note}</p>}
                            <CallbackForm section={section} />
                            {priceLine && (!priceLine.includes('{price}') || cheapest) && (
                                <p className="hero-price-line">
                                    {priceLine.replace('{price}', cheapest)}{' '}
                                    {priceLink && <a href={priceLink}>{stripHtml(getContent(section, 'price_link_label', 'See pricing'))}</a>}
                                </p>
                            )}
                            {ticks.length > 0 && (
                                <ul className="hero-ticks">
                                    {ticks.map((t, i) => <li key={i}><Check size={16} aria-hidden="true" />{t.text}</li>)}
                                </ul>
                            )}
                            {showSlots && <TrialSlots className="hero-slots" />}
                        </div>
                        <div className="hero-image-col">
                            {drawn ? <HeroVisual section={section} /> : (
                                <div className="hero-image-wrap">
                                    <img className="hero-image" src={mainImage} alt="TenaFi platform preview" />
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {features.length > 0 && <div className="hero-features-row">
                    {features.map((feature, index) => (
                        <div key={index} className="hero-feature-item">
                            <div className="hero-feature-card">
                                <div className="hero-feature-image-wrap">
                                    <img src={feature.image} alt={feature.title} className="hero-feature-image" />
                                    <div className="hero-feature-image-overlay">
                                        <i className={`${feature.icon} text-primary text-2xl`}></i>
                                    </div>
                                </div>
                                <div className="hero-feature-content">
                                    <h5 className="hero-feature-title">{feature.title}</h5>
                                    <p className="hero-feature-desc" dangerouslySetInnerHTML={{ __html: sanitizeHtml(feature.description) }} />
                                </div>
                            </div>
                        </div>
                    ))}
                </div>}
            </div>
        </section>
    );
}
