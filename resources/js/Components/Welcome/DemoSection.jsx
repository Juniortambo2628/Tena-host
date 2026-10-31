import React from 'react';
import { usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { SectionWrapper } from './layouts';
import { getContent, stripHtml, textLines } from '@/lib/cms';
import { usePublic } from '@/Components/Public/PublicContext';
import CtaLink from '@/Components/Public/CtaLink';
import './DemoSection.css';

/**
 * "Try it yourself": a QR code for /demo (the real WiFi page with sample
 * places). Phones and tablets get a button instead of the code. Reusable
 * on any page (section type "demo"); hidden while the demo is switched off
 * in Admin -> Settings.
 */
export default function DemoSection({ section }) {
    const { demo } = usePage().props;
    const { site } = usePublic();
    if (!section || !demo) return null;

    const text = (key, fallback = '') => stripHtml(getContent(section, key, fallback));
    const checklist = textLines(getContent(section, 'checklist', ''), site);
    const demoUrl = text('demo_url', '/demo');

    return (
        <SectionWrapper bg={section.bg || 'white'}>
            <div className="demo-section">
                <div className="demo-section__text">
                    {text('badge') && <span className="demo-section__eyebrow">{text('badge')}</span>}
                    <h2>{text('title')}</h2>
                    {text('subtitle') && <p className="demo-section__lede">{text('subtitle')}</p>}
                    {checklist.length > 0 && (
                        <ul className="demo-section__checks">
                            {checklist.map((c, i) => <li key={i}><Check size={18} aria-hidden="true" />{c.text}</li>)}
                        </ul>
                    )}
                    <div className="demo-section__actions">
                        <a className="demo-section__open" href={demoUrl}>{text('button_label', 'Open the demo WiFi page')}</a>
                        {text('cta_label') && <CtaLink href={text('cta_href')} className="demo-section__cta">{text('cta_label')}</CtaLink>}
                    </div>
                </div>
                <div className="demo-section__qr">
                    <img src={text('qr_image', '/brand/demo-qr.svg')} alt={`QR code: ${text('url_label', demoUrl)}`} width="220" height="220" />
                    <b>{text('qr_label', 'Point your phone camera here')}</b>
                    <small>{text('url_label')}</small>
                </div>
            </div>
        </SectionWrapper>
    );
}
