import { getContent, getUpload, getMedia, extractItems, stripHtml } from '@/lib/cms';
import { formatKes, splitLines } from '@/lib/pricingMath';

/**
 * TenaFi devices (Lite, Pro, Outdoor) from the /pricing page's "devices"
 * section (Admin -> Public Pages -> Devices & pricing), as the pricing page
 * and the device pages show them.
 */
export { formatKes };

const kes = (value) => Number(String(stripHtml(value ?? '')).replace(/\D/g, '')) || 0;

/** @returns {{ id, name, badge, bestFor, coverage, coverageLabel, capacity, capacityLabel, price, style, tagline, specs: string[][], highlights: string[], gallery: string[], image }[]} */
export function devicesFrom(section) {
    if (!section) return [];
    return extractItems(section, 'items', ['id', 'name', 'badge', 'best_for', 'coverage', 'coverage_label', 'capacity', 'capacity_label', 'price_kes', 'style', 'tagline', 'specs', 'highlights'])
        .map((d, i) => ({
            id: stripHtml(d.id) || String(i),
            name: stripHtml(d.name),
            badge: stripHtml(d.badge),
            bestFor: stripHtml(d.best_for),
            coverage: stripHtml(d.coverage),
            coverageLabel: stripHtml(d.coverage_label),
            capacity: stripHtml(d.capacity),
            capacityLabel: stripHtml(d.capacity_label),
            // Paid once (Pay monthly is gone; stored monthly_kes / months are no longer read).
            price: kes(d.price_kes),
            style: stripHtml(d.style) || 'light',
            tagline: stripHtml(d.tagline),
            // "Label | Value", one per line (the device's own page).
            specs: splitLines(d.specs).map((line) => stripHtml(line).split('|').map((part) => part.trim())).filter(([label, value]) => label && value),
            highlights: splitLines(d.highlights).map((line) => stripHtml(line).trim()).filter(Boolean),
            gallery: [1, 2, 3].map((n) => getUpload(section, `device_${i}_gallery_${n}`)).filter(Boolean),
            image: getUpload(section, `device_${i}_image`) || getMedia(section, `device_${i}_image`, ''),
        }))
        .filter((d) => d.name);
}

/** "{name}"-style placeholders in CMS text. */
export const fill = (text, values) => String(text || '').replace(/\{(\w+)\}/g, (m, key) => (key in values ? values[key] : m));

export const contentText = (section, key, fallback = '') => stripHtml(getContent(section, key, fallback));
