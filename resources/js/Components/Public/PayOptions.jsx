import React, { useCallback, useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { getContent, extractItems, stripHtml, textLines } from '@/lib/cms';
import { ShieldCheck } from 'lucide-react';
import { formatKes, planMonthly, yearOne, kesPerUsd, aboutUsd, readChoice, clampFree, freeSchedule, fillFree } from '@/lib/pricingMath';
import './PayOptions.css';

/**
 * The two ways to pay on a pricing section (/hosts, /business): buy the
 * device, or pay monthly with a device add-on for its first 12 months. The
 * choice lives in the URL (?pay=monthly&device=pro) so it can be shared.
 * Used by the live (V2) pricing and the previous design's Pricing.jsx.
 */

const text = (section, key, fallback = '') => stripHtml(getContent(section, key, fallback));
const fill = (template, values) => String(template || '').replace(/\{(\w+)\}/g, (m, key) => (key in values ? values[key] : m));
// A line with its "(about $X)" placeholders filled: an empty one leaves no double space.
const tidy = (line) => line.replace(/\s{2,}/g, ' ').replace(/\s+([,.])/g, '$1').trim();

/** The section's free months (0 to 12): 0 when it has none, so the section looks as before. */
export const freeMonthsOf = (section) => clampFree(getContent(section, 'free_months', ''));

/**
 * The devices for a pricing section: its own devices.{i}.* rows if it has
 * any, else the shared ones (kept once on /pricing's devices section).
 */
export function useSectionDevices(section) {
    const { devices: shared = [] } = usePage().props;
    const own = extractItems(section, 'devices', ['id', 'name', 'image', 'coverage', 'capacity', 'best_for', 'price_kes', 'monthly_addon_kes', 'addon_months'])
        .filter((d) => stripHtml(d.name))
        .map((d) => {
            const kes = (v) => Number(String(stripHtml(v ?? '')).replace(/\D/g, '')) || 0;
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
                price_kes: kes(d.price_kes),
                monthly_kes: kes(d.monthly_addon_kes),
                months: kes(d.addon_months) || 12,
            };
        });
    return own.length ? own : shared.filter((d) => d.name && d.price_kes > 0);
}

/** The visitor's choice, read from and written back to the URL. */
export function usePayChoice(devices, freeMonths = 0) {
    const ids = devices.map((d) => d.id);
    const [choice, setChoice] = useState({ pay: 'buy', device: ids[0] || null });

    // After hydration, so the server render and the first client render match.
    useEffect(() => {
        if (typeof window !== 'undefined') setChoice(readChoice(window.location.search, ids));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ids.join(',')]);

    const update = useCallback((next) => {
        setChoice((current) => {
            const merged = { ...current, ...next };
            if (typeof window !== 'undefined') {
                const url = new URL(window.location.href);
                if (merged.pay === 'monthly') {
                    url.searchParams.set('pay', 'monthly');
                    if (merged.device) url.searchParams.set('device', merged.device);
                } else {
                    url.searchParams.delete('pay');
                    // With free months the device is picked on "buy" too.
                    if (freeMonths > 0 && merged.device) url.searchParams.set('device', merged.device);
                    else url.searchParams.delete('device');
                }
                window.history.replaceState(window.history.state, '', url);
            }
            return merged;
        });
    }, [freeMonths]);

    return {
        pay: choice.pay,
        device: devices.find((d) => d.id === choice.device) || devices[0] || null,
        setPay: (pay) => update({ pay }),
        setDevice: (device) => update({ device }),
    };
}

/**
 * A plan's price on the current choice: the main amount, its "(about $X)"
 * (from the plan's own conversion) and, on monthly, "for 12 months, then ...".
 */
export function planPrice(section, plan, pay, device, freeMonths = 0) {
    const planKes = Number(String(plan.price_kes ?? '').replace(/\D/g, '')) || 0;
    const free = clampFree(freeMonths);
    if (planKes && device && free > 0) return freePlanPrice(section, plan, planKes, pay, device, free);
    if (!planKes || pay !== 'monthly' || !device) return null;
    const monthly = planMonthly(planKes, pay, device);
    const rate = kesPerUsd(planKes, stripHtml(plan.unit));
    return {
        amount: formatKes(monthly),
        usd: aboutUsd(monthly, rate),
        then: fill(text(section, 'monthly_price_line', 'for {months} months, then {plan}/month'), { months: device.months, plan: formatKes(planKes) }),
    };
}

/** KES per dollar from the section's first plan with an "(about $X)". */
function sectionRate(section) {
    const plans = extractItems(section, 'plans', ['price_kes', 'unit']);
    for (const p of plans) {
        const rate = kesPerUsd(Number(String(p.price_kes ?? '').replace(/\D/g, '')) || 0, stripHtml(p.unit));
        if (rate) return rate;
    }
    return null;
}

