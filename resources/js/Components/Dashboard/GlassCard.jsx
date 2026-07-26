import React from 'react';
import './GlassCard.css';

const paddingClasses = {
    'p-8': 'glass-card__content--padding-8',
    'p-6': 'glass-card__content--padding-6',
    'p-0 overflow-hidden': 'glass-card__content--padding-0',
    'p-0': 'glass-card__content--padding-0',
};

export default function GlassCard({
    children,
    className = '',
    bgImage = null,
    gradient = true,
    padding = 'p-8'
}) {
    return (
        <div className={`glass-card group ${className}`}>
            {/* Background Image Layer */}
            {bgImage && (
                <div
                    className="glass-card__bg-image"
                    style={{ backgroundImage: `url('${bgImage}')` }}
                />
            )}

            {/* Subtle Gradient Overlay */}
            {gradient && <div className="glass-card__gradient" />}

            {/* Content Container */}
            <div className={`glass-card__content ${paddingClasses[padding] || paddingClasses['p-8']}`}>
                {children}
            </div>
        </div>
    );
}
