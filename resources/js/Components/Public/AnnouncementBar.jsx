import React from 'react';
import { usePage } from '@inertiajs/react';
import { getText, isTruthy } from '@/lib/cms';
import { usePublic } from './PublicContext';
import './AnnouncementBar.css';

const fill = (text, trial) => text.replace(/\{(taken|total|left)\}/g, (_, key) => String(trial[key]));

/**
 * The thin bar above the header (Site-wide → Announcement bar). Shown on the
 * pages listed in "pages" (all when empty). {taken}/{total}/{left} come
 * from the free trial slot counter; without it, "text_without_slots" is used.
 */
export default function AnnouncementBar() {
    const { site, page } = usePublic();
    const { trial } = usePage().props;
    const bar = site.announcement;
    if (!bar || !isTruthy(getText(bar, 'enabled', '0'))) return null;

    const pages = getText(bar, 'pages').split(',').map(p => p.trim()).filter(Boolean);
    if (pages.length && !pages.includes(page?.slug)) return null;

    const usesSlots = /\{(taken|total|left)\}/.test(getText(bar, 'text'));
    const text = usesSlots && !trial?.total ? getText(bar, 'text_without_slots') : fill(getText(bar, 'text'), trial || {});
    const label = getText(bar, 'link_label');
    if (!text) return null;

    return (
        <div className="announcement-bar" role="region" aria-label="Announcement">
            <p>{text}</p>
            {label && <a href={getText(bar, 'link_href', '#')}>{label} →</a>}
        </div>
    );
}
