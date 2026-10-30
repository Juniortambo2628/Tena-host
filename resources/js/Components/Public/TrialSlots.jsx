import React, { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { getText } from '@/lib/cms';
import { usePublic } from './PublicContext';
import './TrialSlots.css';

const fill = (text, values) => text.replace(/\{(taken|total|left)\}/g, (_, key) => String(values[key]));

/**
 * "20 of 50 free trial slots taken". The numbers are real trial bookings
 * (App\Support\TrialSlots); the wording is Site-wide → Free trial slot counter.
 * Renders nothing until a total is set in Admin → Settings.
 */
export default function TrialSlots({ tone = 'light', className = '' }) {
    const { trial } = usePage().props;
    const { site } = usePublic();
    const [grown, setGrown] = useState(false);
    useEffect(() => { const id = requestAnimationFrame(() => setGrown(true)); return () => cancelAnimationFrame(id); }, []);

    if (!trial?.total) return null;

    const copy = site.trial_slots;
    const full = trial.left <= 0;
    const main = fill(full ? getText(copy, 'full_text', 'All {total} free trial slots taken') : getText(copy, 'taken_text', '{taken} of {total} free trial slots taken'), trial);
    const sub = full
        ? getText(copy, 'full_note', 'WhatsApp us to join the waitlist')
        : fill(trial.left === 1 ? getText(copy, 'last_text', 'Only 1 slot left') : getText(copy, 'left_text', '{left} slots left'), trial);
    const pct = Math.round((trial.taken / trial.total) * 100);

    return (
        <div className={`trial-slots trial-slots--${tone} ${className}`}>
            <div className="trial-slots__top">
                <b>{main}</b>
                <span>{sub}</span>
            </div>
            <div className="trial-slots__bar" role="progressbar" aria-valuemin={0} aria-valuemax={trial.total} aria-valuenow={trial.taken} aria-label={main}>
                <span style={{ width: grown ? `${pct}%` : 0 }} />
            </div>
        </div>
    );
}
