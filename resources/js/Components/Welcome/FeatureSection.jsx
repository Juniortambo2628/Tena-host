import React from 'react';
import { SectionWrapper, SectionHeader, FeatureCard } from './layouts';
import { getContent, extractItems } from '@/lib/cms';
import { SkeletonSectionHeader, SkeletonFeatureGrid } from './Skeleton';
import './FeatureSection.css';

const defaultFeatures = [
    { icon: 'fas fa-wifi', title: 'WiFi Splash Pages', description: 'Guest-facing splash pages with your branding to collect emails and phone numbers.' },
    { icon: 'fas fa-users', title: 'Guest Data Collection', description: 'Collect guest emails and phone numbers securely during WiFi onboarding.' },
    { icon: 'fas fa-bullhorn', title: 'Remarketing Tools', description: 'Auto-send review reminders and rebook direct via SMS and Email campaigns.' },
    { icon: 'fas fa-chart-line', title: 'Analytics Dashboard', description: 'View guest captures, campaign performance and bookings in one place.' },
    { icon: 'fas fa-plug', title: 'PMS Integrations', description: 'Integrate with PMS and channel managers to keep bookings in sync.' },
    { icon: 'fas fa-shield-alt', title: 'Privacy & Compliance', description: 'We follow best practices for guest consent and data protection.' },
];

export default function FeatureSection({ section }) {
    if (!section) {
        return (
            <SectionWrapper id="features" bg="white">
                <SkeletonSectionHeader />
                <SkeletonFeatureGrid count={6} />
            </SectionWrapper>
        );
    }

    const title = getContent(section, 'title', 'Why Hosts Choose Tena');
    const cmsFeatures = extractItems(section, 'items', ['icon', 'title', 'description']);
    const features = cmsFeatures.length > 0 ? cmsFeatures : defaultFeatures;

    return (
        <SectionWrapper id="features" bg={section.bg || 'white'}>
            <SectionHeader
                title={<>{title.split('Tena').length > 1 ? <>{title.split('Tena')[0]}<span className="text-[#FFD300]">Tena</span>{title.split('Tena')[1]}</> : title}</>}
            />
            <div className="feature-grid">
                {features.map((feature, index) => (
                    <FeatureCard key={index} {...feature} />
                ))}
            </div>
        </SectionWrapper>
    );
}
