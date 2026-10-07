import { Head, Link } from '@inertiajs/react';
import React, { useState } from 'react';
import { ChevronRight, Mail, MapPin, MessageCircle } from 'lucide-react';
import { getContent, getMedia, extractItems, stripHtml } from '@/lib/cms';
import { track } from '@/lib/analytics';
import { PublicContext } from '@/Components/Public/PublicContext';
import CookiesConsent from '@/Components/CookiesConsent';
import CookieDetailsModal from '@/Components/CookieDetailsModal';
import './PublicLayout.css';

const DEFAULT_LOGO = '/legacy/assets/Tena-logo-square.jpg';

/**
 * Shell for every public page (/, /hosts, /business, /privacy, /terms).
 * Header, footer and SEO come from the CMS (Site-wide page) so all pages
 * stay in sync from one place.
 */
export default function PublicLayout({ site = {}, page, seo = {}, joinHref = '#paths', hasSections = true, navLinks: pageNavLinks = [], sections = [], children }) {
    const [showCookieDetails, setShowCookieDetails] = useState(false);

    const header = site.header;
    const footer = site.footer;
    const logo = getMedia(header, 'logo', DEFAULT_LOGO);
    const siteName = seo.site_name || 'TenaFi';

    // In-page anchors (#how-it-works) only exist on section pages; elsewhere
    // send them to the homepage version of that anchor.
    const resolveHref = (href) => (href?.startsWith('#') && !hasSections ? `/${href}` : href);
    const navLinks = pageNavLinks.length > 0 ? pageNavLinks : extractItems(header, 'links', ['label', 'href']);
    const footerLinks = extractItems(footer, 'links', ['label', 'href']);
    const resolvedJoin = resolveHref(joinHref);

    const loginUrl = getContent(header, 'login_url', '/login');
    const contactEmail = stripHtml(getContent(footer, 'contact_email', ''));
    const whatsapp = stripHtml(getContent(footer, 'whatsapp', ''));

    return (
        <PublicContext.Provider value={{ site, page, sections, joinHref: resolvedJoin }}>
            <div className="welcome-page">
                <Head title={seo.title || siteName} />

                <nav className="welcome-nav">
                    <div className="welcome-nav-container">
                        <div className="welcome-nav-inner">
                            <div className="welcome-nav-logo">
                                <Link href="/">
                                    <img src={logo} alt={`${siteName} logo`} />
                                </Link>
                            </div>
                            <div className="welcome-nav-links">
                                {navLinks.map((link) => (
                                    <a key={link.href} href={resolveHref(link.href)} className="welcome-nav-link">
                                        {stripHtml(link.label)}
                                    </a>
                                ))}
                            </div>
                            <div className="welcome-nav-actions">
                                <a href={loginUrl} className="welcome-login-btn">
                                    {stripHtml(getContent(header, 'login_label', 'Login'))}
                                </a>
                                <a
                                    href={resolvedJoin}
                                    className="welcome-join-btn"
                                    onClick={() => track('join_click', page?.slug)}
                                >
                                    {stripHtml(getContent(header, 'join_label', 'Join'))}
                                </a>
                            </div>
                        </div>
                    </div>
                </nav>

                <main>{children}</main>

                <footer className="welcome-footer">
                    <div className="welcome-footer-container">
                        <div className="welcome-footer-grid">
                            <div className="welcome-footer-brand">
                                <Link href="/" className="welcome-footer-brand-link">
                                    <img src={logo} alt={`${siteName} logo`} />
                                </Link>
                                <p className="welcome-footer-brand-desc">
                                    {stripHtml(getContent(footer, 'description', "Africa's guest relationship platform."))}
                                </p>
                            </div>
                            <div>
                                <h5 className="welcome-footer-heading-gold">Quick Links</h5>
                                <ul className="welcome-footer-links">
                                    {footerLinks.map((link) => (
                                        <li key={link.href}>
                                            <a href={resolveHref(link.href)} className="welcome-footer-link">
                                                <ChevronRight size={12} className="mr-2 inline text-[#FFD300]" />
                                                {stripHtml(link.label)}
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                            <div>
                                <h5 className="welcome-footer-heading">Talk to us</h5>
                                <ul className="welcome-footer-links">
                                    {contactEmail && (
                                        <li className="welcome-footer-contact">
                                            <Mail size={16} className="inline" /> <a href={`mailto:${contactEmail}`}>{contactEmail}</a>
                                        </li>
                                    )}
                                    {whatsapp && (
                                        <li className="welcome-footer-contact">
                                            <MessageCircle size={16} className="inline" />{' '}
                                            <a href={`https://wa.me/${whatsapp.replace(/\D/g, '')}`} target="_blank" rel="noopener">WhatsApp {whatsapp}</a>
                                        </li>
                                    )}
                                    <li className="welcome-footer-contact">
                                        <MapPin size={16} className="inline" /> {stripHtml(getContent(footer, 'location', 'Nairobi, Kenya'))}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div className="welcome-footer-bottom">
                            <p>
                                &copy; {new Date().getFullYear()} {stripHtml(getContent(footer, 'copyright', `${siteName}. All rights reserved.`))}{' '}
                                {stripHtml(getContent(footer, 'tagline', ''))}
                            </p>
                            <div className="welcome-footer-bottom-links">
                                <a href="/privacy" className="welcome-footer-bottom-link">Privacy</a>
                                <a href="/terms" className="welcome-footer-bottom-link">Terms</a>
                                <button onClick={() => setShowCookieDetails(true)} className="welcome-footer-bottom-link">Cookies</button>
                            </div>
                        </div>
                    </div>
                </footer>

                <CookieDetailsModal isOpen={showCookieDetails} onClose={() => setShowCookieDetails(false)} />
                <CookiesConsent
                    onOpenPrivacy={() => { window.location.href = '/privacy'; }}
                    onOpenCookieDetails={() => setShowCookieDetails(true)}
                />
            </div>
        </PublicContext.Provider>
    );
}
