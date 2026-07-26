import React from 'react';
import './SectionWrapper.css';

const bgMap = {
    white: 'section-wrapper--white',
    gray: 'section-wrapper--gray',
    dark: 'section-wrapper--dark',
};

const paddingMap = {
    sm: 'section-wrapper--sm',
    md: '',
    lg: 'section-wrapper--lg',
};

const widthMap = {
    default: 'section-container',
    narrow: 'section-container--narrow',
    wide: 'section-container--wide',
};

export default function SectionWrapper({
    children,
    bg = 'white',
    padding = 'md',
    width = 'default',
    id,
    className = '',
}) {
    const sectionClasses = [
        'section-wrapper',
        bgMap[bg],
        paddingMap[padding],
        className,
    ].filter(Boolean).join(' ');

    return (
        <section id={id} className={sectionClasses}>
            <div className={widthMap[width]}>
                {children}
            </div>
        </section>
    );
}
