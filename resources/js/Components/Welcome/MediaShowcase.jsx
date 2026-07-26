import React from 'react';
import { SectionWrapper } from './layouts';
import { getContent, getMedia } from '@/lib/cms';
import './MediaShowcase.css';

export default function MediaShowcase({ section }) {
    if (!section) return null;

    const heading = getContent(section, 'heading', 'See Tena in Action');
    const description = getContent(section, 'description', '');
    const mediaType = getContent(section, 'media_type', 'video');
    const mediaSrc = getMedia(section, 'showcase_media', '');

    if (!mediaSrc) return null;

    return (
        <SectionWrapper id="media-showcase" bg={section.bg || 'gray'}>
            <div className="media-showcase">
                <div className="media-showcase__header">
                    <h2 className="media-showcase__heading">{heading}</h2>
                    {description && <p className="media-showcase__description">{description}</p>}
                </div>
                <div className="media-showcase__container">
                    {mediaType === 'video' ? (
                        <video
                            className="media-showcase__video"
                            src={mediaSrc}
                            autoPlay
                            loop
                            muted
                            playsInline
                            preload="auto"
                        />
                    ) : (
                        <img
                            className="media-showcase__image"
                            src={mediaSrc}
                            alt={heading}
                        />
                    )}
                    <div className="media-showcase__overlay">
                        <div className="media-showcase__brand">
                            <img src="/legacy/assets/Tena-logo-square.jpg" alt="Tena" className="media-showcase__logo" />
                            <p className="media-showcase__tagline">Own the Guest. Build the Relationship</p>
                        </div>
                    </div>
                </div>
            </div>
        </SectionWrapper>
    );
}
