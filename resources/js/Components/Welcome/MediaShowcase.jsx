import React from 'react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, getMedia } from '@/lib/cms';
import './MediaShowcase.css';

export default function MediaShowcase({ section }) {
    if (!section) return null;

    const heading = getContent(section, 'heading', 'See Tena in Action');
    const description = getContent(section, 'description', '');
    const mediaType = getContent(section, 'media_type', 'video');
    const mediaSrc = getMedia(section, 'showcase_media')
        || getMedia(section, 'feature_video')
        || getMedia(section, 'FEATURE_VIDEO')
        || getMedia(section, 'video')
        || getMedia(section, 'video_slot')
        || (section.media && typeof section.media === 'object' ? Object.values(section.media)[0] : '')
        || '';

    if (!mediaSrc) return null;

    return (
        <SectionWrapper id="media-showcase" bg={section.bg || 'gray'} width="wide">
            <SectionHeader title={heading} subtitle={description} />
            <div className="media-showcase">
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
                    <div className="media-showcase__overlay" />
                </div>
            </div>
        </SectionWrapper>
    );
}
