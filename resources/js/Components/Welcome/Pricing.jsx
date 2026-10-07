import React, { useState } from 'react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, extractItems, sanitizeHtml, stripHtml, isTruthy } from '@/lib/cms';
import { usePublic } from '@/Components/Public/PublicContext';
import CtaLink from '@/Components/Public/CtaLink';
import { SkeletonSectionHeader, SkeletonPricingGrid } from './Skeleton';
import { ArrowRight, Check, ChevronDown, Sparkles, Zap, Award, Clock } from 'lucide-react';
import LineList from '@/Components/Public/LineList';
import ContactForm from './ContactForm';
import './Pricing.css';

function PricingPlanCard({ plan }) {
    const { joinHref } = usePublic();
    const [expanded, setExpanded] = useState(false);
    const descId = `pricing-desc-${String(plan.label).replace(/\s+/g, '-').toLowerCase()}`;
    // "#join?plan=growth" pre-selects the plan in this page's sign-up form.
    const href = plan.id && joinHref.includes('#') ? `${joinHref}?plan=${plan.id}` : joinHref;

    return (
        <div className={`pricing-card ${plan.badge ? 'pricing-card--featured' : ''}`}>
            <div className="pricing-card-inner">
                {stripHtml(plan.badge) && <span className="pricing-card-badge">{stripHtml(plan.badge)}</span>}
                <span className="pricing-card-label">{plan.label}</span>
                {stripHtml(plan.tagline) && <p className="pricing-card-tagline">{stripHtml(plan.tagline)}</p>}
                <div className="pricing-card-price">
                    {plan.price} <span className="pricing-card-price-unit">{plan.unit}</span>
                </div>
                <LineList text={plan.features} className="pricing-card-features" icon={<Check size={16} className="pricing-card-feature-icon" />} />
                {stripHtml(plan.description) && (
                    <>
                        <button
                            type="button"
                            className="pricing-card-toggle"
                            onClick={() => setExpanded((v) => !v)}
                            aria-expanded={expanded}
                            aria-controls={descId}
                        >
                            {expanded ? 'See less' : 'See more'}
                            <ChevronDown size={14} className={`pricing-card-toggle-icon ${expanded ? 'is-open' : ''}`} />
                        </button>
                        <div id={descId} className={`pricing-card-desc-wrap ${expanded ? 'is-expanded' : ''}`} hidden={!expanded}>
                            <p className="pricing-card-desc" dangerouslySetInnerHTML={{ __html: sanitizeHtml(plan.description) }} />
                        </div>
                    </>
                )}
                <CtaLink href={href} event="join_click" className={plan.variant === 'dark' ? 'btn-primary pricing-card-cta-dark' : 'pricing-card-cta-outline'}>
                    {plan.cta}
                </CtaLink>
            </div>
        </div>
    );
}

const PERK_ICONS = { sparkles: Sparkles, zap: Zap, award: Award, clock: Clock };

export default function Pricing({ section }) {
    const { site } = usePublic();

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

    // Plans are shared site-wide (Site-wide -> Plans) so every page quotes
    // the same prices; a page may still override them with its own rows.
    const planFields = ['id', 'label', 'tagline', 'badge', 'price', 'price_kes', 'unit', 'features', 'description', 'cta', 'variant'];
    const ownPlans = extractItems(section, 'plans', planFields);
    const plans = ownPlans.length > 0 ? ownPlans : extractItems(site?.plans, 'plans', planFields);
    const currencyNote = stripHtml(getContent(site?.plans, 'currency_note', ''));
    const perks = extractItems(section, 'perks', ['symbol', 'label', 'text']);
    const footnote = getContent(section, 'footnote', '');
    const showContactForm = isTruthy(getContent(section, 'show_contact_form', '0'));

    const ctaLabel = getContent(section, 'cta_label', '');
    const ctaHeadline = getContent(section, 'cta_headline', '');
    const ctaIntro = getContent(section, 'cta_intro', '');
    const ctaClosing = getContent(section, 'cta_closing', '');
    const ctaButton = getContent(section, 'cta_button', 'Join');

    return (
        <SectionWrapper bg={section.bg || 'gray'}>
            <SectionHeader title={title} subtitle={subtitle} />

            {currencyNote && <p className="pricing-currency-note">{currencyNote}</p>}

            <div className="pricing-cards-grid">
                {plans.map((plan, index) => (
                    <PricingPlanCard key={index} plan={plan} />
                ))}
            </div>

            {ctaHeadline && <div className="pricing-cta-section">
                <div className="pricing-cta-card">
                    <div className="pricing-cta-decoration"></div>
                    <div className="pricing-cta-decoration-bl"></div>
                    <div className="pricing-cta-content">
                        <span className="pricing-cta-label">{ctaLabel}</span>
                        <h3 className="pricing-cta-headline">{ctaHeadline}</h3>
                        <p className="pricing-cta-intro">{ctaIntro}</p>

                        <ul className="pricing-cta-perks">
                            {perks.map(({ symbol, label, text }, i) => {
                                const Icon = PERK_ICONS[symbol] || Sparkles;
                                return (
                                <li key={i} className="pricing-cta-perk">
                                    <span className="pricing-cta-perk-icon"><Icon size={18} /></span>
                                    <div className="pricing-cta-perk-body">
                                        <strong className="pricing-cta-perk-label">{label}</strong>
                                        <span className="pricing-cta-perk-text">{text}</span>
                                    </div>
                                </li>
                                );
                            })}
                        </ul>

                        <p className="pricing-cta-closing">{ctaClosing}</p>

                        <CtaLink className="pricing-cta-button">
                            {ctaButton} <ArrowRight size={14} className="ml-2 inline" />
                        </CtaLink>
                    </div>
                </div>
            </div>}

            {stripHtml(footnote) && <p className="pricing-footnote">{stripHtml(footnote)}</p>}

            {showContactForm && <ContactForm />}
        </SectionWrapper>
    );
}
