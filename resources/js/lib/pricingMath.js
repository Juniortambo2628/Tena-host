/**
 * TenaFi pricing sums, shared by every pricing on the site (/pricing, /hosts,
 * /business, the homepage) and the sign-up summary, so no two places can
 * quote different numbers. Pure functions with no imports, so tests can run
 * them under plain Node (tests/Feature/PricingOfferTest.php).
 *
 * The offer: buy your TenaFi device once; your first `freeMonths` months of
 * the base plan (Basic) are free; then choose Free, Basic, Starter or Growth.
 * During the free months a bigger plan costs only the difference from the
 * base plan. There is no paying for the device monthly.
 */

/** "KES 9,500" */
export const formatKes = (n) => `KES ${Math.round(Number(n) || 0).toLocaleString('en-KE')}`;

/** A KES amount from a CMS value ("4,500", "KES 4500", 4500); 0 when none. */
export const kesOf = (value) => Number(String(value ?? '').replace(/<[^>]*>/g, '').replace(/[^\d]/g, '')) || 0;

/** The free months a pricing section offers: a whole number from 0 (no offer) to 12. */
export function clampFree(freeMonths) {
    const n = Math.floor(Number(String(freeMonths ?? '').replace(/[^\d.]/g, '')) || 0);
    return Math.max(0, Math.min(12, n));
}

/**
 * A plan's monthly price during the free months: what's left after the base
 * plan (Basic) is free, so max(plan - base, 0). An admin's intro_price_kes
 * overrides it; with no free months it's the plan's price.
 */
export function introPrice(planKes, baseKes, freeMonths, override = '') {
    if (clampFree(freeMonths) === 0) return planKes;
    if (String(override ?? '').trim() !== '') return kesOf(override);
    return Math.max(planKes - baseKes, 0);
}

/** Year one: the device once, the intro price for the free months, then the plan. */
export function yearOne(planKes, introKes, deviceKes, freeMonths) {
    const free = clampFree(freeMonths);
    return deviceKes + free * introKes + (12 - free) * planKes;
}

/** The cheapest plan you pay for (Free and KES 0 plans don't count), or null. */
export function cheapestPaid(plans) {
    return [...plans].filter((p) => kesOf(p.price_kes) > 0).sort((a, b) => kesOf(a.price_kes) - kesOf(b.price_kes))[0] || null;
}

/** KES per US dollar until Admin -> Settings says otherwise. */
export const DEFAULT_KES_PER_USD = 129;

/**
 * "(about $12)" for a KES amount, from the one site-wide rate (Admin ->
 * Settings -> Shillings per US dollar), rounded to the nearest dollar.
 * '' for KES 0 (never "(about $0)").
 */
export const aboutUsd = (kes, rate = DEFAULT_KES_PER_USD) => {
    const perUsd = Number(rate) > 0 ? Number(rate) : DEFAULT_KES_PER_USD;
    return kes > 0 ? `(about $${Math.round(kes / perUsd)})` : '';
};

/** A plan's unit text without a typed "(about $23)": the dollars are worked out instead. */
export const withoutTypedUsd = (text) => String(text || '').replace(/\s*\(about\s*\$\s*[\d,.]+\)/gi, '').trim();

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

/** "{name}" placeholders in CMS text; unknown ones are left as they are. */
export const fill = (template, values) => String(template || '').replace(/\{(\w+)\}/g, (m, key) => (key in values ? values[key] : m));

/**
 * Splits a multi-line CMS field into its lines: plain newlines (\n, \r\n),
 * <br>, </p>, </div> and </li> from the rich-text editor, their escaped
 * forms (&lt;br&gt;), and Unicode line separators. Tags are left for the
 * caller to strip.
 */
export function splitLines(text) {
    return String(text || '')
        .replace(/&lt;br\s*\/?&gt;/gi, '\n')
        .replace(/<br\s*\/?>|<\/p>|<\/div>|<\/li>/gi, '\n')
        .split(/\r\n|\r|\n|\u2028|\u2029|\u0085/);
}

/** ?device=pro from a URL's query, else the first device. Anything else (?pay=monthly) is ignored. */
export function readDevice(search, deviceIds = []) {
    const wanted = new URLSearchParams(search || '').get('device');
    return deviceIds.includes(wanted) ? wanted : deviceIds[0] || null;
}

/** A plan button's link: the page's sign-up form, with the plan, the device and the free months. */
export function planHref(joinHref, planId, device, freeMonths = 0) {
    if (!planId || !String(joinHref).includes('#')) return joinHref;
    const params = new URLSearchParams({ plan: planId });
    if (device) params.set('device', device);
    const free = clampFree(freeMonths);
    if (free > 0) params.set('free_months', String(free));
    return `${joinHref}?${params}`;
}

/**
 * The plans to show, cheapest first (Free, Basic, Starter, Growth). With no
 * free months, Free shows only when the admin turns it on (show_free_plan).
 * `plans` are CMS rows ({ id, label, price_kes, ... }).
 */
export function visiblePlans(plans, freeMonths, showFree = false) {
    const strip = (v) => String(v ?? '').replace(/<[^>]*>/g, '').trim();
    return plans
        .map((p, index) => ({ ...p, index, priceKes: kesOf(p.price_kes), isFree: strip(p.id) === 'free' }))
        .filter((p) => strip(p.label))
        .filter((p) => !p.isFree || clampFree(freeMonths) > 0 || showFree)
        .sort((a, b) => a.priceKes - b.priceKes || a.index - b.index);
}
