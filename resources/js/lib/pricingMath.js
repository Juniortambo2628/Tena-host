/**
 * TenaFi pricing sums, shared by the pricing sections (/hosts, /business,
 * /pricing) and the sign-up summary. Pure functions with no imports, so
 * tests can run them under plain Node (tests/Feature/PricingOptionsTest.php).
 *
 * A device has `price_kes` (bought upfront, installed), `monthly_kes` (the
 * add-on while paying monthly) and `months` (how long the add-on lasts).
 */

/** "KES 9,500" */
export const formatKes = (n) => `KES ${Math.round(Number(n) || 0).toLocaleString('en-KE')}`;

/** A plan's monthly price on each way to pay; on "monthly", for the add-on months. */
export function planMonthly(planKes, pay, device) {
    return pay === 'monthly' && device ? planKes + (device.monthly_kes || 0) : planKes;
}

/** First-year cost buying the device (device + 12 x plan) versus paying monthly (12 x (plan + add-on)). */
export function yearOne(planKes, device) {
    const months = Math.min(device?.months || 12, 12);
    const buy = (device?.price_kes || 0) + planKes * 12;
    const monthly = (planKes + (device?.monthly_kes || 0)) * months + planKes * (12 - months);
    return { buy, monthly, saving: monthly - buy };
}

/**
 * KES per US dollar implied by a plan's own "(about $23)" unit text, so new
 * prices get the same conversion the admin chose; null when there is none.
 */
export function kesPerUsd(planKes, unitText) {
    const match = String(unitText || '').match(/about\s*\$\s*([\d,.]+)/i);
    const usd = match ? Number(match[1].replace(/,/g, '')) : 0;
    return usd > 0 && planKes > 0 ? planKes / usd : null;
}

/** "(about $73)" for a KES amount at that rate, or ''. */
export const aboutUsd = (kes, rate) => (rate ? `(about $${Math.round(kes / rate)})` : '');

/**
 * Splits a multi-line CMS field into its lines: plain newlines (\n, \r\n),
 * <br>, </p>, </div> and </li> from the rich-text editor, and their escaped
 * forms (&lt;br&gt;). Tags are left for the caller to strip.
 */
export function splitLines(text) {
    return String(text || '')
        .replace(/&lt;br\s*\/?&gt;/gi, '\n')
        .replace(/<br\s*\/?>|<\/p>|<\/div>|<\/li>/gi, '\n')
        .split(/\r\n|\r|\n/);
}

/** ?pay=monthly&device=pro from a URL's query, falling back to the defaults. */
export function readChoice(search, deviceIds = []) {
    const query = new URLSearchParams(search || '');
    const pay = query.get('pay') === 'monthly' ? 'monthly' : 'buy';
    const device = deviceIds.includes(query.get('device')) ? query.get('device') : deviceIds[0] || null;
    return { pay, device };
}

/** A plan button's link: the page's sign-up form, with the plan and the choice. */
export function planHref(joinHref, planId, pay, device) {
    if (!planId || !String(joinHref).includes('#')) return joinHref;
    const params = new URLSearchParams({ plan: planId, pay });
    if (pay === 'monthly' && device) params.set('device', device);
    return `${joinHref}?${params}`;
}
