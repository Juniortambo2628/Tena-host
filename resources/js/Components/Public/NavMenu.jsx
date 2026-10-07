import React from 'react';
import { usePage } from '@inertiajs/react';
import { ArrowRight, ChevronDown } from 'lucide-react';
import { stripHtml } from '@/lib/cms';

/**
 * The same header links on every public page. A link to an audience page
 * opens a megamenu of that page's sections (built server-side from each
 * section's "menu_label", see LandingPage::navMenus) on hover or focus.
 */
export default function NavMenu({ links, resolveHref }) {
    const { menus = {} } = usePage().props;
    const current = typeof window !== 'undefined' ? window.location.pathname : '';

    return (
        <ul className="welcome-nav-links">
            {links.map((link) => {
                const items = menus[link.href] || [];
                const label = stripHtml(link.label);
                const isCurrent = link.href === current;

                return (
                    <li key={link.href} className={`nav-item ${items.length ? 'nav-item--menu' : ''}`}>
                        <a href={resolveHref(link.href)} className="welcome-nav-link" aria-current={isCurrent ? 'page' : undefined} aria-haspopup={items.length ? 'true' : undefined}>
                            {label}
                            {items.length > 0 && <ChevronDown size={14} className="nav-item-chevron" aria-hidden="true" />}
                        </a>
                        {items.length > 0 && (
                            <div className="nav-mega" role="menu" aria-label={label}>
                                <div className="nav-mega-panel">
                                    <a href={link.href} className="nav-mega-overview" role="menuitem">
                                        {label} <ArrowRight size={14} />
                                    </a>
                                    <div className="nav-mega-grid">
                                        {items.map((item) => (
                                            <a key={item.href} href={item.href} className="nav-mega-item" role="menuitem">
                                                <span className="nav-mega-label">{item.label}</span>
                                                {item.description && <span className="nav-mega-desc">{item.description}</span>}
                                            </a>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}
                    </li>
                );
            })}
        </ul>
    );
}
