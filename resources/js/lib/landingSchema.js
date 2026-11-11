/** Drawn pictures (WiFi pages, chats, reports) can be swapped for a real screenshot or photo. */
const SHOT_HINT = 'Upload a screenshot or photo to show it instead of the drawing. Delete it to bring the drawing back. Real screenshots: use made-up or consenting guests’ names.';

/**
 * Landing section media schema.
 *
 * Each section_key lists the media slots the corresponding React component
 * actually reads via getMedia(). The CMS uses this to:
 *   - offer only meaningful media keys in the "Add media" dropdown
 *   - keep the slot visible after a file is deleted (so admins can re-upload
 *     without having to remember the key spelling)
 *   - describe each slot in the UI
 *
 * Keep this in sync with each Welcome/*.jsx component's getMedia() calls.
 * `dynamic` returns extra per-item slots derived from the section's own
 * content_keys (e.g. one partner_{i}_logo per partner row).
 */
export const SECTION_MEDIA_SCHEMA = {
    hero: {
        slots: [
            { key: 'main_image', label: 'Main hero image', description: 'Primary hero visual on the right of the headline.' },
            { key: 'visual_shot', label: 'Screenshot instead of the drawn WiFi page', description: SHOT_HINT },
            { key: 'phone_image', label: 'Hero photo for phones', description: 'Shown instead of the main photo on phones held upright. Tall, about 900 × 1600. Empty: phones show the main photo.', v2: true },
            { key: 'card_image', label: 'Card picture', description: 'v2 audience pages: the photo on the link cards to this page from the other pages. 4:3, about 1600 × 1200. Empty: the homepage card photo.', v2: true },
        ],
        dynamic: (contentKeys) => featureSlots(contentKeys, 'features', 'feature_{i}_image', 'Feature {n} image'),
    },

    features: {
        slots: [],
        dynamic: (contentKeys) => featureSlots(contentKeys, 'items', 'item_{i}_image', 'Item {n} image'),
    },

    credibility: {
        slots: [
            { key: 'main_image', label: 'Property image', description: 'Tall photo of the Stay Awhile property shown on the right.' },
            { key: 'stay_awhile_logo', label: 'Stay Awhile logo', description: 'Small logo shown next to the "The experience behind Tena" badge.' },
        ],
    },

    how_it_works: {
        slots: [
            { key: 'chapter_shot', label: 'Screen beside this chapter', description: 'On the v2 audience pages, this section is a chapter of the journey with a screen beside it. A tall phone screenshot shows in a phone frame. Empty: a bundled screen.', v2: true },
        ],
        dynamic: (contentKeys) => featureSlots(contentKeys, 'steps', 'step_{i}_image', 'Step {n} image'),
    },

    problem: {
        slots: [
            { key: 'image_0', label: 'Problem image 1' },
            { key: 'image_1', label: 'Problem image 2' },
            { key: 'image_2', label: 'Problem image 3' },
        ],
    },

    detailed_features: {
        slots: [],
        dynamic: (contentKeys) => [
            ...featureSlots(contentKeys, 'sections', 'section_{i}_image', 'Section {n} image'),
            ...shotSlots(contentKeys, 'sections', 'section_{i}_shot', 'Section {n}: screenshot instead of the drawing'),
        ],
    },

    media_showcase: {
        slots: [
            { key: 'showcase_media', label: 'Showcase media', description: 'The featured image or video for the showcase.' },
        ],
    },

    pricing: {
        slots: [],
    },

    roi_calculator: {
        slots: [],
    },

    partners: {
        slots: [],
        // The slot keeps its old key so existing uploads stay attached to their rows.
        // Two per client: the WiFi page (splash) design shown in the phone, and their logo beside the name.
        dynamic: (contentKeys) => featureSlots(contentKeys, 'partners', 'partner_{i}_logo', 'Partner {n}: WiFi page design')
            .map(slot => ({ ...slot, description: 'Their TenaFi WiFi (splash) page, tall like a phone screen (about 600 × 1200, status bar cropped off). It shows in the phone on the right.' }))
            .flatMap(slot => [slot, {
                key: slot.key.replace(/_logo$/, '_mark'),
                label: slot.label.replace('WiFi page design', 'logo'),
                description: 'Their logo, shown on a white tile beside their name. Square or wide both work; a transparent background looks best.',
            }]),
    },

    path_cards: {
        slots: [],
        dynamic: (contentKeys) => [
            ...featureSlots(contentKeys, 'cards', 'card_{i}_image', 'Card {n} image'),
            ...shotSlots(contentKeys, 'cards', 'card_{i}_shot', 'Card {n}: screenshot instead of the drawing'),
        ],
    },

    seo: {
        slots: [
            { key: 'og_image', label: 'Social share image', description: 'Shown when the page is shared on WhatsApp, LinkedIn, X, etc. 1200×630 recommended. Leave empty on a page to use the site-wide image.' },
        ],
    },

    header: {
        slots: [
            { key: 'logo', label: 'Site logo', description: 'TenaFi logo used in the header and footer of every public page.' },
        ],
    },

    stats: {
        slots: [
            { key: 'image', label: 'Illustration (optional)', description: 'e.g. the occupancy calendar graphic. Shown under the numbers with the image caption.' },
        ],
    },

    // Phone mockups are drawn in HTML from the steps' "screen" lines, unless a screenshot is uploaded.
    journey: {
        slots: [
            { key: 'chapter_shot', label: 'Screen beside this chapter', description: 'On the v2 audience pages, this section is a chapter of the journey with a screen beside it. Empty: step 2’s screenshot if there is one, else a bundled screen.', v2: true },
        ],
        dynamic: (contentKeys) => shotSlots(contentKeys, 'steps', 'step_{i}_shot', 'Step {n}: screenshot instead of the drawn phone'),
    },

    comparison: {
        slots: [
            { key: 'image', label: 'Photo above the two columns (optional)', description: 'Wide, about 2100 × 900. Empty: no photo.', v2: true },
        ],
    },

    cta_banner: {
        slots: [
            { key: 'image', label: 'Background photo', description: 'Wide, about 1800 × 900; the words sit over its left side. Empty: a bundled photo.', v2: true },
        ],
    },

    // "See it in action" (v2 pages): one real screen per item. Items without a screen are left out.
    showcase: {
        slots: [],
        dynamic: (contentKeys) => featureSlots(contentKeys, 'items', 'item_{i}_shot', 'Screen {n}')
            .map(slot => ({ ...slot, description: 'A phone or computer screenshot (use made-up or consenting guests’ names). Tall phone screens show in a phone frame.' })),
    },

    comparison_table: { slots: [] },
    // /pricing: one picture per device card (TenaFi Lite, Pro, Outdoor).
    devices: {
        slots: [],
        dynamic: (contentKeys) => featureSlots(contentKeys, 'items', 'device_{i}_image', 'Device {n}: picture')
            .map(slot => ({ ...slot, description: 'The device on a transparent background, wide (about 1320 × 780). It sits on the card’s light, yellow or dark panel.' }))
            // Up to three more pictures on the device's own page (/pricing/lite, ...): the device in use, close-ups.
            .flatMap(slot => [slot, ...[1, 2, 3].map(n => ({
                key: slot.key.replace(/_image$/, `_gallery_${n}`),
                label: slot.label.replace('picture', `page picture ${n}`),
                description: 'Shown on this device’s own page. A photo of it in use or a close-up, landscape (about 1600 × 1200).',
            }))]),
    },
    pay_options: { slots: [] },
    demo: { slots: [] },
    worth_calculator: { slots: [] },
    tiles: { slots: [] },
    announcement: { slots: [] },

    signup: { slots: [] },
    plans: { slots: [] },
    footer: { slots: [] },
    feature_status: { slots: [] },
};

