/**
 * Public-page funnel tracking: pushes to GA4/GTM's dataLayer when present
 * and records a first-party daily counter via POST /api/track.
 * Allowed events are enforced server-side (TrackController).
 */
export function track(event, page) {
    if (typeof window === 'undefined') return;

    window.dataLayer?.push({ event: `tenafi_${event}`, page });

    window.axios?.post('/api/track', { event, page }).catch(() => {});
}
