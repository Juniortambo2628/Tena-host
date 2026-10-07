import React from 'react';
import { usePublic } from './PublicContext';
import { track } from '@/lib/analytics';

/**
 * A CMS call-to-action. Uses the CMS URL when one is set, otherwise the
 * page's "Join" target, and records join/path events on the way out.
 */
export default function CtaLink({ href, event, className, children }) {
    const { page, joinHref } = usePublic();
    const target = href || joinHref;
    const trackedEvent = event || (target === joinHref ? 'join_click' : null);

    return (
        <a
            href={target}
            className={className}
            onClick={() => trackedEvent && track(trackedEvent, page.slug)}
        >
            {children}
        </a>
    );
}
