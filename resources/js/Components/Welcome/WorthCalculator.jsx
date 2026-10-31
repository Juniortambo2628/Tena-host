import React, { useMemo, useState } from 'react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, stripHtml } from '@/lib/cms';
import './WorthCalculator.css';

const kes = (v) => `KES ${Math.round(v).toLocaleString('en-KE')}`;
const num = (v) => Math.round(v).toLocaleString('en-KE');
const pct = (v) => `${+v.toFixed(1)}%`;

/**
 * Each mode: its inputs, what they produce, and the note listing every
 * assumption. Assumptions are percentages or plain numbers from the
 * admin (worth_calculator section), so the note always shows what's used.
 */
const MODES = {
    hosts: {
        inputs: [
            { id: 'units', label: 'Units', min: 1, max: 50, step: 1, value: 3, fmt: num },
            { id: 'rate', label: 'Average nightly rate', min: 2000, max: 40000, step: 500, value: 7000, fmt: kes },
            { id: 'occ', label: 'Occupancy', min: 10, max: 95, step: 1, value: 44, fmt: v => `${v}%` },
        ],
        calc: (v, a) => {
            const nights = v.units * 365 * v.occ / 100;
            const stays = nights / a.hosts_avg_stay;
            const list = stays * a.hosts_guests_per_stay * a.hosts_join_rate / 100;
            const direct = nights * a.hosts_direct_share / 100 * v.rate;
            return [
                { v: num(list), l: 'guests on your list in a year' },
                { v: num(stays), l: 'review requests sent, one per stay' },
                { v: kes(direct), l: `direct-booking revenue, if ${pct(a.hosts_direct_share)} of nights come back direct`, hi: true },
                { v: kes(direct * a.hosts_ota_fee / 100), l: 'OTA fees you keep on those nights' },
            ];
        },
        note: a => `Assumes stays of about ${a.hosts_avg_stay} nights, ${a.hosts_guests_per_stay} guests per stay, ${pct(a.hosts_join_rate)} of guests joining your list, ${pct(a.hosts_direct_share)} of nights rebooked direct and a ${pct(a.hosts_ota_fee)} OTA fee. Direct bookings usually start from month 2 or 3.`,
    },
    business: {
        inputs: [
            { id: 'cust', label: 'Customers a day', min: 10, max: 500, step: 5, value: 60, fmt: num },
            { id: 'spend', label: 'Average spend', min: 200, max: 10000, step: 100, value: 600, fmt: kes },
        ],
        calc: (v, a) => {
            const perMonth = v.cust * a.business_open_days * a.business_join_rate / 100;
            const list3 = perMonth * 3;
            const reviews = perMonth * a.business_review_rate / 100;
            // The list grows each month; a share of it uses an offer each month.
            const redeemed = (perMonth + perMonth * 2 + list3) * a.business_offer_use / 100;
            return [
                { v: num(list3), l: 'customers on your list after 3 months' },
                { v: num(reviews), l: 'new Google reviews a month' },
                { v: num(redeemed), l: 'offers used in the first 3 months' },
                { v: kes(redeemed * v.spend), l: 'spent on those return visits', hi: true },
            ];
        },
        note: a => `Assumes ${a.business_open_days} open days a month, ${pct(a.business_join_rate)} of customers joining, about ${pct(a.business_review_rate)} of review invites becoming reviews, and ${pct(a.business_offer_use)} of your list using an offer each month.`,
    },
    events: {
        inputs: [
            { id: 'att', label: 'Attendees per event', min: 50, max: 30000, step: 50, value: 500, fmt: num },
            { id: 'n', label: 'Events a year', min: 1, max: 52, step: 1, value: 4, fmt: num },
        ],
        calc: (v, a) => {
            const seen = v.att * a.events_connect_rate / 100;
            const contacts = seen * a.events_join_rate / 100;
            return [
                { v: num(contacts), l: 'attendee contacts per event' },
                { v: num(contacts * v.n), l: 'attendee contacts a year', hi: true },
                { v: num(seen * v.n), l: 'sponsor views on the WiFi page a year' },
                { v: num(contacts * v.n), l: 'thank-you and early-bird invites sent' },
            ];
        },
        note: a => `Assumes about ${pct(a.events_connect_rate)} of attendees connect to the WiFi and ${pct(a.events_join_rate)} of them join your list. Busy outdoor events may be lower.`,
    },
};

/** Typical rates; each can be changed in the admin. */
export const DEFAULT_ASSUMPTIONS = {
    hosts_avg_stay: 2.8, hosts_guests_per_stay: 1.7, hosts_join_rate: 67, hosts_direct_share: 5, hosts_ota_fee: 15,
    business_open_days: 26, business_join_rate: 25, business_review_rate: 4, business_offer_use: 5,
    events_connect_rate: 50, events_join_rate: 60,
};

/**
 * "What's it worth to you?": sliders in, estimates out, for rentals,
 * businesses and events (section type "worth_calculator", reusable on any
 * page). `mode` shows one calculator only; empty shows all three as tabs.
 * Results are always labelled as an estimate with the assumptions used.
 */
export default function WorthCalculator({ section }) {
    const only = stripHtml(getContent(section, 'mode', ''));
    const modes = MODES[only] ? [only] : Object.keys(MODES);
    const [mode, setMode] = useState(modes[0]);
    const [vals, setVals] = useState(() => Object.fromEntries(Object.values(MODES).flatMap(m => m.inputs.map(i => [i.id, i.value]))));

    const assumptions = useMemo(() => Object.fromEntries(Object.entries(DEFAULT_ASSUMPTIONS).map(([key, fallback]) => {
        const value = parseFloat(stripHtml(getContent(section, key, '')));
        return [key, Number.isFinite(value) && value >= 0 ? value : fallback];
    })), [section]);

    if (!section) return null;
    const m = MODES[mode];
    const results = m.calc(vals, assumptions);
    const text = (key, fallback = '') => stripHtml(getContent(section, key, fallback));

    return (
        <SectionWrapper bg={section.bg || 'white'}>
            <SectionHeader badge={text('badge')} title={text('title')} subtitle={getContent(section, 'subtitle', '')} />
            <div className="worth">
                {modes.length > 1 && (
                    <div className="worth__tabs" role="tablist" aria-label="Calculator">
                        {modes.map(key => (
                            <button key={key} type="button" role="tab" aria-selected={mode === key} onClick={() => setMode(key)}>
                                {text(`tab_${key}`, key)}
                            </button>
                        ))}
                    </div>
                )}
                <div className="worth__body" role="tabpanel">
                    <div className="worth__inputs">
                        {m.inputs.map(input => (
                            <label key={input.id}>
                                <span className="worth__label"><span>{input.label}</span><output>{input.fmt(vals[input.id])}</output></span>
                                <input type="range" min={input.min} max={input.max} step={input.step} value={vals[input.id]}
                                    onChange={e => setVals(v => ({ ...v, [input.id]: +e.target.value }))} />
                            </label>
                        ))}
                    </div>
                    <div className="worth__outputs" aria-live="polite">
                        {results.map((r, i) => (
                            <div key={i} className={r.hi ? 'is-hi' : ''}><b>{r.v}</b><span>{r.l}</span></div>
                        ))}
                        <p className="worth__note"><strong>{text('estimate_label', 'Estimate only.')}</strong> {m.note(assumptions)}</p>
                    </div>
                </div>
            </div>
        </SectionWrapper>
    );
}
