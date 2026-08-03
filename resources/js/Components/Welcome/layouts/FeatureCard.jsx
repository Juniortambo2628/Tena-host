import React from 'react';
import { sanitizeHtml } from '@/lib/cms';
import './FeatureCard.css';

export default function FeatureCard({
    icon,
    title,
    description,
    image,
    imageAlt,
    variant = 'default',
    step,
}) {
    const cardClasses = [
        'feature-card',
        variant === 'flat' ? 'feature-card--flat' : '',
        variant === 'outlined' ? 'feature-card--outlined' : '',
    ].join(' ');

    return (
        <div className={cardClasses}>
            {image && (
                <div className="feature-card-image-wrap">
                    <img src={image} alt={imageAlt || title} className="feature-card-image" />
                    {icon && (
                        <div className="feature-card-image-overlay">
                            <i className={`${icon} text-[#FFD300] text-3xl`}></i>
                        </div>
                    )}
                </div>
            )}
            <div className="feature-card-content">
                {step && <span className="feature-card-step">Step {step}</span>}
                {!image && icon && (
                    <div className="feature-card-icon-wrap">
                        <i className={icon}></i>
                    </div>
                )}
                <h4 className="feature-card-title">{title}</h4>
                <p className="feature-card-desc" dangerouslySetInnerHTML={{ __html: sanitizeHtml(description) }} />
            </div>
        </div>
    );
}
