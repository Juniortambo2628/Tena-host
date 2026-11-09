import { getContent, getUpload, getMedia, extractItems, stripHtml } from '@/lib/cms';

/**
 * TenaFi devices (Lite, Pro, Outdoor) from the /pricing page's "devices"
 * section (Admin -> Public Pages -> Devices & pricing), and the sums the
 * pricing page and the sign-up form share, so their numbers never differ.
 */
export const formatKes = (n) => `KES ${Math.round(Number(n) || 0).toLocaleString('en-KE')}`;

const kes = (value) => Number(String(stripHtml(value ?? '')).replace(/\D/g, '')) || 0;

/** @returns {{ id, name, badge, bestFor, coverage, coverageLabel, capacity, capacityLabel, price, monthly, months, style, image }[]} */
export function devicesFrom(section) {
    if (!section) return [];
    return extractItems(section, 'items', ['id', 'name', 'badge', 'best_for', 'coverage', 'coverage_label', 'capacity', 'capacity_label', 'price_kes', 'monthly_kes', 'months', 'style'])
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
            image: getUpload(section, `device_${i}_image`) || getMedia(section, `device_${i}_image`, ''),
        }))
        .filter((d) => d.name);
}

/** A plan's monthly price while paying the device off (months 1-12), and after. */
export const monthlyWithDevice = (planKes, device) => planKes + (device?.monthly || 0);

/** First-year cost each way, for the worked example. */
export function firstYear(planKes, device) {
    const upfront = (device?.price || 0) + planKes * 12;
    const months = Math.min(device?.months || 12, 12);
    const monthly = monthlyWithDevice(planKes, device) * months + planKes * (12 - months);
    return { upfront, monthly, saving: monthly - upfront };
}

/** "{monthly}"-style placeholders in CMS text. */
export const fill = (text, values) => String(text || '').replace(/\{(\w+)\}/g, (m, key) => (key in values ? values[key] : m));

export const contentText = (section, key, fallback = '') => stripHtml(getContent(section, key, fallback));
