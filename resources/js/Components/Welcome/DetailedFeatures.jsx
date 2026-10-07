import React, { useState } from 'react';
import { SectionWrapper, SectionHeader, TwoColumn } from './layouts';
import { getContent, getMedia, extractJsonItems, comingSoonBadge, stripHtml } from '@/lib/cms';
import { usePublic } from '@/Components/Public/PublicContext';
import CtaLink from '@/Components/Public/CtaLink';
import { SkeletonTwoColumn } from './Skeleton';
import './DetailedFeatures.css';

// Fallback images when a feature block has no upload yet.
const DEFAULT_IMAGES = [
    '/legacy/assets/Tena-Landing/Branded-Splash-Page.jpg',
    '/legacy/assets/Tena-Landing/Tena-Portrait-1.jpg',
    '/legacy/assets/Tena-Landing/Tena-Features-1.jpg',
    '/legacy/assets/Tena-Landing/Clients-view.jpg',
];

function FeatureImage({ src, alt, defaultSrc }) {
    const [imgSrc, setImgSrc] = useState(src || defaultSrc);
    const [tried, setTried] = useState(false);

    return (
        <img
            src={imgSrc}
            alt={alt}
            onError={() => {
                if (!tried && defaultSrc && imgSrc !== defaultSrc) {
                    setTried(true);
                    setImgSrc(defaultSrc);
                }
            }}
        />
    );
}

export default function DetailedFeatures({ section }) {
    const { site } = usePublic();

    if (!section) {
        return (
            <SectionWrapper bg="white" padding="lg">
                <SkeletonTwoColumn />
            </SectionWrapper>
        );
    }

    // Parse CMS sections from content
    const sectionCount = Object.keys(section.content || {}).filter(k => k.startsWith('sections.') && k.endsWith('.label')).length;

    const sections = [];
    for (let i = 0; i < sectionCount; i++) {
        // Rows tied to a feature key get a "Coming soon" badge until that
        // feature is marked live under Site-wide -> Feature status.
        const features = extractJsonItems(section, `sections.${i}.features`).map((f) => ({
            ...f,
            badge: comingSoonBadge(site, f.feature),
        }));
        sections.push({
            id: `detail-${i}`,
            label: getContent(section, `sections.${i}.label`, ''),
            heading: getContent(section, `sections.${i}.heading`, ''),
            description: getContent(section, `sections.${i}.description`, ''),
            image: getMedia(section, `section_${i}_image`, ''),
            bg: getContent(section, `sections.${i}.bg`, 'white'),
            reverse: getContent(section, `sections.${i}.reverse`, '0') === '1',
            features,
        });
    }

    const ctaText = stripHtml(getContent(section, 'cta_text', 'Get started'));
    const heading = stripHtml(getContent(section, 'title', ''));

    return (
        <div className="detailed-features-wrapper">
            {heading && (
                <SectionWrapper bg="white" padding="sm">
                    <SectionHeader title={heading} subtitle={getContent(section, 'subtitle', '')} />
                </SectionWrapper>
            )}
            {sections.map((s, i) => (
                <SectionWrapper key={s.id} id={s.id} bg={s.bg} padding="lg">
                    <TwoColumn
                        reverse={s.reverse}
                        label={s.label}
                        heading={s.heading}
                        description={s.description}
                        image={<FeatureImage src={s.image} alt={s.label} defaultSrc={DEFAULT_IMAGES[i % DEFAULT_IMAGES.length]} />}
                        features={s.features}
                        cta={
                            <CtaLink className="btn-primary">{ctaText}</CtaLink>
                        }
                    />
                </SectionWrapper>
            ))}
        </div>
    );
}
