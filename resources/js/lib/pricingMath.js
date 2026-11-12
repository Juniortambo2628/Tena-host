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

/** The free months a pricing section offers: a whole number from 0 (no offer) to 12. */
export function clampFree(freeMonths) {
    const n = Math.floor(Number(String(freeMonths ?? '').replace(/[^\d.]/g, '')) || 0);
    return Math.max(0, Math.min(12, n));
}

/**
 * First-year cost, with the plan free for the first `freeMonths` months:
 * buying the device is device + (12 - free) x plan; paying monthly is the
 * add-on for its months (12) + (12 - free) x plan. With no free months this
 * is device + 12 x plan versus 12 x (plan + add-on).
 */
export function yearOne(planKes, device, freeMonths = 0) {
    const free = clampFree(freeMonths);
    const months = Math.min(device?.months || 12, 12);
    const buy = (device?.price_kes || 0) + planKes * (12 - free);
    const monthly = (device?.monthly_kes || 0) * months + planKes * (12 - free);
    return { buy, monthly, saving: monthly - buy };
}

/**
 * What a plan costs month by month with free months:
 *  - buy:     `now` (KES 0) for the free months, then `after` (the plan);
 *  - monthly: `now` (the add-on only) for the free months, then `then`
 *             (plan + add-on) until the add-on payments are done, then `after`.
 * `then` is null when the add-on ends within the free months.
 */
export function freeSchedule(planKes, pay, device, freeMonths) {
    const free = clampFree(freeMonths);
    const addon = pay === 'monthly' ? device?.monthly_kes || 0 : 0;
    const addonMonths = device?.months || 12;
    return {
        free,
        now: addon,
        then: pay === 'monthly' && addonMonths > free ? planKes + addon : null,
        after: planKes,
    };
}

/**
 * "{free_months} months" in a sentence, for one month or several:
 * "Your first {free_months} months are free" -> "Your first month is free".
 */
export function fillFree(template, freeMonths) {
    let text = String(template || '');
    if (clampFree(freeMonths) === 1) {
        text = text
            .replace(/first \{free_months\} months are/g, 'first month is')
            .replace(/first \{free_months\} months/g, 'first month')
            .replace(/\{free_months\} months/g, '1 month');
    }
    return text.replace(/\{free_months\}/g, String(clampFree(freeMonths)));
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
 * forms (&lt;br&gt;), and Unicode line separators. Tags are left for the
 * caller to strip.
 */
export function splitLines(text) {
    return String(text || '')
        .replace(/&lt;br\s*\/?&gt;/gi, '\n')
        .replace(/<br\s*\/?>|<\/p>|<\/div>|<\/li>/gi, '\n')
        // Unicode line and paragraph separators (pasted from some editors and phones).
        .split(/\r\n|\r|\n|\u2028|\u2029|\u0085/);
}

/** ?pay=monthly&device=pro from a URL's query, falling back to the defaults. */
export function readChoice(search, deviceIds = []) {
    const query = new URLSearchParams(search || '');
    const pay = query.get('pay') === 'monthly' ? 'monthly' : 'buy';
    const device = deviceIds.includes(query.get('device')) ? query.get('device') : deviceIds[0] || null;
    return { pay, device };
}

/**
 * A plan button's link: the page's sign-up form, with the plan and the
 * choice. With free months the device is passed in both modes, and the
 * free months too.
 */
export function planHref(joinHref, planId, pay, device, freeMonths = 0) {
    if (!planId || !String(joinHref).includes('#')) return joinHref;
    const free = clampFree(freeMonths);
    const params = new URLSearchParams({ plan: planId, pay });
    if (device && (pay === 'monthly' || free > 0)) params.set('device', device);
    if (free > 0) params.set('free_months', String(free));
    return `${joinHref}?${params}`;
}
