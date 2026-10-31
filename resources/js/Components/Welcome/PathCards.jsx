import React from 'react';
import { ArrowRight } from 'lucide-react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, getMedia, extractItems, sanitizeHtml, stripHtml } from '@/lib/cms';
import { SkeletonSectionHeader } from './Skeleton';
import CtaLink from '@/Components/Public/CtaLink';
import Mockup from '@/Components/Public/Mockup';
import './PathCards.css';

/**
 * "Which one sounds like you?" — one card per audience page. Each card's
 * `event` (e.g. path_card_hosts) is tracked on click.
 */
export default function PathCards({ section }) {
    if (!section) {
        return (
            <SectionWrapper id="paths" bg="white">
                <SkeletonSectionHeader />
            </SectionWrapper>
        );
    }

    // Optional per card: tags (comma-separated) and a drawn mockup instead of the photo.
    const cards = extractItems(section, 'cards', ['label', 'title', 'description', 'cta', 'href', 'event', 'tags', 'mockup', 'screen']);

    return (
        <SectionWrapper bg={section.bg || 'white'}>
            <SectionHeader
                title={stripHtml(getContent(section, 'title', ''))}
                subtitle={getContent(section, 'subtitle', '')}
            />
            <div className={`path-cards-grid path-cards-grid--${cards.length}`}>
                {cards.map((card, i) => (
                    <CtaLink key={i} href={card.href} event={card.event} className="path-card">
                        {stripHtml(card.mockup) ? (
                            <div className="path-card-mockup">
                                <Mockup type={stripHtml(card.mockup)} screen={card.screen} />
                            </div>
                        ) : (
                            <div className="path-card-image-wrap">
                                <img src={getMedia(section, `card_${i}_image`, '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg')} alt={stripHtml(card.label)} />
                            </div>
                        )}
                        <div className="path-card-body">
                            <span className="path-card-label">{stripHtml(card.label)}</span>
                            <h3 className="path-card-title">{stripHtml(card.title)}</h3>
                            <p className="path-card-desc" dangerouslySetInnerHTML={{ __html: sanitizeHtml(card.description) }} />
                            {stripHtml(card.tags) && (
                                <ul className="path-card-tags">
                                    {stripHtml(card.tags).split(',').map(t => t.trim()).filter(Boolean).map(t => <li key={t}>{t}</li>)}
                                </ul>
                            )}
                            <span className="path-card-cta">
                                {stripHtml(card.cta)} <ArrowRight size={16} />
                            </span>
                        </div>
                    </CtaLink>
                ))}
            </div>
        </SectionWrapper>
    );
}
