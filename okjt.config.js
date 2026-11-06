/**
 * TenaFi's settings for the OKJT kit (@okjt/site), used by the v2 public
 * pages (/v2…). The colours follow TenaFi's brand (resources/css/theme.css):
 * near-black ink, warm off-white surfaces, and the brand yellow on dark
 * backgrounds (accentSoft). On light backgrounds the kit sets small labels in
 * `accent`, so that's a deep gold that stays readable rather than the yellow.
 * `muted` is TenaFi's own muted grey, so no existing page changes.
 *
 * After changing it, regenerate resources/css/okjt-theme.css:
 *
 *   npm run theme
 *
 * Only the display font is set here: the kit's body font is scoped to the v2
 * pages (resources/js/Components/V2/V2Site.css) so the dashboard keeps its own.
 */
export default {
    theme: {
        colors: {
            ink: '#1B1B1B',
            inkDeep: '#0E0E0E',
            body: '#3E4250',
            soft: '#5B6170',
            muted: '#5B6170',
            faint: '#8A8F9C',
            accent: '#8A6A00',
            accentStrong: '#735800',
            accentDark: '#5C4700',
            accentSoft: '#FFD300',
            line: '#E7E4DB',
            lineStrong: '#D5D1C5',
            tint: '#F6F4EE',
            paper: '#FDFCF7',
            paperWarm: '#FAF8F1',
            positive: '#0F6B4E',
            positiveDark: '#0B5039',
            tone: 'stone',
        },
        fonts: {
            display: "'Inter', ui-sans-serif, system-ui, sans-serif",
        },
    },
};
