import React, { useState, useMemo } from 'react';
import './IconPicker.css';

const ICON_CATEGORIES = {
    'Connectivity': [
        { class: 'fas fa-wifi', label: 'WiFi' },
        { class: 'fas fa-plug', label: 'Plug' },
        { class: 'fas fa-signal', label: 'Signal' },
        { class: 'fas fa-broadcast-tower', label: 'Broadcast' },
        { class: 'fas fa-network-wired', label: 'Network' },
        { class: 'fas fa-link', label: 'Link' },
    ],
    'Communication': [
        { class: 'fas fa-envelope', label: 'Email' },
        { class: 'fas fa-envelope-open-text', label: 'Open Email' },
        { class: 'fas fa-comments', label: 'Comments' },
        { class: 'fas fa-comment-dots', label: 'Chat' },
        { class: 'fas fa-phone', label: 'Phone' },
        { class: 'fas fa-mobile-alt', label: 'Mobile' },
        { class: 'fas fa-sms', label: 'SMS' },
        { class: 'fas fa-bullhorn', label: 'Megaphone' },
    ],
    'Users & People': [
        { class: 'fas fa-users', label: 'Users' },
        { class: 'fas fa-user-plus', label: 'Add User' },
        { class: 'fas fa-user-shield', label: 'User Shield' },
        { class: 'fas fa-user', label: 'User' },
        { class: 'fas fa-heart', label: 'Heart' },
        { class: 'fas fa-home', label: 'Home' },
    ],
    'Data & Analytics': [
        { class: 'fas fa-chart-line', label: 'Chart' },
        { class: 'fas fa-chart-bar', label: 'Bar Chart' },
        { class: 'fas fa-chart-pie', label: 'Pie Chart' },
        { class: 'fas fa-database', label: 'Database' },
        { class: 'fas fa-file-alt', label: 'Document' },
        { class: 'fas fa-list-alt', label: 'List' },
    ],
    'Actions': [
        { class: 'fas fa-sync', label: 'Sync' },
        { class: 'fas fa-rocket', label: 'Rocket' },
        { class: 'fas fa-check', label: 'Check' },
        { class: 'fas fa-plus', label: 'Plus' },
        { class: 'fas fa-search', label: 'Search' },
        { class: 'fas fa-cog', label: 'Settings' },
    ],
    'Commerce': [
        { class: 'fas fa-shopping-cart', label: 'Cart' },
        { class: 'fas fa-tag', label: 'Tag' },
        { class: 'fas fa-percent', label: 'Percent' },
        { class: 'fas fa-dollar-sign', label: 'Dollar' },
        { class: 'fas fa-credit-card', label: 'Card' },
        { class: 'fas fa-gift', label: 'Gift' },
    ],
    'Security': [
        { class: 'fas fa-shield-alt', label: 'Shield' },
        { class: 'fas fa-lock', label: 'Lock' },
        { class: 'fas fa-key', label: 'Key' },
        { class: 'fas fa-fingerprint', label: 'Fingerprint' },
    ],
    'Objects': [
        { class: 'fas fa-star', label: 'Star' },
        { class: 'fas fa-calendar-check', label: 'Calendar' },
        { class: 'fas fa-clock', label: 'Clock' },
        { class: 'fas fa-map-marker-alt', label: 'Location' },
        { class: 'fas fa-camera', label: 'Camera' },
        { class: 'fas fa-image', label: 'Image' },
    ],
};

export default function IconPicker({ value, onChange, label }) {
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState('');

    const filteredIcons = useMemo(() => {
        if (!search) return ICON_CATEGORIES;
        const q = search.toLowerCase();
        const result = {};
        Object.entries(ICON_CATEGORIES).forEach(([cat, icons]) => {
            const match = icons.filter(i => i.label.toLowerCase().includes(q) || i.class.includes(q));
            if (match.length > 0) result[cat] = match;
        });
        return result;
    }, [search]);

    const renderIconPreview = (iconClass) => {
        if (!iconClass) return <span className="cms-icon-picker__empty">No icon</span>;
        return <i className={`${iconClass} text-lg`}></i>;
    };

    return (
        <div className="cms-icon-picker">
            {label && <label className="cms-icon-picker__label">{label}</label>}
            <div className="cms-icon-picker__trigger" onClick={() => setIsOpen(!isOpen)}>
                <span className="cms-icon-picker__selected">
                    {value ? renderIconPreview(value) : <span className="text-black/30">Choose icon...</span>}
                </span>
                {value && (
                    <span className="cms-icon-picker__class-name">{value}</span>
                )}
                <svg className="cms-icon-picker__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
            </div>

            {isOpen && (
                <div className="cms-icon-picker__dropdown">
                    <div className="cms-icon-picker__search-wrap">
                        <svg className="cms-icon-picker__search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search icons..."
                            className="cms-icon-picker__search"
                            autoFocus
                        />
                    </div>
                    <div className="cms-icon-picker__categories custom-scrollbar">
                        {value && (
                            <button
                                className="cms-icon-picker__clear"
                                onClick={() => { onChange(''); setIsOpen(false); }}
                            >
                                Clear selection
                            </button>
                        )}
                        {Object.entries(filteredIcons).map(([category, icons]) => (
                            <div key={category} className="cms-icon-picker__category">
                                <h5 className="cms-icon-picker__category-title">{category}</h5>
                                <div className="cms-icon-picker__grid">
                                    {icons.map((icon) => (
                                        <button
                                            key={icon.class}
                                            type="button"
                                            className={`cms-icon-picker__icon ${value === icon.class ? 'cms-icon-picker__icon--active' : ''}`}
                                            onClick={() => { onChange(icon.class); setIsOpen(false); setSearch(''); }}
                                            title={icon.label}
                                        >
                                            <i className={icon.class}></i>
                                        </button>
                                    ))}
                                </div>
                            </div>
                        ))}
                        {Object.keys(filteredIcons).length === 0 && (
                            <p className="cms-icon-picker__no-results">No icons match "{search}"</p>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
