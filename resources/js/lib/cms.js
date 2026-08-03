/**
 * Helper to extract CMS section data by section_key.
 */
export function getSection(sections, key) {
    return sections.find(s => s.section_key === key) || null;
}

/**
 * Get content value from a section by key.
 */
export function getContent(section, key, defaultValue = '') {
    if (!section || !section.content) return defaultValue;
    return section.content[key] ?? defaultValue;
}

/**
 * Get media path from a section by key.
 */
export function getMedia(section, key, defaultValue = '') {
    if (!section || !section.media) return defaultValue;
    const value = section.media[key] ?? defaultValue;
    if (!value) return defaultValue;
    if (typeof value === 'string') return value;
    if (typeof value === 'object') return value.optimized_path || value.original_path || defaultValue;
    return defaultValue;
}

/**
 * Parse a JSON content value.
 */
export function parseJsonContent(section, key, defaultValue = []) {
    const value = getContent(section, key, null);
    if (!value) return defaultValue;
    try {
        return typeof value === 'string' ? JSON.parse(value) : value;
    } catch {
        return defaultValue;
    }
}

/**
 * Extract numbered items from section content (features, steps, plans, etc.).
 * Looks for keys like "features.0.title", "features.0.description", etc.
 */
export function extractItems(section, prefix, fields) {
    if (!section || !section.content) return [];
    const items = [];
    let index = 0;

    while (true) {
        const item = {};
        let found = false;

        for (const field of fields) {
            const key = `${prefix}.${index}.${field}`;
            if (section.content[key] !== undefined) {
                item[field] = section.content[key];
                found = true;
            }
        }

        if (!found) break;
        items.push(item);
        index++;
    }

    return items;
}

/**
 * Sanitize HTML content from the CMS editor.
 * Decodes common HTML entities and replaces &nbsp; with regular spaces.
 */
export function sanitizeHtml(html) {
    if (!html || typeof html !== 'string') return '';
    const textarea = typeof document !== 'undefined' ? document.createElement('textarea') : null;
    let decoded = html;
    if (textarea) {
        textarea.innerHTML = html;
        decoded = textarea.value;
    } else {
        decoded = decoded
            .replace(/&amp;/g, '&')
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>')
            .replace(/&quot;/g, '"')
            .replace(/&#039;/g, "'")
            .replace(/&nbsp;/g, ' ');
    }
    return decoded.replace(/&nbsp;/g, ' ');
}

/**
 * Extract items from a JSON-encoded content field.
 */
export function extractJsonItems(section, key) {
    const raw = getContent(section, key, '[]');
    try {
        return typeof raw === 'string' ? JSON.parse(raw) : (Array.isArray(raw) ? raw : []);
    } catch {
        return [];
    }
}
