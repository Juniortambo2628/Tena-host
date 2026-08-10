import React, { useState } from 'react';
import { SectionWrapper, TwoColumn } from './layouts';
import { getContent, getMedia, extractJsonItems } from '@/lib/cms';
import { SkeletonTwoColumn } from './Skeleton';
import './DetailedFeatures.css';

const defaultSections = [
    { id: 'data-collection', label: 'Guest Data Collection', heading: 'Maximize on Collecting Information from Not Only the Booker but Every Guest', description: 'Transform your WiFi into a powerful data collection tool. Capture contact details from every guest who connects, not just the person who made the booking.', image: '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg', bg: 'white', features: [{ icon: 'fas fa-wifi', title: 'Branded WiFi Splash Pages', desc: 'Customize your WiFi login page with your property branding' }, { icon: 'fas fa-envelope', title: 'Automatically Verified Emails', desc: 'Collect verified email addresses through our integrated verification' }, { icon: 'fas fa-shield-alt', title: 'GDPR Compliant Collection', desc: 'Ensure guest agreement to terms and conditions' }] },
    { id: 'email-marketing', label: 'Tena Email Marketing', heading: 'Send Pre-Built Emails That Drive Bookings in Seconds', description: 'Streamline your email marketing with our pre-designed templates and automated campaigns.', image: '/legacy/assets/Tena-Landing/Tena-Features-1.jpg', bg: 'gray', reverse: true, features: [{ icon: 'fas fa-mouse-pointer', title: 'Create with a Click', desc: 'Use pre-generated content blocks to build professional emails' }, { icon: 'fas fa-plug', title: 'PMS Integration', desc: 'Sync property details and guest data automatically' }, { icon: 'fas fa-user-plus', title: 'Pre-Arrival Data Collection', desc: 'Collect guest info through custom landing pages' }] },
    { id: 'guest-homepage', label: 'Guest Homepage', heading: 'Provide Upsell & Rental Information from One Dynamic Page', description: 'Create a centralized hub for your guests with all rental information, upselling opportunities, and property resources.', image: '/legacy/assets/Tena-Landing/Tena-Portrait-1.jpg', bg: 'white', features: [{ icon: 'fas fa-shopping-cart', title: 'Upsell Amenities', desc: 'Generate additional income through integrated partners' }, { icon: 'fas fa-key', title: 'Property Information', desc: 'Easy access to guidebooks and contact info' }, { icon: 'fas fa-comments', title: 'Meet Guests', desc: 'Engage guests via multiple communication channels' }] },
    { id: 'sms-marketing', label: 'Tena SMS Marketing', heading: 'Engage Guests With Text Marketing & Review Screening', description: 'Leverage the power of SMS marketing with high open rates to drive bookings, collect reviews, and increase revenue.', image: '/legacy/assets/Tena-Landing/Tena-Welcome-Divine-1.jpg', bg: 'gray', reverse: true, features: [{ icon: 'fas fa-star', title: 'Rate & Review Campaigns', desc: 'Automate review collection and screen for 5-star reviews' }, { icon: 'fas fa-wifi', title: 'WiFi Welcome Messages', desc: 'Send personalized welcome texts' }, { icon: 'fas fa-comments', title: 'Group Messages', desc: 'Send targeted marketing messages to past guests' }] },
    { id: 'wifi-monitoring', label: 'WiFi Monitoring & Protection', heading: 'Reduce WiFi Issues & Protect Your Property', description: 'Deploy enterprise-grade WiFi 6 mesh networks. Ensure reliable connectivity while protecting your investment.', image: '/legacy/assets/Tena-Landing/Clients-view.jpg', bg: 'white', features: [{ icon: 'fas fa-plug', title: 'Plug & Play Set-Up', desc: 'Arrives ready to plug into your router' }, { icon: 'fas fa-wifi', title: 'Remote Outage Alerts', desc: 'Monitor networks from one screen' }, { icon: 'fas fa-user-shield', title: 'Occupancy Alerting', desc: 'Get alerted if guest count exceeds booking' }] },
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

export default function DetailedFeatures({ onOpenWaitlist, section }) {
    if (!section) {
        return (
            <div className="detailed-features-wrapper">
                {defaultSections.map((s) => (
                    <SectionWrapper key={s.id} id={s.id} bg={s.bg} padding="lg">
                        <SkeletonTwoColumn />
                    </SectionWrapper>
                ))}
            </div>
        );
    }

    // Parse CMS sections from content
    const sectionCount = Object.keys(section.content || {}).filter(k => k.startsWith('sections.') && k.endsWith('.label')).length;

    const sections = [];
    for (let i = 0; i < sectionCount; i++) {
        const features = extractJsonItems(section, `sections.${i}.features`);
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

    const displaySections = sections.length > 0 ? sections : defaultSections;

    return (
        <div className="detailed-features-wrapper">
            {displaySections.map((s, i) => (
                <SectionWrapper key={s.id} id={s.id} bg={s.bg} padding="lg">
                    <TwoColumn
                        reverse={s.reverse}
                        label={s.label}
                        heading={s.heading}
                        description={s.description}
                        image={<FeatureImage src={s.image} alt={s.label} defaultSrc={defaultSections[i]?.image} />}
                        features={s.features}
                        cta={
                            <button onClick={onOpenWaitlist} className="btn-primary">
                                Get Started Today
                            </button>
                        }
                    />
                </SectionWrapper>
            ))}
        </div>
    );
}
