import React from 'react';
import { SectionWrapper } from './layouts';
import { getContent, getMedia, extractItems, sanitizeHtml } from '@/lib/cms';
import { SkeletonSectionHeader } from './Skeleton';
import './Credibility.css';

const defaultStats = [
    { value: '16×', label: 'Superhost' },
    { value: '1,400+', label: 'Reservations' },
    { value: '5,000+', label: 'Guest Nights' },
    { value: '750+', label: 'Guest Reviews' },
    { value: '4.9/5', label: 'Guest Rating' },
];

export default function Credibility({ section }) {
    if (!section) {
        return (
            <SectionWrapper id="credibility" bg="gray">
                <SkeletonSectionHeader />
            </SectionWrapper>
        );
    }

    const badge = getContent(section, 'badge', 'The experience behind Tena — Stay Awhile Rentals');
    const title = getContent(section, 'title', 'Built by Superhosts. Built for SuperHosts.');
    const subtitle = getContent(
        section,
        'subtitle',
        'Tena was born from Stay Awhile Rentals — a real short-term rental business. After years of hosting thousands of guests, we saw firsthand how difficult it can be for hosts to build direct relationships with guests beyond the booking platform. That experience became Tena.'
    );
    const closingLine = getContent(
        section,
        'closing_line',
        "We experienced the problem ourselves. Now we're building the solution for Superhosts across Africa."
    );
    const tagline = getContent(section, 'tagline', 'Own the Guest. Build the Relationship.');

    const mainImage = getMedia(section, 'main_image', '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg');
    const stayAwhileLogo = getMedia(section, 'stay_awhile_logo', '/legacy/assets/Tena-logo-square.jpg');

    const cmsStats = extractItems(section, 'stats', ['value', 'label']);
    const stats = cmsStats.length > 0 ? cmsStats : defaultStats;

    return (
        <SectionWrapper id="credibility" bg={section.bg || 'gray'}>
            <div className="credibility-card">
                <div className="credibility-decoration"></div>
                <div className="credibility-decoration-bl"></div>

                <div className="credibility-content">
                    <div className="credibility-text">
                        <div className="credibility-badge-row">
                            <img
                                src={stayAwhileLogo}
                                alt="Stay Awhile Rentals"
                                className="credibility-partner-logo"
                            />
                            <span className="credibility-badge">{badge}</span>
                        </div>

                        <h2 className="credibility-title">{title}</h2>
                        <p
                            className="credibility-subtitle"
                            dangerouslySetInnerHTML={{ __html: sanitizeHtml(subtitle) }}
                        />

                        <ul className="credibility-stats">
                            {stats.map((stat, i) => (
                                <li key={i} className="credibility-stat">
                                    <span className="credibility-stat-value">{stat.value}</span>
                                    <span className="credibility-stat-label">{stat.label}</span>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div className="credibility-image-col">
                        <div className="credibility-image-wrap">
                            <img
                                src={mainImage}
                                alt="Stay Awhile Rentals property"
                                className="credibility-image"
                            />
                            <span className="credibility-image-caption">{badge}</span>
                        </div>
                    </div>
                </div>

                <div className="credibility-footer">
                    <p className="credibility-closing" dangerouslySetInnerHTML={{ __html: sanitizeHtml(closingLine) }} />
                    <p className="credibility-tagline">{tagline}</p>
                </div>
            </div>
        </SectionWrapper>
    );
}
