import React from 'react';
import { getContent, getMedia, extractItems, sanitizeHtml } from '@/lib/cms';
import { SkeletonHero } from './Skeleton';
import './Hero.css';

const defaultFeatures = [
    { icon: 'fa fa-list-alt', title: 'Build Your Guest List', description: 'Capture guest contact details automatically through your WiFi network.', image: '/legacy/assets/Tena-Landing/Step-1-Connect.jpg' },
    { icon: 'fa fa-percent', title: 'Reduce OTA Commissions', description: 'Save up to 20% by converting OTA guests to direct bookers.', image: '/legacy/assets/Tena-Landing/Step-2-Data-Collection.jpg' },
    { icon: 'fa fa-sync', title: 'Increase Repeat Bookings', description: 'Build lasting relationships with guests for future stays.', image: '/legacy/assets/Tena-Landing/Step-3-Remarket.jpg' },
    { icon: 'fa fa-rocket', title: 'Easy Setup & Deployment', description: 'Get up and running in minutes with our simple installation.', image: '/legacy/assets/Tena-Landing/Branded-Splash-Page.jpg' },
];

export default function Hero({ onOpenWaitlist, section }) {
    if (!section) return <SkeletonHero />;

    const badge = getContent(section, 'badge', 'Built by Superhosts — For Superhosts');
    const title = getContent(section, 'title', 'Stop Losing <span class="text-[#FFD300]">20% to OTAs</span>. Take Control of Your Bookings.');
    const subtitle = getContent(section, 'subtitle', '<strong>Tena</strong> — built by Superhosts for Superhosts. Grow your guest list, boost repeat bookings, and save on commission — all from your WiFi.');
    const ctaPrimary = getContent(section, 'cta_primary', 'Join the Waitlist Today');
    const ctaSecondary = getContent(section, 'cta_secondary', 'Learn How It Works');
    const ctaSecondaryUrl = getContent(section, 'cta_secondary_url', '#how-it-works');
    const mainImage = getMedia(section, 'main_image', '/legacy/img/hero-slider-1.jpg');

    const cmsFeatures = extractItems(section, 'features', ['icon', 'title', 'description']);
    const features = cmsFeatures.length > 0
        ? cmsFeatures.map((f, i) => ({
              ...f,
              image: getMedia(section, `feature_${i}_image`, defaultFeatures[i]?.image || ''),
          }))
        : defaultFeatures;

    return (
        <section className="hero-wrapper">
            <div className="hero-container">
                <div className="hero-main-card">
                    <div className="hero-content">
                        <div className="hero-text">
                            <div className="hero-badge-wrap">
                                <span className="hero-badge">{badge}</span>
                            </div>
                            <h1 className="hero-title" dangerouslySetInnerHTML={{ __html: sanitizeHtml(title) }} />
                            <p className="hero-subtitle" dangerouslySetInnerHTML={{ __html: sanitizeHtml(subtitle) }} />
                            <div className="hero-cta-group">
                                <button onClick={onOpenWaitlist} className="hero-btn-primary">
                                    {ctaPrimary}
                                </button>
                                <a href={ctaSecondaryUrl} className="hero-btn-secondary">
                                    {ctaSecondary}
                                </a>
                            </div>
                        </div>
                        <div className="hero-image-col">
                            <div className="hero-image-wrap">
                                <img className="hero-image" src={mainImage} alt="Tena WiFi platform preview" />
                            </div>
                        </div>
                    </div>
                </div>

                <div className="hero-features-row">
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
                                    <p className="hero-feature-desc">{feature.description}</p>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
