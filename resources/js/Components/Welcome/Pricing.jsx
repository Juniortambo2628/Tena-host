import React from 'react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, extractItems } from '@/lib/cms';
import { SkeletonSectionHeader, SkeletonPricingGrid } from './Skeleton';
import './Pricing.css';

const defaultPlans = [
    { label: 'Monthly Subscription', price: '$19', unit: '/ listing / month', description: 'Includes guest data collection, analytics dashboard, and marketing tools (SMS & Email).', cta: 'Join Waitlist', variant: 'dark' },
    { label: 'Device Cost', price: '$150', unit: 'one-time', description: 'One-time WiFi hardware cost to run the splash pages and capture guests on-site.', cta: 'Get Early Access', variant: 'outline' },
    { label: 'Founding Host Bundle', price: '$45', unit: '/ month', description: 'Pay monthly ($79/month for first 6 months) — drops to $49/month after the device is paid off. Founding hosts get 1 month free.', cta: 'Claim Founding Offer', variant: 'dark' },
];

export default function Pricing({ onOpenWaitlist, section }) {
    if (!section) {
        return (
            <SectionWrapper id="pricing" bg="gray">
                <SkeletonSectionHeader />
                <SkeletonPricingGrid />
            </SectionWrapper>
        );
    }

    const title = getContent(section, 'title', 'Transparent Pricing');
    const subtitle = getContent(section, 'subtitle', 'Simple, predictable pricing so you can scale direct bookings without surprises.');

    const cmsPlans = extractItems(section, 'plans', ['label', 'price', 'unit', 'description', 'cta', 'variant']);
    const plans = cmsPlans.length > 0 ? cmsPlans : defaultPlans;

    const ctaLabel = getContent(section, 'cta_label', 'Founding Host Offer');
    const ctaText = getContent(section, 'cta_text', 'Join our waitlist and be among the first <strong>100 hosts</strong> to sign up.');
    const ctaButton = getContent(section, 'cta_button', 'Join the Waitlist Now');
    const footerText = getContent(section, 'footer_text', 'Questions? Email');
    const footerEmail = getContent(section, 'footer_email', 'info@tena.host');

    return (
        <SectionWrapper id="pricing" bg={section.bg || 'gray'}>
            <SectionHeader title={title} subtitle={subtitle} />

            <div className="pricing-cards-grid">
                {plans.map((plan, index) => (
                    <div key={index} className="pricing-card">
                        <div className="pricing-card-inner">
                            <span className="pricing-card-label">{plan.label}</span>
                            <div className="pricing-card-price">
                                {plan.price} <span className="pricing-card-price-unit">{plan.unit}</span>
                            </div>
                            <p className="pricing-card-desc">{plan.description}</p>
                            <button
                                onClick={onOpenWaitlist}
                                className={plan.variant === 'dark' ? 'btn-primary pricing-card-cta-dark' : 'pricing-card-cta-outline'}
                            >
                                {plan.cta}
                            </button>
                        </div>
                    </div>
                ))}
            </div>

            <div className="pricing-cta-section">
                <div className="pricing-cta-card">
                    <div className="pricing-cta-decoration"></div>
                    <div className="pricing-cta-content">
                        <span className="pricing-cta-label">{ctaLabel}</span>
                        <p className="pricing-cta-text" dangerouslySetInnerHTML={{ __html: ctaText }} />
                        <button onClick={onOpenWaitlist} className="pricing-cta-button">
                            {ctaButton} <i className="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div className="pricing-footer">
                <p className="pricing-footer-text">
                    {footerText} <a href={`mailto:${footerEmail}`} className="pricing-footer-link">{footerEmail}</a>
                </p>
            </div>
        </SectionWrapper>
    );
}
