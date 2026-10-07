import React from 'react';
import { SectionWrapper, TwoColumn } from './layouts';
import { getContent, getMedia, stripHtml } from '@/lib/cms';
import { SkeletonTwoColumn } from './Skeleton';
import './ProblemSection.css';

export default function ProblemSection({ section }) {
    if (!section) {
        return (
            <SectionWrapper id="problem" bg="white">
                <SkeletonTwoColumn />
            </SectionWrapper>
        );
    }

    const badge = getContent(section, 'badge', 'The problem');
    const title = getContent(section, 'title', '');
    const description = getContent(section, 'description', '');
    const stat = {
        value: stripHtml(getContent(section, 'stat_value', '')),
        label: stripHtml(getContent(section, 'stat_label', '')),
        source: stripHtml(getContent(section, 'stat_source', '')),
    };

    const images = ['image_0', 'image_1', 'image_2'].map((key) => getMedia(section, key, '')).filter(Boolean);

    return (
        <SectionWrapper id="problem" bg={section.bg || 'white'}>
            <TwoColumn
                label={badge}
                heading={title}
                description={description}
                image={
                    <div className="problem-images-grid">
                        {images.map((img, i) => (
                            <div key={i} className="problem-image-item">
                                <img src={img} alt={`Problem ${i + 1}`} />
                            </div>
                        ))}
                    </div>
                }
            >
                {stat.value && (
                    <div className="problem-stat">
                        <span className="problem-stat-value">{stat.value}</span>
                        <span className="problem-stat-label">
                            {stat.label}
                            {stat.source && <span className="problem-stat-source">Source: {stat.source}</span>}
                        </span>
                    </div>
                )}
            </TwoColumn>
        </SectionWrapper>
    );
}
