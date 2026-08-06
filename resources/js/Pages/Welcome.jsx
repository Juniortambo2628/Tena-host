import { Head, Link } from '@inertiajs/react';
import React, { useState } from 'react';
import { getSection } from '@/lib/cms';
import Hero from '@/Components/Welcome/Hero';
import FeatureSection from '@/Components/Welcome/FeatureSection';
import DetailedFeatures from '@/Components/Welcome/DetailedFeatures';
import ProblemSection from '@/Components/Welcome/ProblemSection';
import HowItWorks from '@/Components/Welcome/HowItWorks';
import ROICalculator from '@/Components/Welcome/ROICalculator';
import Pricing from '@/Components/Welcome/Pricing';
import MediaShowcase from '@/Components/Welcome/MediaShowcase';
import WaitlistModal from '@/Components/Welcome/WaitlistModal';
import CookiesConsent from '@/Components/CookiesConsent';
import TermsModal from '@/Components/TermsModal';
import PrivacyPolicyModal from '@/Components/PrivacyPolicyModal';
import CookieDetailsModal from '@/Components/CookieDetailsModal';
import { ChevronRight, Mail, MapPin } from 'lucide-react';
import './Welcome.css';

const SECTION_COMPONENTS = {
    hero: Hero,
    detailed_features: DetailedFeatures,
    features: FeatureSection,
    media_showcase: MediaShowcase,
    problem: ProblemSection,
    how_it_works: HowItWorks,
    roi_calculator: ROICalculator,
    pricing: Pricing,
};

export default function Welcome({ auth, laravelVersion, phpVersion, landingContent = [] }) {
    const [showWaitlist, setShowWaitlist] = useState(false);
    const [showTerms, setShowTerms] = useState(false);
    const [showPrivacy, setShowPrivacy] = useState(false);
    const [showCookieDetails, setShowCookieDetails] = useState(false);

    const toggleWaitlist = () => setShowWaitlist(true);
    const closeWaitlist = () => setShowWaitlist(false);

    const toggleTerms = (e) => {
        if (e) e.preventDefault();
        setShowTerms(true);
    };
    const closeTerms = () => setShowTerms(false);

    const togglePrivacy = () => setShowPrivacy(true);
    const closePrivacy = () => setShowPrivacy(false);

    const toggleCookieDetails = () => setShowCookieDetails(true);
    const closeCookieDetails = () => setShowCookieDetails(false);

    return (
        <div className="welcome-page">
            <nav className="welcome-nav">
                <div className="welcome-nav-container">
                    <div className="welcome-nav-inner">
                        <div className="welcome-nav-logo">
                            <Link href="/">
                                <img src="/legacy/assets/Tena-logo-square.jpg" alt="TENA Logo" />
                            </Link>
                        </div>
                        <div className="welcome-nav-links">
                            <a href="#how-it-works" className="welcome-nav-link">How it works</a>
                            <a href="#pricing" className="welcome-nav-link">Pricing</a>
                            <a href="#roi-calculator" className="welcome-nav-link">ROI Calculator</a>
                        </div>
                        <div className="welcome-nav-actions">
                            <Link href={route('login')} className="welcome-login-btn">Login</Link>
                            <button onClick={toggleWaitlist} className="welcome-join-btn">Join</button>
                        </div>
                    </div>
                </div>
            </nav>

            <main>
                {landingContent.map((section) => {
                    const Component = SECTION_COMPONENTS[section.section_key];
                    if (!Component) return null;
                    return (
                        <Component
                            key={section.id}
                            section={section}
                            onOpenWaitlist={toggleWaitlist}
                        />
                    );
                })}
            </main>

            <footer className="welcome-footer">
                <div className="welcome-footer-container">
                    <div className="welcome-footer-grid">
                        <div className="welcome-footer-brand">
                            <Link href="/" className="welcome-footer-brand-link">
                                <img src="/legacy/assets/Tena-logo-square.jpg" alt="TENA Logo" />
                            </Link>
                            <p className="welcome-footer-brand-desc">
                                We help Superhosts maximize guest data collection and direct bookings through intelligent WiFi automation and marketing.
                            </p>
                        </div>
                        <div>
                            <h5 className="welcome-footer-heading-gold">Quick Links</h5>
                            <ul className="welcome-footer-links">
                                <li><a href="/" className="welcome-footer-link"><ChevronRight size={12} className="mr-2 inline text-[#FFD300]"></ChevronRight>Home</a></li>
                                <li><a href="#pricing" className="welcome-footer-link"><ChevronRight size={12} className="mr-2 inline text-[#FFD300]"></ChevronRight>Pricing</a></li>
                                <li><Link href={route('register')} className="welcome-footer-link"><ChevronRight size={12} className="mr-2 inline text-[#FFD300]"></ChevronRight>Register</Link></li>
                                <li><button onClick={toggleWaitlist} className="welcome-footer-link"><ChevronRight size={12} className="mr-2 inline text-[#FFD300]"></ChevronRight>Join Waitlist</button></li>
                                <li><button onClick={togglePrivacy} className="welcome-footer-link"><ChevronRight size={12} className="mr-2 inline text-[#FFD300]"></ChevronRight>Privacy Policy</button></li>
                                <li><button onClick={toggleTerms} className="welcome-footer-link"><ChevronRight size={12} className="mr-2 inline text-[#FFD300]"></ChevronRight>Terms & Conditions</button></li>
                            </ul>
                        </div>
                        <div>
                            <h5 className="welcome-footer-heading">Platform</h5>
                            <ul className="welcome-footer-links">
                                <li><a href="#how-it-works" className="welcome-footer-link-simple">Features</a></li>
                                <li><a href="#roi-calculator" className="welcome-footer-link-simple">ROI Calculator</a></li>
                                <li><Link href={route('login')} className="welcome-footer-link-simple">Admin Login</Link></li>
                            </ul>
                        </div>
                        <div>
                            <h5 className="welcome-footer-heading">Contact</h5>
                            <ul className="welcome-footer-links">
                                <li className="welcome-footer-contact"><Mail size={16} className="inline"></Mail> info@tena.host</li>
                                <li className="welcome-footer-contact"><MapPin size={16} className="inline"></MapPin> Nairobi, Kenya</li>
                            </ul>
                        </div>
                    </div>
                    <div className="welcome-footer-bottom">
                        <p>&copy; <span className="welcome-footer-brand-name">Tena</span>. All Rights Reserved. Built by Superhosts for Superhosts.</p>
                        <div className="welcome-footer-bottom-links">
                            <a href="/" className="welcome-footer-bottom-link">Home</a>
                            <button onClick={toggleCookieDetails} className="welcome-footer-bottom-link">Cookies</button>
                            <Link href={route('login')} className="welcome-footer-bottom-link">Login</Link>
                            <a href="#" className="welcome-footer-bottom-link">Help</a>
                        </div>
                    </div>
                </div>
            </footer>

            {/* Modals & Overlays */}
            <WaitlistModal show={showWaitlist} onClose={closeWaitlist} />
            <TermsModal isOpen={showTerms} onClose={closeTerms} />
            <PrivacyPolicyModal isOpen={showPrivacy} onClose={closePrivacy} />
            <CookieDetailsModal isOpen={showCookieDetails} onClose={closeCookieDetails} />
            <CookiesConsent
                onOpenPrivacy={togglePrivacy}
                onOpenCookieDetails={toggleCookieDetails}
            />
        </div>
    );
}
