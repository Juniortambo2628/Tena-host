import React, { useState } from 'react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, extractItems, sanitizeHtml } from '@/lib/cms';
import { SkeletonSectionHeader, SkeletonPricingGrid } from './Skeleton';
import { ArrowRight, ChevronDown, Sparkles, Zap, Award, Clock } from 'lucide-react';
import ContactForm from './ContactForm';
import './Pricing.css';

const defaultPlans = [
    { label: 'Monthly Subscription', price: '$10', unit: '/ listing / month', description: 'Includes guest data collection, analytics dashboard, and marketing tools (SMS & Email).', cta: 'Join Waitlist', variant: 'dark' },
    { label: 'Device Cost', price: '$150', unit: 'one-time', description: 'One-time WiFi hardware cost to run the splash pages and capture guests on-site.', cta: 'Get Early Access', variant: 'outline' },
    { label: 'Founding Host Bundle', price: '$45', unit: '/ month', description: 'Pay monthly ($79/month for first 6 months) — drops to $49/month after the device is paid off. Founding hosts get 1 month free.', cta: 'Claim Founding Offer', variant: 'dark' },
];

function PricingPlanCard({ plan, onOpenWaitlist }) {
    const [expanded, setExpanded] = useState(false);
    return (
        <div className="pricing-card">
            <div className="pricing-card-inner">
                <span className="pricing-card-label">{plan.label}</span>
                <div className="pricing-card-price">
                    {plan.price} <span className="pricing-card-price-unit">{plan.unit}</span>
                </div>
                <div className={`pricing-card-desc-wrap ${expanded ? 'is-expanded' : ''}`}>
                    <p
                        className="pricing-card-desc"
                        dangerouslySetInnerHTML={{ __html: sanitizeHtml(plan.description) }}
                    />
                </div>
                <button
                    type="button"
                    className="pricing-card-toggle"
                    onClick={() => setExpanded((v) => !v)}
                    aria-expanded={expanded}
                >
                    {expanded ? 'See less' : 'See more'}
                    <ChevronDown size={14} className={`pricing-card-toggle-icon ${expanded ? 'is-open' : ''}`} />
                </button>
                <button
                    onClick={onOpenWaitlist}
                    className={plan.variant === 'dark' ? 'btn-primary pricing-card-cta-dark' : 'pricing-card-cta-outline'}
                >
                    {plan.cta}
                </button>
            </div>
        </div>
    );
}

const FOUNDING_PERKS = [
    { icon: Sparkles, label: '3 months free', text: 'on the Tena platform' },
    { icon: Zap, label: 'Priority onboarding', text: 'and dedicated support' },
    { icon: Award, label: 'Early access', text: 'to new features as they ship' },
    { icon: Clock, label: 'Complimentary device', text: 'chance to receive a Tena router' },
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
    const ctaHeadline = getContent(section, 'cta_headline', 'Join the first 100 hosts shaping Tena.');
    const ctaIntro = getContent(section, 'cta_intro', "We're inviting our first 100 Superhosts into the Founding Host Program — priority access before public launch and a direct line to the team building Tena.");
    const ctaClosing = getContent(section, 'cta_closing', 'Applications are open now. Once all 100 spots are filled, the program closes.');
    const ctaButton = getContent(section, 'cta_button', 'Join the Waitlist Now');

    return (
        <SectionWrapper id="pricing" bg={section.bg || 'gray'}>
            <SectionHeader title={title} subtitle={subtitle} />

            <div className="pricing-cards-grid">
                {plans.map((plan, index) => (
                    <PricingPlanCard key={index} plan={plan} onOpenWaitlist={onOpenWaitlist} />
                ))}
            </div>

            <div className="pricing-cta-section">
                <div className="pricing-cta-card">
                    <div className="pricing-cta-decoration"></div>
                    <div className="pricing-cta-decoration-bl"></div>
                    <div className="pricing-cta-content">
                        <span className="pricing-cta-label">{ctaLabel}</span>
                        <h3 className="pricing-cta-headline">{ctaHeadline}</h3>
                        <p className="pricing-cta-intro">{ctaIntro}</p>

                        <ul className="pricing-cta-perks">
                            {FOUNDING_PERKS.map(({ icon: Icon, label, text }, i) => (
                                <li key={i} className="pricing-cta-perk">
                                    <span className="pricing-cta-perk-icon"><Icon size={18} /></span>
                                    <div className="pricing-cta-perk-body">
                                        <strong className="pricing-cta-perk-label">{label}</strong>
                                        <span className="pricing-cta-perk-text">{text}</span>
                                    </div>
                                </li>
                            ))}
                        </ul>

                        <p className="pricing-cta-closing">{ctaClosing}</p>

                        <button onClick={onOpenWaitlist} className="pricing-cta-button">
                            {ctaButton} <ArrowRight size={14} className="ml-2 inline" />
                        </button>
                    </div>
                </div>
            </div>

            <ContactForm />
        </SectionWrapper>
    );
}
