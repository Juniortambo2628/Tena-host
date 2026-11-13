import React, { useCallback, useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { getContent, extractItems, stripHtml, textLines } from '@/lib/cms';
import {
    formatKes, kesOf, clampFree, introPrice, yearOne, kesPerUsd, aboutUsd, fillFree, fill, readDevice, visiblePlans,
} from '@/lib/pricingMath';
import './PlanOffer.css';

/**
 * TenaFi's offer, on every pricing on the site (/pricing, /hosts, /business,
 * the homepage, How it works): 1. get your TenaFi device, pay once; 2. your
 * first free_months months of Basic are free; 3. then choose Free, Basic,
 * Starter or Growth. Every number is worked out from the plans' price_kes,
 * the devices (kept once on /pricing) and free_months (lib/pricingMath.js).
 * There is no paying for the device monthly.
 */

const text = (section, key, fallback = '') => stripHtml(getContent(section, key, fallback));

export const PLAN_FIELDS = ['id', 'label', 'tagline', 'badge', 'price', 'price_kes', 'intro_price_kes', 'unit', 'features', 'note', 'description', 'cta', 'variant'];

/** The offer's settings on a pricing section; free_months 0 (or none) means no offer. */
export function offerOf(section) {
    const plans = extractItems(section, 'plans', ['unit']);
    return {
        free: clampFree(getContent(section, 'free_months', '')),
        baseId: text(section, 'base_free_plan', 'basic') || 'basic',
        showFree: ['1', 'true', 'yes', 'on'].includes(text(section, 'show_free_plan', '0').toLowerCase()),
        // "per unit" / "per location": the field, else the start of the first plan's unit ("per unit, per month").
        unit: text(section, 'price_unit') || (stripHtml(plans[0]?.unit).match(/^per [^,]+/i)?.[0] ?? ''),
    };
}

/**
 * The plans to show, cheapest first (Free, Basic, Starter, Growth): with no
 * free months, Free shows only when the admin turns it on (show_free_plan).
 */
export function offerPlans(section, plans, offer = offerOf(section)) {
    return visiblePlans(plans, offer.free, offer.showFree);
}

/** KES per dollar from the section's first plan with an "(about $X)", so one amount reads the same everywhere. */
function sectionRate(plans) {
    for (const p of plans) {
        const rate = kesPerUsd(kesOf(p.price_kes), stripHtml(p.unit));
        if (rate) return rate;
    }
    return null;
}

/**
 * One card's price: the big amount, the line under it, then the "then" and
 * device lines, each amount with its "(about $X)".
 */
export function offerPrice(section, plan, plans, device, offer = offerOf(section)) {
    const rate = sectionRate(plans);
    const usd = (kes) => aboutUsd(kes, rate);
    const money = (kes) => `${formatKes(kes)} ${usd(kes)}`.trim();
    // "{plan}/month" -> "KES 4,500/month (about $35)"; "{device_price}" -> "KES 42,000 (about $322)".
    const line = (key, fallback, values) => fillFree(text(section, key, fallback), offer.free)
        .replace(/\{(\w+)\}(\/month)?/g, (m, key, month) => (key in values
            ? (month ? `${formatKes(values[key])}/month ${usd(values[key])}` : (typeof values[key] === 'number' ? money(values[key]) : values[key]))
            : m))
        .replace(/\s{2,}/g, ' ').replace(/\s+([,.])/g, '$1').trim();

    const base = plans.find((p) => stripHtml(p.id) === offer.baseId);
    const planKes = kesOf(plan.price_kes);
    const deviceLine = device
        ? line('device_line_card', 'plus {device}, {device_price} once', { device: device.name, device_price: device.price_kes })
        : '';

    if (stripHtml(plan.id) === 'free') {
        return {
            amount: `${formatKes(0)}/month`,
            under: line('free_plan_line', 'forever', {}),
            lines: [deviceLine].filter(Boolean),
        };
    }
    if (offer.free === 0) {
        return {
            amount: stripHtml(plan.price) || formatKes(planKes),
            under: stripHtml(plan.unit),
            lines: [deviceLine].filter(Boolean),
        };
    }

    const intro = introPrice(planKes, kesOf(base?.price_kes), offer.free, stripHtml(plan.intro_price_kes));
    return {
        amount: formatKes(intro),
        under: [offer.unit, line('intro_line', 'a month for your first {free_months} months', {})].filter(Boolean).join(', ')
            + (intro > 0 ? ` ${usd(intro)}` : ''),
        lines: [line('then_line', 'then {plan}/month', { plan: planKes }), deviceLine].filter(Boolean),
    };
}

/**
 * The devices for a pricing section: its own devices.{i}.* rows if it has
 * any, else the shared ones (kept once on /pricing's devices section).
 */
export function useSectionDevices(section) {
    const { devices: shared = [] } = usePage().props;
    const own = extractItems(section, 'devices', ['id', 'name', 'image', 'coverage', 'capacity', 'best_for', 'price_kes'])
        .filter((d) => stripHtml(d.name))
        .map((d) => {
            const id = stripHtml(d.id);
            return {
                id,
                name: stripHtml(d.name),
                image: stripHtml(d.image) || (['lite', 'pro', 'outdoor'].includes(id) ? `/images/devices/photos/tenafi-${id}.webp` : ''),
                coverage: stripHtml(d.coverage),
                coverage_label: '',
                capacity: stripHtml(d.capacity),
                capacity_label: '',
                best_for: stripHtml(d.best_for),
                price_kes: kesOf(d.price_kes),
            };
        });
    return own.length ? own : shared.filter((d) => d.name && d.price_kes > 0);
}

/** The chosen device, read from and written back to the URL (?device=pro). ?pay=monthly is dropped. */
export function useDeviceChoice(devices) {
    const ids = devices.map((d) => d.id);
    const [id, setId] = useState(ids[0] || null);

    // After hydration, so the server render and the first client render match.
    useEffect(() => {
        if (typeof window !== 'undefined') setId(readDevice(window.location.search, ids));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ids.join(',')]);

    const choose = useCallback((next) => {
        setId(next);
        if (typeof window !== 'undefined') {
            const url = new URL(window.location.href);
            url.searchParams.set('device', next);
            url.searchParams.delete('pay');
            window.history.replaceState(window.history.state, '', url);
        }
    }, []);

    return { device: devices.find((d) => d.id === id) || devices[0] || null, choose };
}

/** The offer's three steps; {free_months} in a title becomes the number. */
export function PricingSteps({ steps = [], freeMonths = 0, compact = false, className = '' }) {
    const rows = steps.map((s) => ({ title: fillFree(stripHtml(s.title), freeMonths), text: fillFree(stripHtml(s.text), freeMonths) })).filter((s) => s.title);
    if (!freeMonths || !rows.length) return null;

    return (
        <ol className={`pricing-steps ${compact ? 'pricing-steps--compact' : ''} ${className}`.trim()}>
            {rows.map((s, i) => (
                <li key={i} className="pricing-steps__step">
                    <span className="pricing-steps__number" aria-hidden="true">{i + 1}</span>
                    <span className="pricing-steps__body">
                        <b className="pricing-steps__title">{s.title}</b>
                        {s.text && <span className="pricing-steps__text">{s.text}</span>}
                    </span>
                </li>
            ))}
        </ol>
    );
}

/** The section's own steps (steps.{i}.title / text). */
export const sectionSteps = (section) => extractItems(section, 'steps', ['title', 'text']);

/** Device chips (Lite / Pro / Outdoor) with each price once; a radio group operated with the arrow keys. */
export function DevicePicker({ section, devices, choice }) {
    const refs = useRef([]);
    if (devices.length < 2) return null;
    const pick = (i) => {
        choice.choose(devices[i].id);
        refs.current[i]?.focus();
    };
    const onKeyDown = (e, i) => {
        const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
        if (!step) return;
        e.preventDefault();
        pick((i + step + devices.length) % devices.length);
    };

    return (
        <div className="pricing-picker">
            <span className="pricing-picker__label" id="pricing-picker-label">{text(section, 'devices_picker_label', 'Your TenaFi device')}</span>
            <div role="radiogroup" aria-labelledby="pricing-picker-label" className="pricing-picker__chips">
                {devices.map((d, i) => {
                    const checked = d.id === choice.device?.id;
                    return (
                        <button
                            key={d.id}
                            ref={(el) => { refs.current[i] = el; }}
                            type="button"
                            role="radio"
                            aria-checked={checked}
                            tabIndex={checked ? 0 : -1}
                            onClick={() => pick(i)}
                            onKeyDown={(e) => onKeyDown(e, i)}
                        >
                            <span>{d.name}</span>
                            <small>{formatKes(d.price_kes)} once</small>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

/** The worked example, year one including the free months, from the plan and device it names. */
export function OfferExample({ section, plans, devices, offer = offerOf(section) }) {
    const plan = plans.find((p) => stripHtml(p.id) === text(section, 'example_plan'));
    const device = devices.find((d) => d.id === text(section, 'example_device'));
    const label = text(section, 'example_label');
    const planKes = kesOf(plan?.price_kes);
    if (!label || !planKes || !device) return null;

    const base = plans.find((p) => stripHtml(p.id) === offer.baseId);
    const intro = introPrice(planKes, kesOf(base?.price_kes), offer.free, stripHtml(plan.intro_price_kes));
    const values = {
        label,
        device_price: formatKes(device.price_kes),
        plan: formatKes(planKes),
        year_one: formatKes(yearOne(planKes, intro, device.price_kes, offer.free)),
        intro_phrase: intro > 0
            ? fill(fillFree(text(section, 'intro_paid_phrase', '{intro}/month for {free_months} months'), offer.free), { intro: formatKes(intro) })
            : fillFree(text(section, 'intro_free_phrase', '{free_months} months free'), offer.free),
    };
    const template = offer.free > 0
        ? text(section, 'example_text', '{label}: {device_price} once, {intro_phrase}, then {plan}. Year one: {year_one}.')
        : text(section, 'example_full_text', '{label}: {device_price} once, then {plan}/month. Year one: {year_one}.');
    // A template from before the offer ({buy}, {monthly}) has nothing to fill: show nothing rather than braces.
    const sentence = fill(template, values);
    if (/\{\w+\}/.test(sentence)) return null;

    return <p className="pay-example">{sentence}</p>;
}

/** "Month to month after your free months, 30 days' notice. Your device is yours." */
export function OfferNote({ section, offer = offerOf(section) }) {
    const note = offer.free > 0 ? fillFree(text(section, 'offer_note'), offer.free) : '';
    const vat = text(section, 'vat_note');
    if (!note && !vat) return null;
    return (
        <div className="pricing-offer-note">
            {note && <p>{note}</p>}
            {vat && <p className="pricing-offer-note__vat">{vat}</p>}
        </div>
    );
}

/** "Choose your TenaFi device": one card per device, its price once, and the small print. */
export function DevicesRow({ section, devices }) {
    if (!devices.length) return null;

    return (
        <div className="pay-devices">
            <h3 className="pay-devices__title">{text(section, 'devices_title', 'Choose your TenaFi device')}</h3>
            <ul className="pay-devices__list">
                {devices.map((d) => (
                    <li key={d.id} className="pay-device">
                        {d.image && <img src={d.image} alt={d.name} width="440" height="260" loading="lazy" decoding="async" />}
                        <b className="pay-device__name">{d.name}</b>
                        <span className="pay-device__specs">
                            {[d.coverage && `${d.coverage}${d.coverage_label && d.coverage_label !== 'coverage' ? ` ${d.coverage_label}` : ''}`, d.capacity && `${d.capacity} ${d.capacity_label || 'devices'}`].filter(Boolean).join(' · ')}
                        </span>
                        {d.best_for && <span className="pay-device__for">{d.best_for}</span>}
                        <span className="pay-device__price">{formatKes(d.price_kes)} <small>once</small></span>
                    </li>
                ))}
            </ul>
            {text(section, 'devices_note') && <p className="pay-devices__note">{text(section, 'devices_note')}</p>}
        </div>
    );
}

/** "Bigger space? Add more devices.": one "Space | Devices" per line, and a WhatsApp link. */
export function BiggerSpaces({ section, site }) {
    const rows = textLines(getContent(section, 'bigger_spaces', ''), site)
        .map((line) => line.text.split('|').map((part) => part.trim()))
        .filter(([space]) => space);
    if (!rows.length) return null;

    const ctaLabel = text(section, 'bigger_cta_label');
    const rawHref = text(section, 'bigger_cta_href');
    const whatsapp = stripHtml(getContent(site?.footer, 'whatsapp', '')).replace(/\D/g, '');
    const href = rawHref.startsWith('whatsapp:')
        ? (whatsapp ? `https://wa.me/${whatsapp}?text=${encodeURIComponent(rawHref.slice(9))}` : '')
        : rawHref;

    return (
        <div className="pay-bigger">
            <h3>{text(section, 'bigger_title', 'Bigger space? Add more devices.')}</h3>
            <ul>
                {rows.map(([space, devices], i) => <li key={i}><span>{space}</span>{devices && <b>{devices}</b>}</li>)}
            </ul>
            {ctaLabel && href && <a className="pay-bigger__cta" href={href} target={href.startsWith('http') ? '_blank' : undefined} rel="noopener noreferrer">{ctaLabel}</a>}
        </div>
    );
}
