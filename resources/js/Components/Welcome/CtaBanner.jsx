import React from 'react';
import { ArrowRight } from 'lucide-react';
import { SectionWrapper } from './layouts';
import { getContent, extractItems, stripHtml } from '@/lib/cms';
import CtaLink from '@/Components/Public/CtaLink';
import './CtaBanner.css';

/**
 * Dark call-to-action band (Founding 20, cross-links between audiences).
 * A button with an empty href goes to this page's sign-up form.
 */
export default function CtaBanner({ section }) {
    if (!section) return null;

    const buttons = extractItems(section, 'buttons', ['label', 'href']);
    const badge = stripHtml(getContent(section, 'badge', ''));
    const note = stripHtml(getContent(section, 'note', ''));

    return (
        <SectionWrapper bg={section.bg || 'white'}>
            <div className="cta-banner">
                {badge && <span className="cta-banner-badge">{badge}</span>}
                <h2 className="cta-banner-title">{stripHtml(getContent(section, 'title', ''))}</h2>
                <p className="cta-banner-body">{stripHtml(getContent(section, 'body', ''))}</p>
                <div className="cta-banner-actions">
                    {buttons.map((button, i) => (
                        <CtaLink key={i} href={button.href} className={i === 0 ? 'cta-banner-btn' : 'cta-banner-btn cta-banner-btn--ghost'}>
                            {stripHtml(button.label)} <ArrowRight size={16} />
                        </CtaLink>
                    ))}
                </div>
                {note && <p className="cta-banner-note">{note}</p>}
            </div>
        </SectionWrapper>
    );
}
