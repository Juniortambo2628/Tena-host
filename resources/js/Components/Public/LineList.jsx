import React from 'react';
import { textLines } from '@/lib/cms';
import { usePublic } from './PublicContext';

export function SoonBadge({ children }) {
    return children ? <span className="soon-badge">{children}</span> : null;
}

/**
 * Renders a multi-line CMS field as a list; "[[feature_key]]" line
 * suffixes become "Coming soon" badges (see textLines in lib/cms).
 */
export default function LineList({ text, className = '', icon = null }) {
    const { site } = usePublic();
    const lines = textLines(text, site);
    if (lines.length === 0) return null;

    return (
        <ul className={className}>
            {lines.map((line, i) => (
                <li key={i}>
                    {icon}
                    <span>{line.text} <SoonBadge>{line.badge}</SoonBadge></span>
                </li>
            ))}
        </ul>
    );
}
