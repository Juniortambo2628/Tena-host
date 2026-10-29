// The five guest splash templates, in the order hosts see them.
export const splashTemplates = [
    { key: 'bento', name: 'Bento', description: 'Photo hero with a frosted tray of big, rounded cards.', best: 'Homes & apartments' },
    { key: 'horizon', name: 'Horizon', description: 'A crisp white panel floating over your best landscape shot.', best: 'Lodges & villas' },
    { key: 'clover', name: 'Clover', description: 'Editorial split with a coloured headline and a story strip.', best: 'Boutique hotels' },
    { key: 'monogram', name: 'Monogram', description: 'Monochrome stationery with a giant initial. Quietly luxurious.', best: 'Luxury & serviced' },
    { key: 'nocturne', name: 'Nocturne', description: 'After-dark glass and glow for lounges and city stays.', best: 'Bars, lounges & city' },
];

const legacy = { classic: 'bento', retreat: 'horizon', bold: 'nocturne' };

export const accentPresets = ['#FFD300', '#E8A0B4', '#5B6CFF', '#1F8A70', '#E4572E', '#C9A227', '#111111', '#7B61FF'];

export const defaultSplashDesign = { template: 'bento', accent: '#FFD300', heading: '', message: '', button: '' };

export function normalizeTemplate(key) {
    return legacy[key] || (splashTemplates.some(t => t.key === key) ? key : 'bento');
}
