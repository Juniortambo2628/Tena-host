import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { getContent } from '@/lib/cms';
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

/**
 * section_key => component. Any CMS page can use any of these sections;
 * keys without a component (e.g. "seo") hold data only and render nothing.
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
    return (
        <PublicLayout site={site} page={page} seo={seo} joinHref={joinTarget(sections)}>
            {sections.map((section) => {
                const Component = SECTION_COMPONENTS[section.section_key];
                return Component ? <Component key={section.id} section={section} /> : null;
            })}
        </PublicLayout>
    );
}
