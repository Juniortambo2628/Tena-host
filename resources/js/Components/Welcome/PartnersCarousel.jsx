import React from 'react';
import { getContent, getMedia, extractItems } from '@/lib/cms';
import { SectionWrapper } from './layouts';
import './PartnersCarousel.css';

const defaultPartners = [
    { name: 'Stay Awhile Rentals', url: 'https://stayawhilerentals.com' },
    { name: 'Airbnb', url: '#' },
    { name: 'Booking.com', url: '#' },
    { name: 'Vrbo', url: '#' },
    { name: 'Expedia', url: '#' },
    { name: 'Hostaway', url: '#' },
];

export default function PartnersCarousel({ section }) {
    if (!section) return null;

    const title = getContent(section, 'title', 'Trusted by hosts and partners across Africa');
    const subtitle = getContent(section, 'subtitle', '');

    const cmsPartners = extractItems(section, 'partners', ['name', 'url']);
    const partners = (cmsPartners.length > 0 ? cmsPartners : defaultPartners).map((partner, i) => ({
        ...partner,
        logo: getMedia(section, `partner_${i}_logo`, '/legacy/assets/Tena-logo-square.jpg'),
    }));

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
                    {loop.map((partner, i) => (
                        <li key={`${partner.name}-${i}`} className="partners-item">
                            {partner.url && partner.url !== '#' ? (
                                <a
                                    href={partner.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="partners-link"
                                    aria-label={partner.name}
                                >
                                    <img
                                        src={partner.logo}
                                        alt={partner.name}
                                        className="partners-logo"
                                        loading="lazy"
                                    />
                                </a>
                            ) : (
                                <span className="partners-link" aria-label={partner.name}>
                                    <img
                                        src={partner.logo}
                                        alt={partner.name}
                                        className="partners-logo"
                                        loading="lazy"
                                    />
                                </span>
                            )}
                        </li>
                    ))}
                </ul>
            </div>
        </SectionWrapper>
    );
}
