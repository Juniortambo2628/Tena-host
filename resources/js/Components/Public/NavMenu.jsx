import React, { useState } from 'react';
import { usePage } from '@inertiajs/react';
import { ArrowRight, ChevronDown } from 'lucide-react';
import { titleCase } from '@/lib/cms';

/**
 * The same header links on every public page. A link to an audience page
 * has a megamenu of that page's sections (built server-side from each
 * section's "menu_label", see LandingPage::navMenus).
 *
 * One component for every screen size: on desktop the menu opens on hover
 * or keyboard focus; on mobile (and touch) the chevron button expands it
 * inline inside the header drawer. `open` shows the drawer on mobile.
 */
export default function NavMenu({ links, resolveHref, open = false, onNavigate }) {
    const { menus = {} } = usePage().props;
    const [expanded, setExpanded] = useState(null);
    const current = typeof window !== 'undefined' ? window.location.pathname : '';

    return (
        <ul id="site-nav" className={`welcome-nav-links ${open ? 'is-open' : ''}`}>
            {links.map((link) => {
                const items = menus[link.href] || [];
                const label = titleCase(link.label);
                const isExpanded = expanded === link.href;
                const menuId = `nav-menu-${link.href.replace(/\W+/g, '')}`;

                return (
                    <li key={link.href} className={`nav-item ${items.length ? 'nav-item--menu' : ''} ${isExpanded ? 'is-expanded' : ''}`}>
                        <div className="nav-item-row">
                            <a href={resolveHref(link.href)} className="welcome-nav-link" aria-current={link.href === current ? 'page' : undefined} onClick={onNavigate}>
                                {label}
                            </a>
                            {items.length > 0 && (
                                <button
                                    type="button"
                                    className="nav-item-toggle"
                                    aria-expanded={isExpanded}
                                    aria-controls={menuId}
                                    aria-label={`${label} sections`}
                                    onClick={() => setExpanded(isExpanded ? null : link.href)}
                                >
                                    <ChevronDown size={16} className="nav-item-chevron" aria-hidden="true" />
                                </button>
                            )}
                        </div>
                        {items.length > 0 && (
                            <div id={menuId} className="nav-mega">
                                <div className="nav-mega-panel">
                                    <a href={link.href} className="nav-mega-overview" onClick={onNavigate}>
                                        {label} <ArrowRight size={14} />
                                    </a>
                                    <div className="nav-mega-grid">
                                        {items.map((item) => (
                                            <a key={item.href} href={item.href} className="nav-mega-item" onClick={onNavigate}>
                                                <span className="nav-mega-label">{titleCase(item.label)}</span>
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
