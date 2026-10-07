import React from 'react';
import { SectionWrapper, SectionHeader, FeatureCard } from './layouts';
import { getContent, getMedia, extractItems, comingSoonBadge, stripHtml } from '@/lib/cms';
import { usePublic } from '@/Components/Public/PublicContext';
import { SkeletonSectionHeader, SkeletonStepGrid } from './Skeleton';
import './HowItWorks.css';

export default function HowItWorks({ section }) {
    const { site } = usePublic();

    if (!section) {
        return (
            <SectionWrapper id="how-it-works" bg="white">
                <SkeletonSectionHeader />
                <SkeletonStepGrid count={4} />
            </SectionWrapper>
        );
    }

    const title = stripHtml(getContent(section, 'title', 'How TenaFi works'));
    const subtitle = getContent(section, 'subtitle', '');

    const steps = extractItems(section, 'steps', ['step', 'icon', 'title', 'description', 'feature']).map((s, i) => ({
        ...s,
        image: getMedia(section, `step_${i}_image`, ''),
        badge: comingSoonBadge(site, s.feature),
    }));

    return (
        <SectionWrapper bg={section.bg || 'white'}>
            <SectionHeader title={title} subtitle={subtitle} />
            <div className={`how-steps-grid how-steps-grid--${steps.length}`}>
                {steps.map((step, index) => (
                    <FeatureCard
                        key={index}
                        icon={step.icon}
                        title={step.title}
                        description={step.description}
                        image={step.image}
                        step={step.step}
                        badge={step.badge}
                    />
                ))}
            </div>
        </SectionWrapper>
    );
}
