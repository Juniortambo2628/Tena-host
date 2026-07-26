import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import './AuthenticatedLayout.css';

export default function Authenticated({ header, children }) {
    const user = usePage().props.auth.user;
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);

    return (
        <div className="authenticated-layout">
            {/* Ambient Background */}
            <div className="ambient-bg">
                <div className="ambient-red" />
                <div className="ambient-yellow" />
            </div>

            {/* Sticky Navigation */}
            <nav className="main-nav">
                <div className="nav-container">
                    <div className="nav-inner">
                        <div className="nav-left">
                            <Link href="/" className="nav-logo">
                                TENA<span className="nav-logo-dot">.</span>
                            </Link>

                            <div className="nav-links-desktop">
                                <Link
                                    href={route('dashboard')}
                                    className={`nav-link ${route().current('dashboard') ? 'nav-link-active' : 'nav-link-inactive'}`}
                                >
                                    Dashboard
                                </Link>
                                <Link href="#" className="nav-link nav-link-inactive">Analytics</Link>
                                <Link href="#" className="nav-link nav-link-inactive">Users</Link>
                            </div>
                        </div>

                        <div className="nav-right-desktop">
                            <div className="user-pill">
                                <div className="user-avatar">
                                    {user?.name?.charAt(0) || user?.first_name?.charAt(0) || 'U'}
                                </div>
                                <span className="user-name">{user?.name || user?.first_name || 'User'}</span>
                                <Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    className="logout-btn"
                                >
                                    <svg className="logout-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </Link>
                            </div>
                        </div>

                        {/* Mobile menu button */}
                        <div className="mobile-menu-toggle">
                            <button
                                onClick={() => setShowingNavigationDropdown(!showingNavigationDropdown)}
                                className="mobile-menu-btn"
                            >
                                <svg className="mobile-menu-icon" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path className={!showingNavigationDropdown ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path className={showingNavigationDropdown ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {/* Mobile Navigation */}
                <AnimatePresence>
                    {showingNavigationDropdown && (
                        <motion.div
                            initial={{ opacity: 0, height: 0 }}
                            animate={{ opacity: 1, height: 'auto' }}
                            exit={{ opacity: 0, height: 0 }}
                            className="mobile-nav-container"
                        >
                            <div className="mobile-nav-links">
                                <Link href={route('dashboard')} className="mobile-nav-link mobile-link-dashboard">Dashboard</Link>
                                <Link href={route('profile.edit')} className="mobile-nav-link mobile-link-profile">Profile</Link>
                                <Link href={route('logout')} method="post" as="button" className="mobile-nav-link mobile-link-logout">Log Out</Link>
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>
            </nav>

            {/* Page Header */}
            {header && (
                <header className="page-header">
                    <div className="page-header-container">
                        {header}
                    </div>
                </header>
            )}

            {/* Page Content */}
            <main className="page-content">
                {children}
            </main>

        </div>
    );
}
