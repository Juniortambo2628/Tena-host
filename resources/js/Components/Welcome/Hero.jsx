import React from 'react';
import { getContent, getMedia, extractItems, sanitizeHtml, stripHtml, getText } from '@/lib/cms';
import { SkeletonHero } from './Skeleton';
import CtaLink from '@/Components/Public/CtaLink';
import FitText from '@/Components/Public/FitText';
import './Hero.css';

export default function Hero({ section }) {
    if (!section) return <SkeletonHero />;

    const badge = getText(section, 'badge', "Africa's guest relationship platform");
    const title = getContent(section, 'title', 'Turn your existing WiFi into <span class="text-[#FFD300]">growth</span>.');
    const subtitle = getContent(section, 'subtitle', '');
    const ctaPrimary = getText(section, 'cta_primary', 'Join');
    // Empty URL = this page's "Join" target (its sign-up form).
    const ctaPrimaryUrl = getContent(section, 'cta_primary_url', '');
    const ctaSecondary = getText(section, 'cta_secondary', 'How it works');
    const body = getContent(section, 'body', '');
    const note = stripHtml(getContent(section, 'note', ''));
    const ctaSecondaryUrl = getContent(section, 'cta_secondary_url', '#how-it-works');
    const mainImage = getMedia(section, 'main_image', '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg');

    // Optional feature cards under the hero (none on the relaunch pages).
    const features = extractItems(section, 'features', ['icon', 'title', 'description']).map((f, i) => ({
        ...f,
        image: getMedia(section, `feature_${i}_image`, ''),
    }));

    return (
        <section className="hero-wrapper">
            <div className="hero-container">
                <div className="hero-main-card">
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
                        </div>
                        <div className="hero-image-col">
                            <div className="hero-image-wrap">
                                <img className="hero-image" src={mainImage} alt="TenaFi platform preview" />
                            </div>
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
                                        <i className={`${feature.icon} text-[#FFD300] text-2xl`}></i>
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