function shotSlots(contentKeys, arrName, keyPattern, labelPattern) {
    return featureSlots(contentKeys, arrName, keyPattern, labelPattern).map(slot => ({ ...slot, description: SHOT_HINT }));
}

function featureSlots(contentKeys, arrName, keyPattern, labelPattern) {
    const indices = new Set();
    contentKeys.forEach((k) => {
        const m = k.match(new RegExp(`^${arrName}\\.(\\d+)\\..+$`));
        if (m) indices.add(parseInt(m[1], 10));
    });
    return [...indices].sort((a, b) => a - b).map((i) => ({
        key: keyPattern.replace('{i}', String(i)),
        label: labelPattern.replace('{n}', String(i + 1)),
    }));
}

/** The v2 design's pages ("v2", and later "v2-hosts", ...). Slots marked v2 show only there. */
export function isV2Page(pageSlug) {
    return pageSlug === 'v2' || String(pageSlug || '').startsWith('v2-');
}

/**
 * Return the expected media slots for a section, deduplicated.
 * Each slot is { key, label, description? }.
 */
export function getExpectedMediaSlots(sectionKey, contentKeys = [], pageSlug = null) {
    // "stats__problem" uses the "stats" schema.
    const entry = SECTION_MEDIA_SCHEMA[String(sectionKey).split('__')[0]];
    if (!entry) return [];
    const dynamic = entry.dynamic ? entry.dynamic(contentKeys) : [];
    const merged = [...(entry.slots || []), ...dynamic].filter((slot) => !slot.v2 || isV2Page(pageSlug));
    const seen = new Set();
    return merged.filter((slot) => {
        if (seen.has(slot.key)) return false;
        seen.add(slot.key);
        return true;
    });
}

