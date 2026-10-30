import { BRAND_COLORS } from '@/constants';
import catalog from '../../../data/splash-templates.json';

// Templates, groups and business types are shared with App\Support\SplashDesign
// through resources/data/splash-templates.json.
export const templateGroups = catalog.groups;
export const businessTypes = catalog.businessTypes;

// Every guest splash template, grouped by the kind of place it suits.
export const splashTemplates = catalog.templates.map(t => ({
    key: t.id, name: t.name, description: t.description, best: t.for, group: t.group, accent: t.accent, defaults: t.defaults,
}));

// The first five; their saved designs predate per-template defaults.
export const originalTemplates = ['bento', 'horizon', 'clover', 'monogram', 'nocturne'];

const legacy = { classic: 'bento', retreat: 'horizon', bold: 'nocturne' };

export const accentPresets = [BRAND_COLORS.primary, '#E8A0B4', '#5B6CFF', '#1F8A70', '#E4572E', '#C9A227', '#111111', '#7B61FF'];

export const defaultSplashDesign = { template: 'bento', accent: BRAND_COLORS.primary, heading: '', message: '', button: '' };

export const tileIcons = ['menu', 'list', 'cal', 'gift', 'tag', 'star', 'cart', 'clock', 'guide', 'home', 'wifi'];

// Clinics ask for a first name and phone number only.
export const nameAndPhoneOnly = ['calm'];

export function normalizeTemplate(key) {
    return legacy[key] || (splashTemplates.some(t => t.key === key) ? key : 'bento');
}

export const findTemplate = key => splashTemplates.find(t => t.key === normalizeTemplate(key));

export const businessType = id => businessTypes.find(b => b.id === id) || null;

export const recommendedTemplate = id => businessType(id)?.template || null;

/** A local business rather than a rental, judged by its type (or the account, when no type is set). */
export const isBusinessType = (id, accountIsBusiness = false) => (businessType(id) ? businessType(id).group !== 'stays' : accountIsBusiness);

/** The template's starting content, in branding_json's shape. */
export function templateDefaults(key, business) {
    const d = findTemplate(key)?.defaults;
    if (!d) return {};
    return {
        heading: d.headline,
        opt_in: d.optIn,
        fields: { ...d.fields },
        ...(business ? { tiles: d.tiles.map(t => ({ ...t, url: '' })) } : {}),
    };
}

const sameTiles = (a = [], b = []) => a.length === b.length && a.every((t, i) => t.icon === b[i].icon && t.label === b[i].label);

/**
 * Switch template, swapping in the new template's starting content wherever the
 * owner hasn't changed the old one's, and its accent unless they picked a colour.
 */
export function applyTemplate(design, key, { business = false, keepAccent = false } = {}) {
    const from = templateDefaults(design.template, business);
    const to = templateDefaults(key, business);
    const next = { ...design, template: key };
    for (const [field, value] of Object.entries(to)) {
        const current = design[field];
        const untouched = current === undefined || current === '' || current === null
            || (field === 'tiles' ? sameTiles(current, from.tiles) : JSON.stringify(current) === JSON.stringify(from[field]));
        if (untouched) next[field] = field === 'tiles' ? value.map((t, i) => ({ ...t, url: current?.[i]?.url || '' })) : value;
    }
    if (!keepAccent) next.accent = findTemplate(key)?.accent || next.accent;
    if (nameAndPhoneOnly.includes(key)) next.fields = { email: false, birthday: false };
    return next;
}
