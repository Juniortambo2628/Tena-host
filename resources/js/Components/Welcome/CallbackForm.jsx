import React, { useState } from 'react';
import { usePage } from '@inertiajs/react';
import { CheckCircle2, Loader2 } from 'lucide-react';
import { getContent, stripHtml, textLines } from '@/lib/cms';
import { track } from '@/lib/analytics';
import { usePublic } from '@/Components/Public/PublicContext';
import './CallbackForm.css';

/** "hosts|I run rentals" lines (the CMS's value|Label convention). */
const parseOptions = (text) => textLines(text, {}).map(({ text: line }) => {
    const [value, label] = line.split('|').map(p => p.trim());
    return { value, label: label || value };
});

/**
 * "Get a call back": a +254 number and what the visitor runs, posted to
 * POST /api/callbacks. Shown inside a hero whose callback_title is set,
 * and only while Admin -> Settings has the call-back form switched on.
 */
export default function CallbackForm({ section }) {
    const { callbacks } = usePage().props;
    const { page } = usePublic();
    const options = parseOptions(getContent(section, 'callback_options', ''));
    const [phone, setPhone] = useState('');
    const [kind, setKind] = useState(options[0]?.value || 'hosts');
    const [status, setStatus] = useState('idle');
    const [error, setError] = useState('');

    const title = stripHtml(getContent(section, 'callback_title', ''));
    if (!callbacks || !title) return null;

    const submit = async (e) => {
        e.preventDefault();
        setStatus('sending');
        setError('');
        try {
            await window.axios.post('/api/callbacks', { phone, kind, page: page?.slug });
            track('callback_submit', page?.slug);
            setStatus('done');
        } catch (err) {
            setStatus('idle');
            setError(err.response?.data?.errors?.phone?.[0] || err.response?.data?.message || 'Something went wrong. Please try again, or WhatsApp us.');
        }
    };

    if (status === 'done') {
        return (
            <div className="callback-form callback-form--done" role="status">
                <CheckCircle2 size={22} aria-hidden="true" />
                <p>{stripHtml(getContent(section, 'callback_thanks', 'Thanks! We’ll be in touch.'))}</p>
            </div>
        );
    }

    const link = stripHtml(getContent(section, 'callback_link', ''));

    return (
        <form className="callback-form" onSubmit={submit} noValidate>
            <b className="callback-form__title">{title}</b>
            <div className="callback-form__row">
                <label className="callback-form__phone">
                    <span className="sr-only">WhatsApp or mobile number</span>
                    <span className="callback-form__prefix" aria-hidden="true">+254</span>
                    <input
                        type="tel" inputMode="tel" autoComplete="tel-national" placeholder="7XX XXX XXX" required
                        value={phone} onChange={e => setPhone(e.target.value)}
                        aria-invalid={error ? 'true' : undefined} aria-describedby={error ? 'callback-error' : undefined}
                    />
                </label>
                <label className="callback-form__kind">
                    <span className="sr-only">What do you run?</span>
                    <select value={kind} onChange={e => setKind(e.target.value)}>
                        {options.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
                    </select>
                </label>
                <button type="submit" disabled={status === 'sending'}>
                    {status === 'sending' && <Loader2 size={16} className="animate-spin" aria-hidden="true" />}
                    {stripHtml(getContent(section, 'callback_button', 'Call me back'))}
                </button>
            </div>
            {error && <p className="callback-form__error" id="callback-error" role="alert">{error}</p>}
            <p className="callback-form__note">
                {stripHtml(getContent(section, 'callback_note', ''))}{' '}
                {link && <a href={link}>{stripHtml(getContent(section, 'callback_link_label', 'Or book your slot now'))} →</a>}
            </p>
        </form>
    );
}
