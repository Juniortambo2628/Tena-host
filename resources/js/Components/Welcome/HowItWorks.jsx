import React from 'react';
import { SectionWrapper, SectionHeader, FeatureCard } from './layouts';
import { getContent, getMedia, extractItems } from '@/lib/cms';
import { SkeletonSectionHeader, SkeletonStepGrid } from './Skeleton';
import './HowItWorks.css';

const defaultSteps = [
    { step: '1', icon: 'fas fa-wifi', title: 'Guest Connects', description: 'Guest connects to WiFi & data is captured through a branded splash page.', image: '/legacy/assets/Tena-Landing/Step-1-Connect.jpg' },
    { step: '2', icon: 'fas fa-home', title: 'Guest Homepage', description: 'A customized landing page is shown with native upsell storefronts & guidebooks.', image: '/legacy/assets/Tena-Landing/Step-2-Data-Collection.jpg' },
    { step: '3', icon: 'fas fa-envelope-open-text', title: 'Automated Msgs', description: 'Guest automatically receives welcome email, SMS reviews, & stay anniversaries.', image: '/legacy/assets/Tena-Landing/Step-3-Remarket.jpg' },
    { step: '4', icon: 'fas fa-calendar-check', title: 'Direct Rebooking', description: 'Guest books direct for next trip and keeps coming back.', image: '/legacy/assets/Tena-Landing/Branded-Splash-Page.jpg' },
];

export default function HowItWorks({ section }) {
    if (!section) {
        return (
            <SectionWrapper id="how-it-works" bg="white">
                <SkeletonSectionHeader />
                <SkeletonStepGrid count={4} />
            </SectionWrapper>
        );
    }

    const title = getContent(section, 'title', 'How Tena Works');
    const subtitle = getContent(section, 'subtitle', 'Four simple steps to capture guest data, engage them with your brand, and drive direct bookings.');

    const cmsSteps = extractItems(section, 'steps', ['step', 'icon', 'title', 'description']);
    const steps = cmsSteps.length > 0
        ? cmsSteps.map((s, i) => ({
            ...s,
            image: getMedia(section, `step_${i}_image`, defaultSteps[i]?.image || ''),
        }))
        : defaultSteps;

    return (
        <SectionWrapper id="how-it-works" bg={section.bg || 'white'}>
            <SectionHeader title={title} subtitle={subtitle} />
            <div className="how-steps-grid">
                {steps.map((step, index) => (
                    <FeatureCard
                        key={index}
                        icon={step.icon}
                        title={step.title}
                        description={step.description}
                        image={step.image}
                        step={step.step}
                    />
                ))}
            </div>
        </SectionWrapper>
    );
}
