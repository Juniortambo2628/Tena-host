import React from 'react';
import { usePage } from '@inertiajs/react';
import { ArrowRight, Check } from 'lucide-react';
import { SectionWrapper } from './layouts';
import { getContent, getText, extractItems, stripHtml, isTruthy, textLines } from '@/lib/cms';
import { usePublic } from '@/Components/Public/PublicContext';
import CtaLink from '@/Components/Public/CtaLink';
import TrialSlots from '@/Components/Public/TrialSlots';
import './CtaBanner.css';

/** "whatsapp:Hi TenaFi…" opens WhatsApp on the Site-wide number with that message. */
function useHref() {
    const { site } = usePublic();
    const number = getText(site.whatsapp_button, 'number').replace(/\D/g, '');
    return (href) => (href?.startsWith('whatsapp:') && number
        ? `https://wa.me/${number}?text=${encodeURIComponent(href.slice(9).trim())}`
        : href);
}

/**
 * Call-to-action band: the free trial and cross-links between audiences.
 * A button with an empty href goes to this page's sign-up form.
 *
 * Style "trial" (homepage) adds ticks, the slot counter and an
 * "After the trial" card listing the plans from the live /hosts pricing.
 */
export default function CtaBanner({ section }) {
    const { site } = usePublic();
    const { plans = [] } = usePage().props;
    const resolve = useHref();
    if (!section) return null;

    const buttons = extractItems(section, 'buttons', ['label', 'href']);
    const badge = stripHtml(getContent(section, 'badge', ''));
    const note = stripHtml(getContent(section, 'note', ''));
    const trial = stripHtml(getContent(section, 'style', '')) === 'trial';
    const ticks = textLines(getContent(section, 'ticks', ''), site);
    const links = extractItems(section, 'pricing_links', ['label', 'href']).filter(l => stripHtml(l.label));
    const priced = plans.filter(p => p.price);

    const actions = (
        <div className="cta-banner-actions">
            {buttons.map((button, i) => {
                const href = resolve(stripHtml(button.href));
                const external = href?.startsWith('https://wa.me/');
                const className = i === 0 ? 'cta-banner-btn' : 'cta-banner-btn cta-banner-btn--ghost';
                return external
                    ? <a key={i} href={href} target="_blank" rel="noopener noreferrer" className={className}>{stripHtml(button.label)} <ArrowRight size={16} /></a>
                    : <CtaLink key={i} href={href} className={className}>{stripHtml(button.label)} <ArrowRight size={16} /></CtaLink>;
            })}
        </div>
    );

    if (!trial) {
        return (
            <SectionWrapper bg={section.bg || 'white'}>
                <div className="cta-banner">
                    {badge && <span className="cta-banner-badge">{badge}</span>}
                    <h2 className="cta-banner-title">{stripHtml(getContent(section, 'title', ''))}</h2>
                    <p className="cta-banner-body">{stripHtml(getContent(section, 'body', ''))}</p>
                    {actions}
                    {note && <p className="cta-banner-note">{note}</p>}
                </div>
            </SectionWrapper>
        );
    }

    return (
        <SectionWrapper bg={section.bg || 'white'}>
            <div className="cta-trial">
                <div className="cta-trial__main">
                    {badge && <span className="cta-banner-badge">{badge}</span>}
                    <h2 className="cta-trial__title">{stripHtml(getContent(section, 'title', ''))}</h2>
                    <p className="cta-trial__body">{stripHtml(getContent(section, 'body', ''))}</p>
                    {ticks.length > 0 && (
                        <ul className="cta-trial__ticks">
                            {ticks.map((t, i) => <li key={i}><Check size={16} aria-hidden="true" />{t.text}</li>)}
                        </ul>
                    )}
                    {isTruthy(getContent(section, 'show_slots', '0')) && <TrialSlots className="cta-trial__slots" />}
                    {priced.length > 0 && (
                        <div className="cta-trial__pricing" id={stripHtml(getContent(section, 'pricing_anchor', '')) || undefined}>
                            <b>{stripHtml(getContent(section, 'pricing_title', 'After the trial'))}</b>
                            <ul>
                                {priced.map(plan => <li key={plan.id || plan.label}><span>{plan.label}</span><strong>{plan.price}</strong></li>)}
                            </ul>
                            <small>
                                {stripHtml(getContent(section, 'pricing_note', ''))}{' '}
                                {links.map((l, i) => (
                                    <React.Fragment key={i}>
                                        {i > 0 && ' · '}
                                        <a href={stripHtml(l.href)}>{stripHtml(l.label)}</a>
                                    </React.Fragment>
                                ))}
                            </small>
                        </div>
                    )}
                </div>
                <div className="cta-trial__book">
                    {stripHtml(getContent(section, 'buttons_title', '')) && <b className="cta-trial__book-title">{stripHtml(getContent(section, 'buttons_title', ''))}</b>}
                    {actions}
                    {note && <p className="cta-banner-note">{note}</p>}
                </div>
            </div>
        </SectionWrapper>
    );
}
