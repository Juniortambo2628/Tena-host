import React from 'react';
import { sanitizeHtml } from '@/lib/cms';
import './TwoColumn.css';

export function TwoColumnFeatureRow({ icon, title, desc }) {
    return (
        <div className="two-column-feature-row">
            <div className="two-column-feature-icon-wrap">
                <i className={icon}></i>
            </div>
            <div>
                <h6 className="two-column-feature-title">{title}</h6>
                <p className="two-column-feature-desc" dangerouslySetInnerHTML={{ __html: sanitizeHtml(desc) }} />
            </div>
        </div>
    );
}

export default function TwoColumn({
    children,
    image,
    reverse = false,
    label,
    heading,
    description,
    features,
    cta,
}) {
    const columnClasses = [
        'two-column',
        reverse ? 'two-column--reverse' : '',
    ].join(' ');

    return (
        <div className={columnClasses}>
            <div className="two-column-image-col">
                <div className="two-column-image-wrap">
                    {image}
                </div>
            </div>
            <div className="two-column-text-col">
                <div className="two-column-text-card">
                    {label && <span className="two-column-label">{label}</span>}
                    {heading && <h2 className="two-column-heading">{heading}</h2>}
                    {description && <p className="two-column-desc" dangerouslySetInnerHTML={{ __html: sanitizeHtml(description) }} />}
                    {features && (
                        <div className="two-column-features-list">
                            {features.map((feature, index) => (
                                <TwoColumnFeatureRow key={index} {...feature} />
                            ))}
                        </div>
                    )}
                    {children}
                    {cta && <div className="two-column-cta">{cta}</div>}
                </div>
            </div>
        </div>
    );
}
