import React from 'react';
import './SectionHeader.css';

export default function SectionHeader({
    badge,
    title,
    titleHighlight,
    subtitle,
    align = 'center',
    dark = false,
}) {
    const headerClasses = [
        'section-header',
        align === 'center' ? 'section-header--center' : 'section-header--left',
    ].join(' ');

    const titleClasses = [
        'section-header-title',
        dark ? 'section-header-title--dark' : '',
    ].join(' ');

    const subtitleClasses = [
        'section-header-subtitle',
        dark ? 'section-header-subtitle--dark' : '',
    ].join(' ');

    const renderTitle = () => {
        if (title) return title;
        if (titleHighlight) {
            return (
                <>
                    Why Hosts Choose <span className="section-header-highlight">{titleHighlight}</span>
                </>
            );
        }
        return null;
    };

    return (
        <div className={headerClasses}>
            {badge && <span className="section-header-badge">{badge}</span>}
            {(title || titleHighlight) && (
                <h2 className={titleClasses}>{renderTitle()}</h2>
            )}
            {subtitle && <p className={subtitleClasses}>{subtitle}</p>}
        </div>
    );
}
