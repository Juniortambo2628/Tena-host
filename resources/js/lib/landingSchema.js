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
        dynamic: (contentKeys) => featureSlots(contentKeys, 'sections', 'section_{i}_image', 'Section {n} image'),
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
        dynamic: (contentKeys) => featureSlots(contentKeys, 'partners', 'partner_{i}_logo', 'Partner {n} logo'),
    },
};

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
    const entry = SECTION_MEDIA_SCHEMA[sectionKey];
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
