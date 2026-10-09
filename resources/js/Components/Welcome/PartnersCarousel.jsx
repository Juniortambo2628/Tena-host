import React from 'react';
import { getMedia, extractItems, getText } from '@/lib/cms';
import { SectionWrapper } from './layouts';
import './PartnersCarousel.css';

export default function PartnersCarousel({ section }) {
    if (!section) return null;

    const title = getText(section, 'title', 'Trusted by hosts and partners across Africa');
    const subtitle = getText(section, 'subtitle', '');

    // Only businesses entered in the CMS; never placeholder brands.
    const cmsPartners = extractItems(section, 'partners', ['name', 'url']);
    const partners = cmsPartners.map((partner, i) => ({
        ...partner,
        // Logo or photo uploaded in Admin → Public Pages → Media (slot i matches row i).
        logo: getMedia(section, `partner_${i}_logo`, ''),
    })).filter((p) => p.name);

    if (partners.length === 0) return null;

    // Duplicate the list so the marquee loops seamlessly.
    const loop = [...partners, ...partners];

    return (
        <SectionWrapper id="partners" bg={section.bg || 'white'}>
            <div className="partners-header">
                <h3 className="partners-title">{title}</h3>
                {subtitle && <p className="partners-subtitle">{subtitle}</p>}
            </div>

            <div
                className="partners-marquee"
                role="region"
                aria-label="Trusted partners"
                style={{ '--partners-count': partners.length }}
            >
                <ul className="partners-track">
                    {loop.map((partner, i) => {
                        const linked = partner.url && partner.url !== '#';
                        const Tile = linked ? 'a' : 'span';
                        return (
                            <li key={`${partner.name}-${i}`} className="partners-item" aria-hidden={i >= partners.length || undefined}>
                                <Tile
                                    {...(linked ? { href: partner.url, target: '_blank', rel: 'noopener noreferrer' } : {})}
                                    className="partners-link"
                                    tabIndex={i >= partners.length ? -1 : undefined}
                                >
                                    {partner.logo
                                        ? <img src={partner.logo} alt="" className="partners-logo" loading="lazy" />
                                        : <span className="partners-logo partners-logo--text" aria-hidden="true">{partner.name.charAt(0)}</span>}
                                    <span className="partners-name">{partner.name}</span>
                                </Tile>
                            </li>
                        );
                    })}
                </ul>
            </div>
        </SectionWrapper>
    );
}
