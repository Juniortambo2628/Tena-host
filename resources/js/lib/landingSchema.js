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
        slots: [],
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
        dynamic: (contentKeys) => featureSlots(contentKeys, 'partners', 'partner_{i}_logo', 'Partner {n}: WiFi page screenshot (portrait, about 1:2)')
            .map(slot => ({ ...slot, description: 'A phone screenshot of their TenaFi WiFi page, status bar cropped off, e.g. 600 × 1200, under 150 KB. Until then the row shows a placeholder page with their name.' })),
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
        slots: [],
        dynamic: (contentKeys) => shotSlots(contentKeys, 'steps', 'step_{i}_shot', 'Step {n}: screenshot instead of the drawn phone'),
    },

    comparison_table: { slots: [] },
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

/**
 * Return the expected media slots for a section, deduplicated.
 * Each slot is { key, label, description? }.
 */
export function getExpectedMediaSlots(sectionKey, contentKeys = []) {
    // "stats__problem" uses the "stats" schema.
    const entry = SECTION_MEDIA_SCHEMA[String(sectionKey).split('__')[0]];
    if (!entry) return [];
    const dynamic = entry.dynamic ? entry.dynamic(contentKeys) : [];
    const merged = [...(entry.slots || []), ...dynamic];
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
export function isSchemaKey(sectionKey, contentKeys, mediaKey) {
    return getExpectedMediaSlots(sectionKey, contentKeys).some((slot) => slot.key === mediaKey);
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
export function slotNotice(sectionKey, mediaKey, content = {}, media = {}) {
    const type = String(sectionKey).split('__')[0];
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

    // Trusted by only shows tall phone screenshots; anything squarer keeps the drawn placeholder phone.
    if (type === 'partners' && /^partner_\d+_logo$/.test(mediaKey)) {
        const { width, height } = media?.[mediaKey] || {};
        if (width && height && height / width < 1.3) {
            return 'Not on the site: this image isn’t a tall phone screenshot, so the drawn placeholder phone is shown. Upload a portrait screenshot (about twice as tall as it is wide).';
        }
    }

    if (type === 'credibility' && mediaKey === 'main_image' && plain(content.style) === 'dark') {
        return 'Not on the site: the dark style shows the founder quote in this spot. Clear Style under Content to show this photo.';
    }

    return '';
}
