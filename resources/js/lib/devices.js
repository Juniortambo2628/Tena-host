import { getContent, getUpload, getMedia, extractItems, stripHtml } from '@/lib/cms';
import { formatKes, planMonthly, yearOne, splitLines } from '@/lib/pricingMath';

/**
 * TenaFi devices (Lite, Pro, Outdoor) from the /pricing page's "devices"
 * section (Admin -> Public Pages -> Devices & pricing), and the sums the
 * pricing page and the sign-up form share, so their numbers never differ.
 */
export { formatKes };

const kes = (value) => Number(String(stripHtml(value ?? '')).replace(/\D/g, '')) || 0;

/** @returns {{ id, name, badge, bestFor, coverage, coverageLabel, capacity, capacityLabel, price, monthly, months, style, tagline, specs: string[][], highlights: string[], gallery: string[], image }[]} */
export function devicesFrom(section) {
    if (!section) return [];
    return extractItems(section, 'items', ['id', 'name', 'badge', 'best_for', 'coverage', 'coverage_label', 'capacity', 'capacity_label', 'price_kes', 'monthly_kes', 'months', 'style', 'tagline', 'specs', 'highlights'])
        .map((d, i) => ({
            id: stripHtml(d.id) || String(i),
            name: stripHtml(d.name),
            badge: stripHtml(d.badge),
            bestFor: stripHtml(d.best_for),
            coverage: stripHtml(d.coverage),
            coverageLabel: stripHtml(d.coverage_label),
            capacity: stripHtml(d.capacity),
            capacityLabel: stripHtml(d.capacity_label),
            price: kes(d.price_kes),
            monthly: kes(d.monthly_kes),
            months: kes(d.months) || 12,
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

/** A plan's monthly price while paying the device off (months 1-12), and after. */
// The sums themselves live in pricingMath.js, shared with the /hosts and
// /business pricing, so the two pages can never quote different numbers.
const asPriced = (device) => device && { price_kes: device.price, monthly_kes: device.monthly, months: device.months };

export const monthlyWithDevice = (planKes, device) => planMonthly(planKes, 'monthly', asPriced(device));

/** First-year cost each way, for the worked example. */
export function firstYear(planKes, device) {
    const { buy, monthly, saving } = yearOne(planKes, asPriced(device));
    return { upfront: buy, monthly, saving };
}

/** "{monthly}"-style placeholders in CMS text. */
export const fill = (text, values) => String(text || '').replace(/\{(\w+)\}/g, (m, key) => (key in values ? values[key] : m));

export const contentText = (section, key, fallback = '') => stripHtml(getContent(section, key, fallback));
