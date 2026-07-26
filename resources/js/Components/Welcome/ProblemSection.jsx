import React from 'react';
import { SectionWrapper, TwoColumn } from './layouts';
import { getContent, getMedia } from '@/lib/cms';
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

    const badge = getContent(section, 'badge', 'Did you know?');
    const title = getContent(section, 'title', 'OTA Commissions Are Costing You');
    const description = getContent(section, 'description', 'OTAs (Online Travel Agencies) can take up to 20% of your booking revenue — and you lose control of the guest relationship.');

    const images = [
        getMedia(section, 'image_0', '/legacy/assets/Tena-Landing/Problem-1.jpg'),
        getMedia(section, 'image_1', '/legacy/assets/Tena-Landing/Problem-2.jpg'),
        getMedia(section, 'image_2', '/legacy/assets/Tena-Landing/Problem-3.jpg'),
    ];

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
            />
        </SectionWrapper>
    );
}
