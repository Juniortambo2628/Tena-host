import React from 'react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, getMedia, extractItems, stripHtml } from '@/lib/cms';
import './StatsSection.css';

/**
 * Headline numbers with their sources (occupancy problem, review facts,
 * commission comparison). Optional illustration image with caption.
 */
export default function StatsSection({ section }) {
    if (!section) return null;

    const stats = extractItems(section, 'stats', ['value', 'label']);
    const image = getMedia(section, 'image', '');
    const caption = stripHtml(getContent(section, 'image_caption', ''));
    const note = getContent(section, 'note', '');
    const sources = stripHtml(getContent(section, 'sources', ''));

    return (
        <SectionWrapper bg={section.bg || 'white'}>
            <SectionHeader
                badge={stripHtml(getContent(section, 'badge', ''))}
                title={stripHtml(getContent(section, 'title', ''))}
                subtitle={getContent(section, 'subtitle', '')}
            />
            <div className={`stats-grid stats-grid--${Math.min(stats.length, 4)}`}>
                {stats.map((stat, i) => (
                    <div key={i} className="stats-item">
                        <span className="stats-value">{stripHtml(stat.value)}</span>
                        <span className="stats-label">{stripHtml(stat.label)}</span>
                    </div>
                ))}
            </div>
            {image && (
                <figure className="stats-figure">
                    <img src={image} alt={caption || stripHtml(getContent(section, 'title', ''))} />
                    {caption && <figcaption>{caption}</figcaption>}
                </figure>
            )}
            {!image && caption && <p className="stats-caption">{caption}</p>}
            {stripHtml(note) && <p className="stats-note">{stripHtml(note)}</p>}
            {sources && <p className="stats-sources">{sources}</p>}
        </SectionWrapper>
    );
}
