import { createContext, useContext } from 'react';

/**
 * Shared state for every public page: the site-wide CMS sections (header,
 * footer, plans, feature status), the current page slug, and where "Join"
 * points on this page (its sign-up anchor, or the path cards on /).
 */
export const PublicContext = createContext({
    site: {},
    page: { slug: 'home' },
    joinHref: '#paths',
});

export const usePublic = () => useContext(PublicContext);
