import React from 'react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, extractItems, sanitizeHtml } from '@/lib/cms';
import { SkeletonSectionHeader, SkeletonPricingGrid } from './Skeleton';
import { ArrowRight } from 'lucide-react';
import './Pricing.css';

const defaultPlans = [
    { label: 'Monthly Subscription', price: '$10', unit: '/ listing / month', description: 'Includes guest data collection, analytics dashboard, and marketing tools (SMS & Email).', cta: 'Join Waitlist', variant: 'dark' },
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

    const ctaLabel = getContent(section, 'cta_label', 'Become a Founding Host');
    const ctaText = getContent(section, 'cta_text', 'Join the Tena waitlist for priority access before public launch.<br/><br/>We\'re inviting only our first 100 hosts to join the Founding Host Program.<br/><br/>As a Founding Host, you\'ll receive:<br/><br/><strong>*</strong> 3 months free on the Tena platform<br/><strong>*</strong> Priority onboarding and dedicated support<br/><strong>*</strong> Early access to new features<br/><strong>*</strong> The opportunity to receive a complimentary Tena device<br/><br/>Built by Superhosts, for Superhosts, Tena helps you capture every guest—not just the booker—build lasting guest relationships, and drive more direct bookings beyond the OTAs.<br/><br/>Applications are now open. Once all 100 Founding Host spots are filled, the program will close.');
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
                            <p className="pricing-card-desc" dangerouslySetInnerHTML={{ __html: sanitizeHtml(plan.description) }} />
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
                        <p className="pricing-cta-text" dangerouslySetInnerHTML={{ __html: sanitizeHtml(ctaText) }} />
                        <button onClick={onOpenWaitlist} className="pricing-cta-button">
                            {ctaButton} <ArrowRight size={14} className="ml-2 inline"></ArrowRight>
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