/**
 * A plan's price while its first months are free:
 *  - buy:     "KES 0/month", for your first 3 months, then the plan, plus the device once;
 *  - monthly: "KES {add-on}/month", device only, then plan + add-on until the
 *             device is paid off, then the plan.
 * Every amount comes from the plan, the device and the free months, each
 * with its "(about $X)" from the plan's own conversion.
 */
function freePlanPrice(section, plan, planKes, pay, device, free) {
    // One rate for the whole section, so the same amount reads the same on every card.
    const rate = sectionRate(section) || kesPerUsd(planKes, stripHtml(plan.unit));
    const usd = (kes) => (kes > 0 ? aboutUsd(kes, rate) : '');
    const schedule = freeSchedule(planKes, pay, device, free);
    // "{plan}/month" becomes "KES 4,500/month (about $35)"; "{device_price}" "KES 42,000 (about $323)".
    const values = {
        'plan/month': `${formatKes(schedule.after)}/month ${usd(schedule.after)}`,
        'plan_addon/month': schedule.then ? `${formatKes(schedule.then)}/month ${usd(schedule.then)}` : '',
        plan: `${formatKes(schedule.after)} ${usd(schedule.after)}`,
        plan_addon: schedule.then ? `${formatKes(schedule.then)} ${usd(schedule.then)}` : '',
        device: device.name,
        device_price: `${formatKes(device.price_kes)} ${usd(device.price_kes)}`,
    };
    const line = (key, fallback) => tidy(fillFree(text(section, key, fallback), free)
        .replace(/\{(\w+)\}(\/month)?/g, (m, key, month) => {
            const k = month ? `${key}/month` : key;
            return k in values ? values[k] : (key in values ? `${values[key]}${month || ''}` : m);
        }));

    const lines = pay === 'monthly'
        ? [
            line('free_monthly_first', 'for your first {free_months} months, device only'),
            // The add-on ends within the free months: straight to the plan.
            schedule.then
                ? line('free_monthly_then', 'then {plan_addon}/month until the device is paid off, then {plan}/month')
                : line('free_buy_then', 'then {plan}/month'),
        ]
        : [
            line('free_first_line', 'for your first {free_months} months'),
            line('free_buy_then', 'then {plan}/month'),
            line('free_buy_device', 'plus {device}, {device_price} once'),
        ];

    return {
        amount: `${formatKes(schedule.now)}/month`,
        usd: usd(schedule.now),
        then: '',
        lines: lines.filter(Boolean),
        cta: tidy(fillFree(text(section, 'free_cta_label', 'Start with {free_months} months free'), free)),
    };
}

/** A small radio group operated with the arrow keys. */
function RadioGroup({ label, options, value, onChange, className, renderOption }) {
    const refs = useRef([]);
    const onKeyDown = (e, i) => {
        const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
        if (!step) return;
        e.preventDefault();
        const next = (i + step + options.length) % options.length;
        onChange(options[next].value);
        refs.current[next]?.focus();
    };

    return (
        <div role="radiogroup" aria-label={label} className={className}>
            {options.map((option, i) => {
                const checked = option.value === value;
                return (
                    <button
                        key={option.value}
                        ref={(el) => { refs.current[i] = el; }}
                        type="button"
                        role="radio"
                        aria-checked={checked}
                        tabIndex={checked ? 0 : -1}
                        onClick={() => onChange(option.value)}
                        onKeyDown={(e) => onKeyDown(e, i)}
                    >
                        {renderOption(option, checked)}
                    </button>
                );
            })}
        </div>
    );
}

/** Each way to pay's text while months are free (free_buy_text, free_monthly_text); '' otherwise. */
const freeText = (section, pay, freeMonths) => (freeMonths > 0 ? fillFree(text(section, `free_${pay}_text`), freeMonths) : '');

