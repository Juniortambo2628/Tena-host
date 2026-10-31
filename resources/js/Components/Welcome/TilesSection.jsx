import React from 'react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, extractItems, stripHtml } from '@/lib/cms';
import './TilesSection.css';

/**
 * A row of small tiles: icon, title and one line (e.g. the kinds of events
 * on /events). Reusable on any page as section type "tiles".
 */
export default function TilesSection({ section }) {
    if (!section) return null;

    const items = extractItems(section, 'items', ['icon', 'title', 'text']).filter(i => stripHtml(i.title));

    return (
        <SectionWrapper bg={section.bg || 'white'}>
            <SectionHeader
                title={stripHtml(getContent(section, 'title', ''))}
                subtitle={getContent(section, 'subtitle', '')}
                align="left"
            />
            <ul className="tiles-grid">
                {items.map((item, i) => (
                    <li key={i} className="tiles-item">
                        {stripHtml(item.icon) && <span className="tiles-item__icon" aria-hidden="true">{stripHtml(item.icon)}</span>}
                        <b>{stripHtml(item.title)}</b>
                        {stripHtml(item.text) && <span>{stripHtml(item.text)}</span>}
                    </li>
                ))}
            </ul>
        </SectionWrapper>
    );
}