/**
 * True when a key belongs to the section's declared schema (static or
 * dynamic). Custom/ad-hoc keys return false and remain removable.
 */
export function isSchemaKey(sectionKey, contentKeys, mediaKey, pageSlug = null) {
    return getExpectedMediaSlots(sectionKey, contentKeys, pageSlug).some((slot) => slot.key === mediaKey);
}

const plain = (value) => String(value ?? '').replace(/<[^>]*>/g, '').trim();

/**
 * Why a media slot isn't on the public page right now, or '' when it is.
 * Several photo slots are covered by a drawing (the hero's drawn WiFi page,
 * drawn rows and cards, the dark proof style); the editor shows this note
 * so an upload there doesn't look like it failed.
 *
 * @param {string} sectionKey e.g. "detailed_features__home"
 * @param {string} mediaKey   e.g. "section_2_image"
 * @param {object} content    the section's content map
 * @param {object} media      the section's media map (key => upload or null)
 */
export function slotNotice(sectionKey, mediaKey, content = {}, media = {}, pageSlug = null) {
    const type = String(sectionKey).split('__')[0];
    // The v2 design shows every photo slot it lists (no drawings), except as noted here.
    if (isV2Page(pageSlug)) {
        if (type === 'hero' && mediaKey === 'visual_shot') return 'Not on the v2 design: it shows the main photo.';
        if (/^(section|card)_\d+_shot$/.test(mediaKey) && type === 'path_cards') return 'Not on the v2 design: the cards show their photo.';
        return '';
    }
    const has = (key) => Boolean(media?.[key]);

    if (type === 'hero') {
        const drawn = plain(content.visual) === 'splash';
        if (mediaKey === 'main_image' && drawn) {
            return has('visual_shot')
                ? 'Not on the site: your screenshot below is shown in this spot instead.'
                : 'Not on the site: this hero shows a drawn WiFi page. Upload in “Screenshot instead of the drawn WiFi page” to replace the drawing, or set Visual to “image” under Content to show this photo.';
        }
        if (mediaKey === 'visual_shot' && !drawn) {
            return 'Not on the site: this hero shows its main photo. Set Visual to “splash” under Content to use this slot.';
        }
    }

    const row = /^(section|card)_(\d+)_image$/.exec(mediaKey);
    if (row && ((type === 'detailed_features' && row[1] === 'section') || (type === 'path_cards' && row[1] === 'card'))) {
        const [, kind, i] = row;
        const mockup = plain(content[`${kind === 'section' ? 'sections' : 'cards'}.${i}.mockup`]);
        const n = Number(i) + 1;
        const what = kind === 'section' ? `Section ${n}` : `Card ${n}`;
        if (has(`${kind}_${i}_shot`)) return `Not on the site: the screenshot in “${what}: screenshot instead of the drawing” is shown instead.`;
        if (mockup) return `Not on the site: ${what.toLowerCase()} shows a drawing. Upload in “${what}: screenshot instead of the drawing” to replace it.`;
    }

    // Trusted by: the phone needs a tall WiFi page design; a logo belongs in the logo slot.
    if (type === 'partners' && /^partner_\d+_logo$/.test(mediaKey)) {
        const { width, height } = media?.[mediaKey] || {};
        if (width && height && height / width < 1.3) {
            return 'This isn’t a tall phone screen, so it won’t fill the phone. Upload their WiFi page design here, and put the logo in their logo slot.';
        }
    }

    if (type === 'credibility' && mediaKey === 'main_image' && plain(content.style) === 'dark') {
        return 'Not on the site: the dark style shows the founder quote in this spot. Clear Style under Content to show this photo.';
    }

    return '';
}

/** A slot's name in the editor; Trusted by slots are named after their client. */
export function slotTitle(sectionKey, mediaKey, label, content = {}) {
    const partner = /^partner_(\d+)_(logo|mark)$/.exec(mediaKey);
    if (String(sectionKey).split('__')[0] === 'partners' && partner) {
        const name = plain(content[`partners.${partner[1]}.name`]);
        if (name) return partner[2] === 'mark' ? `${name}: logo` : `${name}: WiFi page design`;
    }
    return label;
}
