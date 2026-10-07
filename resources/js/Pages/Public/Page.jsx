import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { getContent, baseSectionKey, extractItems, stripHtml } from '@/lib/cms';
import Hero from '@/Components/Welcome/Hero';
import PathCards from '@/Components/Welcome/PathCards';
import FeatureSection from '@/Components/Welcome/FeatureSection';
import DetailedFeatures from '@/Components/Welcome/DetailedFeatures';
import ProblemSection from '@/Components/Welcome/ProblemSection';
import HowItWorks from '@/Components/Welcome/HowItWorks';
import ROICalculator from '@/Components/Welcome/ROICalculator';
import Pricing from '@/Components/Welcome/Pricing';
import MediaShowcase from '@/Components/Welcome/MediaShowcase';
import Credibility from '@/Components/Welcome/Credibility';
import PartnersCarousel from '@/Components/Welcome/PartnersCarousel';
import SignupForm from '@/Components/Welcome/SignupForm';
import StatsSection from '@/Components/Welcome/StatsSection';
import ComparisonSection from '@/Components/Welcome/ComparisonSection';
import FaqSection from '@/Components/Welcome/FaqSection';
import CtaBanner from '@/Components/Welcome/CtaBanner';

/**
 * Section type => component. Any CMS page can use any of these, more than
 * once via a variant suffix ("stats__problem"). Keys without a component
 * ("seo", "nav") hold data only and render nothing.
 */
const SECTION_COMPONENTS = {
    hero: Hero,
    path_cards: PathCards,
    detailed_features: DetailedFeatures,
    features: FeatureSection,
    credibility: Credibility,
    media_showcase: MediaShowcase,
    problem: ProblemSection,
    how_it_works: HowItWorks,
    roi_calculator: ROICalculator,
    pricing: Pricing,
    partners: PartnersCarousel,
    signup: SignupForm,
    stats: StatsSection,
    comparison: ComparisonSection,
    faq: FaqSection,
    cta_banner: CtaBanner,
};

/** "Join" scrolls to this page's sign-up form, or to the path cards on /. */
function joinTarget(sections) {
    const withAnchor = (key) => sections.find((s) => s.section_key === key);
    const signup = withAnchor('signup');
    if (signup) return `#${getContent(signup, 'anchor', 'join')}`;
    const paths = withAnchor('path_cards');
    if (paths) return `#${getContent(paths, 'anchor', 'paths')}`;
    return '/hosts#join';
}

export default function Page({ page, sections = [], site = {}, seo = {} }) {
    // A page-level "nav" section replaces the site-wide header links.
    const navLinks = extractItems(sections.find((s) => s.section_key === 'nav'), 'links', ['label', 'href']);

    return (
        <PublicLayout site={site} page={page} seo={seo} joinHref={joinTarget(sections)} navLinks={navLinks} sections={sections}>
            {sections.map((section) => {
                const Component = SECTION_COMPONENTS[baseSectionKey(section.section_key)];
                if (!Component) return null;
                const anchor = stripHtml(getContent(section, 'anchor', ''));
                return (
                    <div key={section.id} id={anchor || undefined} className="public-section">
                        <Component section={section} />
                    </div>
                );
            })}
        </PublicLayout>
    );
}
