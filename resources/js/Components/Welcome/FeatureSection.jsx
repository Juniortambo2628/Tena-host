import React from 'react';
import { SectionWrapper, SectionHeader, FeatureCard } from './layouts';
import { getContent, extractItems, stripHtml, comingSoonBadge } from '@/lib/cms';
import { usePublic } from '@/Components/Public/PublicContext';
import { SkeletonSectionHeader, SkeletonFeatureGrid } from './Skeleton';
import './FeatureSection.css';

export default function FeatureSection({ section }) {
    const { site } = usePublic();

    if (!section) {
        return (
            <SectionWrapper id="features" bg="white">
                <SkeletonSectionHeader />
                <SkeletonFeatureGrid count={6} />
            </SectionWrapper>
        );
    }

    const features = extractItems(section, 'items', ['step', 'icon', 'title', 'description', 'feature']).map((f) => ({
        ...f,
        badge: comingSoonBadge(site, f.feature),
    }));

    return (
        <SectionWrapper bg={section.bg || 'white'}>
            <SectionHeader
                badge={stripHtml(getContent(section, 'badge', ''))}
                title={stripHtml(getContent(section, 'title', ''))}
                subtitle={getContent(section, 'subtitle', '')}
            />
            <div className={`feature-grid ${features.length === 4 ? 'feature-grid--4' : ''}`}>
                {features.map(({ feature, ...card }, index) => (
                    <FeatureCard key={index} {...card} />
                ))}
            </div>
        </SectionWrapper>
    );
}