/** "Two ways to pay": the switch, the chosen option's terms and (on monthly) the device picker. */
export function PaySwitch({ section, devices, choice, site, freeMonths = 0 }) {
    if (!devices.length) return null;
    const options = [
        { value: 'buy', label: text(section, 'pay_buy_label', 'Buy the device'), badge: text(section, 'pay_buy_badge'), body: freeText(section, 'buy', freeMonths) || text(section, 'pay_buy_text'), terms: getContent(section, 'pay_buy_terms', '') },
        { value: 'monthly', label: text(section, 'pay_monthly_label', 'Pay monthly'), badge: text(section, 'pay_monthly_badge'), body: freeText(section, 'monthly', freeMonths) || text(section, 'pay_monthly_text'), terms: getContent(section, 'pay_monthly_terms', '') },
    ];
    const current = options.find((o) => o.value === choice.pay) || options[0];

    return (
        <div className="pay-options">
            <RadioGroup
                label={text(section, 'pay_label', 'Two ways to pay')}
                options={options}
                value={choice.pay}
                onChange={choice.setPay}
                className="pay-options__switch"
                renderOption={(o) => (
                    <>
                        <span className="pay-options__label">{o.label}</span>
                        {o.badge && <span className="pay-options__badge">{o.badge}</span>}
                    </>
                )}
            />
            <div className="pay-options__detail" aria-live="polite">
                {current.body && <p>{current.body}</p>}
                {textLines(current.terms, site).length > 0 && (
                    <ul>{textLines(current.terms, site).map((line, i) => <li key={i}>{line.text}</li>)}</ul>
                )}
            </div>
            {(choice.pay === 'monthly' || freeMonths > 0) && devices.length > 1 && (
                <RadioGroup
                    label="Device"
                    options={devices.map((d) => ({ value: d.id, label: d.name, addon: d.monthly_kes, price: d.price_kes }))}
                    value={choice.device?.id}
                    onChange={choice.setDevice}
                    className="pay-options__devices"
                    renderOption={(o) => (
                        <>
                            <span>{o.label}</span>
                            <small>{choice.pay === 'monthly' ? `+${formatKes(o.addon)}/mo` : `${formatKes(o.price)} once, installed`}</small>
                        </>
                    )}
                />
            )}
            {text(section, 'vat_note') && <p className="pay-options__vat">{text(section, 'vat_note')}</p>}
        </div>
    );
}

/** "Choose your TenaFi device": one card per device, with the small print. */
export function DevicesRow({ section, devices }) {
    if (!devices.length) return null;
    const priceLine = text(section, 'device_price_line', 'or +{addon}/month for {months} months');

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
                        <span className="pay-device__price">{formatKes(d.price_kes)}</span>
                        {d.monthly_kes > 0 && <span className="pay-device__monthly">{fill(priceLine, { addon: formatKes(d.monthly_kes), months: d.months })}</span>}
                    </li>
                ))}
            </ul>
            {text(section, 'devices_note') && <p className="pay-devices__note">{text(section, 'devices_note')}</p>}
        </div>
    );
}

/** The worked example, worked out from the plan and device it names. */
export function WorkedExample({ section, plans, devices, freeMonths = 0 }) {
    const plan = plans.find((p) => stripHtml(p.id) === text(section, 'example_plan'));
    const device = devices.find((d) => d.id === text(section, 'example_device'));
    const label = text(section, 'example_label');
    const planKes = Number(String(plan?.price_kes ?? '').replace(/\D/g, '')) || 0;
    if (!label || !planKes || !device) return null;

    const free = clampFree(freeMonths);
    const year = yearOne(planKes, device, free);
    const template = free > 0
        ? fillFree(text(section, 'free_example_text', '{label} pays {buy} in year one buying the device, or {monthly} paying monthly, with the first {free_months} months free. Buying saves {saving}.'), free)
        : text(section, 'example_text', '{label} pays {buy} in year one buying the device, or {monthly} paying monthly. Buying saves {saving}.');
    return (
        <p className="pay-example">
            {fill(template, {
                label, buy: formatKes(year.buy), monthly: formatKes(year.monthly), saving: formatKes(year.saving),
            })}
        </p>
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

/**
 * "Your first 3 months are free": above the two ways to pay, with the
 * guarantee (when filled in) and "See your WiFi page free first", which opens
 * this page's sign-up form with ?free_design=1.
 */
export function FreeBanner({ section, freeMonths, joinHref }) {
    if (!freeMonths) return null;
    const words = (key, fallback = '') => fillFree(text(section, key, fallback), freeMonths);
    const title = words('free_banner_title', 'Your first {free_months} months are free');
    const body = words('free_banner_text');
    const guarantee = text(section, 'guarantee_text');
    const cta = text(section, 'free_design_cta');
    const ctaText = text(section, 'free_design_text');
    const href = String(joinHref || '').includes('#') ? `${joinHref}?free_design=1` : '';

    return (
        <div className="pay-free">
            <div className="pay-free__offer">
                <h3 className="pay-free__title">{title}</h3>
                {body && <p className="pay-free__text">{body}</p>}
                {guarantee && (
                    <p className="pay-free__guarantee"><ShieldCheck size={16} aria-hidden="true" />{guarantee}</p>
                )}
            </div>
            {cta && href && (
                <div className="pay-free__design">
                    <a className="pay-free__cta" href={href}>{cta}</a>
                    {ctaText && <p className="pay-free__design-text">{ctaText}</p>}
                </div>
            )}
        </div>
    );
}
