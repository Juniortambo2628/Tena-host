import React from 'react';
import { usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { getContent, getMedia, extractItems, sanitizeHtml, stripHtml, getText, isTruthy, textLines, getUpload } from '@/lib/cms';
import { usePublic } from '@/Components/Public/PublicContext';
import TrialSlots from '@/Components/Public/TrialSlots';
import Mockup from '@/Components/Public/Mockup';
import EditablePicture from '@/Components/Public/EditablePicture';
import CallbackForm from './CallbackForm';
import { SkeletonHero } from './Skeleton';
import { fillFree } from '@/lib/pricingMath';
import CtaLink from '@/Components/Public/CtaLink';
import FitText from '@/Components/Public/FitText';
import './Hero.css';

/** "From KES 3,000": the cheapest plan on the live /hosts pricing section. */
/**
 * "Plus a TenaFi device from {device_price}, ..." with the cheapest device on
 * /pricing; '' when the line is empty or there are no devices yet.
 */
export function useDeviceLine(section) {
    const { devices = [] } = usePage().props;
    const line = stripHtml(getContent(section, 'device_line', ''));
    const cheapest = devices.filter((d) => d.price_kes > 0).sort((a, b) => a.price_kes - b.price_kes)[0];
    if (!line || (line.includes('{device_price}') && !cheapest)) return '';
    return line.replace('{device_price}', cheapest ? `KES ${cheapest.price_kes.toLocaleString('en-KE')}` : '');
}

/**
 * "3 months free with your TenaFi device. Then from KES 3,000/month.": the
 * free months and cheapest paid plan (Free doesn't count) on the live /hosts
 * pricing; '' when /hosts has no free months or the line is empty, so the
 * usual price line shows.
 */
export function useFreePriceLine(section) {
    const { offer = { freeMonths: 0 } } = usePage().props;
    const cheapest = useCheapestPrice();
    const line = stripHtml(getContent(section, 'free_price_line', ''));
    if (!offer.freeMonths || !line || (line.includes('{price}') && !cheapest)) return '';
    return fillFree(line, offer.freeMonths).replace('{price}', cheapest);
}

/** "From KES 3,000": the cheapest paid plan on the live /hosts pricing (Free, at KES 0, doesn't count). */
export function useCheapestPrice() {
    const { plans = [] } = usePage().props;
    const priced = plans.filter(p => p.price_kes > 0).sort((a, b) => a.price_kes - b.price_kes);
    return priced[0]?.price || '';
}

/**
 * The drawn hero picture: a WiFi page on a phone, with up to three
 * "what happens next" cards stacked beside it (under it on narrower
 * screens). The cards never cover the phone.
 */
function HeroVisual({ section }) {
    const cards = extractItems(section, 'visual_cards', ['icon', 'title', 'text']).filter(c => stripHtml(c.title));
    return (
        <div className={`hero-visual ${cards.length ? 'hero-visual--cards' : ''}`} aria-hidden="true">
            <div className="hero-visual__phone-wrap">
                <Mockup type="splash" screen={getContent(section, 'visual_screen', '')} shot={getUpload(section, 'visual_shot')} alt="A TenaFi WiFi page" className="hero-visual__phone" />
            </div>
            {cards.length > 0 && (
                <ul className="hero-visual__cards">
                    {cards.slice(0, 3).map((card, i) => (
                        <li key={i} className={`hero-float hero-float--${i}`}>
                            <span className="hero-float__ic">{stripHtml(card.icon)}</span>
                            <span className="hero-float__text"><b>{stripHtml(card.title)}</b><small>{stripHtml(card.text)}</small></span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

export default function Hero({ section }) {
    const { site } = usePublic();
    const cheapest = useCheapestPrice();
    // While /hosts has free months, "3 months free with your TenaFi device. Then from ..." replaces the price line.
    const freeLine = useFreePriceLine(section);
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
    const priceLine = freeLine || stripHtml(getContent(section, 'price_line', ''));
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
                            <EditablePicture section={section} slot={drawn ? 'visual_shot' : 'main_image'}>
                                {drawn ? <HeroVisual section={section} /> : (
                                    <div className="hero-image-wrap">
                                        <img className="hero-image" src={mainImage} alt="TenaFi platform preview" />
                                    </div>
                                )}
                            </EditablePicture>
                        </div>
                    </div>
                    {audiences.length > 0 && (
                        <nav className="hero-audiences" aria-label="Who it's for">
                            <span className="hero-audiences__label">{getText(section, 'audiences_label', 'Made for')}</span>
                            {audiences.map((a, i) => <a key={i} href={stripHtml(a.href)}>{stripHtml(a.label)}</a>)}
                        </nav>
                    )}
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
