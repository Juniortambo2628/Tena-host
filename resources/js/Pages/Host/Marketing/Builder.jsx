import React, { useEffect, useRef, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Mail, MessageCircle, MessageSquare, Users, Clock, Send, PenLine, ClipboardCheck, ArrowLeft, Loader2, Save,
    Wifi, CalendarClock, LogOut, CalendarDays, Gift, CheckCircle2, AlertTriangle, Smartphone,
} from 'lucide-react';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import PillButton from '@/Components/Dashboard/PillButton';
import { T, useTerms } from '@/Components/Dashboard/Terms';
import { csrfHeaders } from '@/Support/csrf';
import '../Properties/Splash.css';
import './Builder.css';

// WhatsApp falls back to SMS when it can't be delivered (see Messenger).
const CHANNELS = [
    { value: 'whatsapp', label: 'WhatsApp', Icon: MessageCircle, hint: 'Best reach in Kenya. Falls back to SMS.' },
    { value: 'sms', label: 'SMS', Icon: MessageSquare, hint: 'Works on every phone. Keep it short.' },
    { value: 'email', label: 'Email', Icon: Mail, hint: 'Room for detail, links and your branding.' },
];

const TRIGGERS = [
    { value: 'Guest Connects to WiFi', title: 'When they join the WiFi', after: 'when they join the WiFi', event: 'they join the WiFi', sub: 'Welcome offers, menus, upsells', Icon: Wifi },
    { value: '24 Hours Before Arrival', title: 'Before arrival', after: '24 hours before check-in', event: 'the 24-hours-before-arrival mark', sub: '24h before check-in (needs PMS bookings)', Icon: CalendarClock },
    { value: 'Day of Checkout', title: 'On check-out day', after: 'on check-out day', event: 'check-out', sub: 'Thank-you, return-stay offers', Icon: LogOut },
    { value: 'Custom Date', title: 'One-off broadcast', sub: 'Everyone in the audience, now or on a date', Icon: CalendarDays },
];

const DELAYS = ['Instant', '1 Hour', '6 Hours', '24 Hours', '3 Days', '7 Days'];

const SEGMENTS = [
    ['all_guests', 'Everyone', 'All guests who agreed to offers'],
    ['new_guests', 'First-timers', 'One visit so far'],
    ['returning_guests', 'Returning', 'Two or more visits'],
    ['vip_guests', 'VIPs', 'Your most frequent guests'],
];

const PLACEHOLDERS = [['{guest_name}', 'First name'], ['{property_name}', 'Property']];
const STOP_FOOTER = 'Reply STOP to opt out.';
const SAMPLE = { guest: 'Wanjiru' };

const steps = [
    { key: 'message', label: 'Message', icon: PenLine },
    { key: 'audience', label: 'Audience', icon: Users },
    { key: 'timing', label: 'Timing', icon: Clock },
    { key: 'review', label: 'Review', icon: ClipboardCheck },
];

// SMS is billed per segment: 160 GSM characters, or 70 once any emoji/accent appears.
function smsSegments(text) {
    const unicode = /[^\n\r\x20-\x7E£¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ¡ÄÖÑÜ§¿äöñüà€]/.test(text);
    const single = unicode ? 70 : 160;
    const multi = unicode ? 67 : 153;
    return { chars: text.length, segments: text.length <= single ? 1 : Math.ceil(text.length / multi), unicode };
}

function Field({ label, hint, error, children, count }) {
    return (
        <label className="studio-field">
            <span className="studio-field__label">{label}{count}</span>
            {children}
            {hint && !error && <small className="studio-field__hint">{hint}</small>}
            {error && <small className="studio-field__error" role="alert">{error}</small>}
        </label>
    );
}

function MessagePreview({ data, propertyName }) {
    const frame = useRef(null);
    const [status, setStatus] = useState('ready');
    const personal = (text) => text.replaceAll('{guest_name}', SAMPLE.guest).replaceAll('%FIRSTNAME%', SAMPLE.guest)
        .replaceAll('{property_name}', propertyName).replaceAll('%PROPERTY%', propertyName);

    useEffect(() => {
        if (data.type !== 'email') return;
        const controller = new AbortController();
        const timer = setTimeout(async () => {
            setStatus('loading');
            try {
                const res = await fetch(route('host.marketing.preview'), {
                    method: 'POST', signal: controller.signal, credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...csrfHeaders() },
                    body: JSON.stringify({ name: data.name, subject: data.subject, content: data.content, property_id: data.audience_property_id || null }),
                });
                if (!res.ok) throw new Error(String(res.status));
                if (frame.current) frame.current.srcdoc = await res.text();
                setStatus('ready');
            } catch (e) {
                if (e.name !== 'AbortError') setStatus('error');
            }
        }, 400);
        return () => { clearTimeout(timer); controller.abort(); };
    }, [data.type, data.name, data.subject, data.content, data.audience_property_id]);

    const body = personal(data.content || '');
    return (
        <div className="studio-preview">
            <div className="studio-preview__bar">
                <span className="campaign-preview__label">
                    {data.type === 'email' ? <Mail size={15} /> : <Smartphone size={15} />}
                    {data.type === 'email' ? 'Email inbox' : data.type === 'whatsapp' ? 'WhatsApp' : 'SMS'} · as {SAMPLE.guest} sees it
                </span>
                <span className={`studio-preview__status studio-preview__status--${status}`}>
                    {status === 'loading' ? <><Loader2 size={13} className="animate-spin" /> Updating</> : status === 'error' ? 'Preview unavailable' : 'Live preview'}
                </span>
            </div>
            <div className="studio-preview__stage">
                {data.type === 'email' ? (
                    <div className="studio-device studio-device--desktop campaign-email-frame">
                        <div className="campaign-email-frame__head">
                            <strong>{propertyName}</strong>
                            <span>{personal(data.subject || data.name || 'Your subject line')}</span>
                        </div>
                        <iframe ref={frame} title="Email preview" />
                    </div>
                ) : (
                    <div className={`campaign-phone campaign-phone--${data.type}`}>
                        <div className="campaign-phone__top">
                            <span className="campaign-phone__avatar">{propertyName.slice(0, 1)}</span>
                            <span><strong>{propertyName}</strong><small>{data.type === 'whatsapp' ? 'Business account' : 'Text message'}</small></span>
                        </div>
                        <div className="campaign-phone__thread">
                            <div className="campaign-bubble">
                                {body ? <p>{body}</p> : <p className="campaign-bubble__empty">Your message appears here as you type.</p>}
                                <p className="campaign-bubble__stop">{STOP_FOOTER}</p>
                                <span className="campaign-bubble__time">09:41{data.type === 'whatsapp' && ' ✓✓'}</span>
                            </div>
                        </div>
                    </div>
                )}
            </div>
            <p className="studio-preview__note">Placeholders are filled per guest. Links are tracked so you can see clicks.</p>
        </div>
    );
}

export default function MarketingBuilder({ campaign, properties = [], splashOffers = [], channels = {} }) {
    const t = useTerms();
    const isEditing = !!campaign;
    const isActive = campaign?.status === 'active';
    const [step, setStep] = useState('message');
    const [reach, setReach] = useState(null);
    const content = useRef(null);

    const { data, setData, transform, post, put, processing, errors, isDirty } = useForm({
        name: campaign?.name || '',
        type: campaign?.type || 'whatsapp',
        subject: campaign?.subject || '',
        content: campaign?.content || '',
        trigger_event: campaign?.trigger_event || 'Guest Connects to WiFi',
        trigger_delay: campaign?.trigger_delay || 'Instant',
        target_audience: campaign?.target_audience || 'all_guests',
        audience_property_id: campaign?.audience_property_id || '',
        audience_from: campaign?.audience_from?.slice(0, 10) || '',
        audience_to: campaign?.audience_to?.slice(0, 10) || '',
        scheduled_at: campaign?.scheduled_at?.slice(0, 16) || '',
    });

    // Live "Estimated reach": the same audience query sending uses.
    useEffect(() => {
        const timer = setTimeout(() => {
            window.axios.get(route('host.marketing.estimate'), {
                params: {
                    type: data.type, target_audience: data.target_audience,
                    audience_property_id: data.audience_property_id || null,
                    audience_from: data.audience_from || null, audience_to: data.audience_to || null,
                },
            }).then(({ data: res }) => setReach(res.count)).catch(() => setReach(null));
        }, 300);
        return () => clearTimeout(timer);
    }, [data.type, data.target_audience, data.audience_property_id, data.audience_from, data.audience_to]);

    const insert = (token) => {
        const el = content.current;
        const start = el?.selectionStart ?? data.content.length;
        const end = el?.selectionEnd ?? data.content.length;
        setData('content', data.content.slice(0, start) + token + data.content.slice(end));
        requestAnimationFrame(() => { el?.focus(); el?.setSelectionRange(start + token.length, start + token.length); });
    };

    const applyOffer = (offer) => {
        const line = [offer.title, offer.detail, offer.when && `(${offer.when})`].filter(Boolean).join(' – ');
        setData(d => ({
            ...d,
            name: d.name || offer.title,
            subject: d.subject || offer.title,
            audience_property_id: d.audience_property_id || offer.property_id,
            content: d.content || `Hi {guest_name}! ${line}. Reply to this message to book with {property_name}.`,
        }));
    };

    const save = (launch) => {
        transform(d => ({
            ...d,
            audience_property_id: d.audience_property_id || null,
            scheduled_at: d.trigger_event === 'Custom Date' ? (d.scheduled_at || null) : null,
            launch,
        }));
        const options = { preserveScroll: true };
        isEditing ? put(route('host.marketing.update', campaign.id), options) : post(route('host.marketing.store'), options);
    };

    const propertyName = properties.find(p => String(p.id) === String(data.audience_property_id))?.name || properties[0]?.name || 'Your property';
    const sms = smsSegments(`${data.content}\n${STOP_FOOTER}`);
    const isBroadcast = data.trigger_event === 'Custom Date';
    const timing = isBroadcast
        ? (data.scheduled_at ? `Sends once on ${new Date(data.scheduled_at).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' })}` : 'Sends once, as soon as you launch')
        : (() => {
            const trigger = TRIGGERS.find(x => x.value === data.trigger_event);
            return data.trigger_delay === 'Instant'
                ? `Each guest gets it ${trigger?.after}`
                : `Each guest gets it ${data.trigger_delay.toLowerCase()} after ${trigger?.event}`;
        })();
    const checks = [
        [!!data.name.trim(), 'Campaign has a name'],
        [!!data.content.trim(), 'Message written'],
        [data.type !== 'email' || !!(data.subject || data.name).trim(), 'Email has a subject'],
        [channels[data.type] !== false, channels[data.type] === false ? `${data.type === 'sms' ? 'SMS' : 'WhatsApp'} isn't connected yet: messages won't send` : 'Channel is connected'],
        [(reach ?? 0) > 0 || !isBroadcast, isBroadcast ? `${reach ?? '…'} guests will receive it` : 'Reaches guests as they qualify'],
    ];
    const stepErrors = { message: ['name', 'type', 'subject', 'content'], audience: ['target_audience', 'audience_property_id', 'audience_from', 'audience_to'], timing: ['trigger_event', 'trigger_delay', 'scheduled_at'], review: [] };
    const index = steps.findIndex(s => s.key === step);

    const sectionBody = {
        message: (
            <>
                <header className="studio-section__head"><div><h2>Write your message</h2><p>Pick a channel, then write it the way you'd text a guest. Friendly and short wins.</p></div></header>
                <div className="campaign-channels" role="radiogroup" aria-label="Channel">
                    {CHANNELS.map(({ value, label, Icon, hint }) => (
                        <button key={value} type="button" role="radio" aria-checked={data.type === value} onClick={() => setData('type', value)} className="campaign-channel">
                            <Icon size={20} />
                            <strong>{label}</strong>
                            <small>{hint}</small>
                            {channels[value] === false && <em><AlertTriangle size={12} /> Not connected</em>}
                        </button>
                    ))}
                </div>
                {splashOffers.length > 0 && (
                    <div className="studio-picks">
                        <p>Start from an offer on your splash page</p>
                        <div className="campaign-offers">
                            {splashOffers.map((o, i) => (
                                <button key={i} type="button" onClick={() => applyOffer(o)}>
                                    <Gift size={15} /><span><strong>{o.title}</strong><small>{o.property}{o.when ? ` · ${o.when}` : ''}</small></span>
                                </button>
                            ))}
                        </div>
                    </div>
                )}
                <Field label="Campaign name" hint="Only you see this." error={errors.name}>
                    <input type="text" maxLength={255} value={data.name} placeholder="e.g. Return-stay discount" onChange={e => setData('name', e.target.value)} />
                </Field>
                {data.type === 'email' && (
                    <Field label="Subject line" hint="Leave empty to use the campaign name." error={errors.subject} count={<em>{data.subject.length}/120</em>}>
                        <input type="text" maxLength={255} value={data.subject} placeholder="{guest_name}, your next stay is 20% off" onChange={e => setData('subject', e.target.value)} />
                    </Field>
                )}
                <Field label="Message" error={errors.content}
                    count={data.type === 'email' ? <em>{data.content.length}/5000</em> : <em>{sms.chars} chars · {data.type === 'sms' ? `${sms.segments} SMS` : `${sms.segments} SMS if it falls back`}{sms.unicode ? ' · emoji/accents' : ''}</em>}
                    hint={data.type === 'email' ? 'Plain text: links are clickable and line breaks are kept. An unsubscribe link is added for you.' : `“${STOP_FOOTER}” is added at the end for you.`}>
                    <textarea ref={content} rows={data.type === 'email' ? 9 : 5} maxLength={5000} value={data.content}
                        placeholder={'Hi {guest_name}! Thanks for staying at {property_name}. Book your next visit direct this month and get 20% off: https://wa.me/2547…'}
                        onChange={e => setData('content', e.target.value)} />
                </Field>
                <div className="campaign-tokens">
                    <span>Insert</span>
                    {PLACEHOLDERS.map(([token, label]) => <button key={token} type="button" onClick={() => insert(token)}>{label}</button>)}
                </div>
            </>
        ),
        audience: (
            <>
                <header className="studio-section__head"><div><h2>Who gets it?</h2><p>Only guests who agreed to offers and haven't replied STOP are ever included.</p></div></header>
                <div className="campaign-segments" role="radiogroup" aria-label="Segment">
                    {SEGMENTS.map(([value, label, sub]) => (
                        <button key={value} type="button" role="radio" aria-checked={data.target_audience === value} onClick={() => setData('target_audience', value)}>
                            <strong>{t(label)}</strong><small>{t(sub)}</small>
                        </button>
                    ))}
                </div>
                <Field label={<T>Property</T>} error={errors.audience_property_id}>
                    <select value={data.audience_property_id} onChange={e => setData('audience_property_id', e.target.value)} className="campaign-select">
                        <option value="">{t('All properties')}</option>
                        {properties.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
                    </select>
                </Field>
                <div className="studio-grid-2">
                    <Field label="First seen from" error={errors.audience_from}><input type="date" value={data.audience_from} onChange={e => setData('audience_from', e.target.value)} className="campaign-date" /></Field>
                    <Field label="First seen until" error={errors.audience_to}><input type="date" value={data.audience_to} onChange={e => setData('audience_to', e.target.value)} className="campaign-date" /></Field>
                </div>
                <div className="campaign-reach">
                    <span>Estimated reach</span>
                    <strong>{reach === null ? '…' : reach.toLocaleString()}</strong>
                    <small>{t('guests')} with {data.type === 'email' ? 'an email address' : 'a phone number'} matching these filters right now</small>
                </div>
            </>
        ),
        timing: (
            <>
                <header className="studio-section__head"><div><h2>When does it send?</h2><p>Automations run on their own for every new guest. Broadcasts go out once.</p></div></header>
                <div className="campaign-triggers" role="radiogroup" aria-label="Trigger">
                    {TRIGGERS.map(({ value, title, sub, Icon }) => (
                        <button key={value} type="button" role="radio" aria-checked={data.trigger_event === value} onClick={() => setData('trigger_event', value)}>
                            <Icon size={18} /><strong>{title}</strong><small>{sub}</small>
                        </button>
                    ))}
                </div>
                {isBroadcast ? (
                    <Field label="Send on" hint="Leave empty to send as soon as you launch." error={errors.scheduled_at}>
                        <input type="datetime-local" value={data.scheduled_at} onChange={e => setData('scheduled_at', e.target.value)} className="campaign-date" />
                    </Field>
                ) : (
                    <Field label="Wait after the trigger" error={errors.trigger_delay}>
                        <div className="studio-cats" role="radiogroup">
                            {DELAYS.map(d => <button key={d} type="button" role="radio" aria-checked={data.trigger_delay === d} onClick={() => setData('trigger_delay', d)}>{d}</button>)}
                        </div>
                    </Field>
                )}
                <div className="campaign-reach campaign-reach--soft"><span>Schedule</span><strong className="campaign-reach__text">{timing}</strong></div>
            </>
        ),
        review: (
            <>
                <header className="studio-section__head"><div><h2>Ready to go?</h2><p>{isActive ? 'This campaign is live. Saving updates it for guests who haven\'t received it yet.' : 'Launch now, or save a draft and come back.'}</p></div></header>
                <ul className="campaign-checks">
                    {checks.map(([ok, label], i) => <li key={i} className={ok ? 'is-ok' : 'is-warn'}>{ok ? <CheckCircle2 size={16} /> : <AlertTriangle size={16} />} {label}</li>)}
                </ul>
                <dl className="campaign-summary">
                    <div><dt>Channel</dt><dd>{CHANNELS.find(c => c.value === data.type)?.label}</dd></div>
                    <div><dt>Audience</dt><dd>{SEGMENTS.find(s => s[0] === data.target_audience)?.[1]} · {data.audience_property_id ? propertyName : t('All properties')}</dd></div>
                    <div><dt>Timing</dt><dd>{timing}</dd></div>
                </dl>
            </>
        ),
    };

    return (
        <>
            <Head title={isEditing ? `Edit ${campaign.name}` : 'Campaign Studio'} />
            <DashboardHero
                title={isEditing ? campaign.name : 'Campaign Studio'}
                breadcrumbs={[{ label: 'Marketing', href: route('host.marketing.index') }, { label: isEditing ? 'Edit campaign' : 'New campaign' }]}
                actions={[
                    { label: 'Back to campaigns', icon: <ArrowLeft size={16} />, href: route('host.marketing.index') },
                    { label: isActive ? 'Save changes' : 'Save draft', icon: <Save size={16} />, onClick: () => save(false) },
                ]}
            />

            <div className="studio">
                <div className="studio-editor">
                    <nav className="studio-steps" aria-label="Campaign steps">
                        {steps.map(({ key, label, icon: Icon }) => (
                            <button key={key} type="button" aria-current={step === key ? 'step' : undefined} onClick={() => setStep(key)}
                                className={stepErrors[key].some(f => errors[f]) ? 'has-error' : ''}>
                                <Icon size={16} /><span>{label}</span>
                            </button>
                        ))}
                    </nav>
                    <section className="studio-section">{sectionBody[step]}</section>
                    <footer className="studio-footer">
                        <PillButton variant="secondary" onClick={() => setStep(steps[Math.max(0, index - 1)].key)} disabled={index === 0}>Back</PillButton>
                        <span className={`studio-dirty ${isDirty ? 'is-on' : ''}`}>{isDirty ? 'Unsaved changes' : isEditing ? 'All changes saved' : 'Not saved yet'}</span>
                        {index < steps.length - 1
                            ? <PillButton variant="secondary" onClick={() => setStep(steps[index + 1].key)}>Next: {steps[index + 1].label}</PillButton>
                            : <PillButton variant="primary" onClick={() => save(!isActive)} disabled={processing}>
                                {processing ? <Loader2 size={14} className="animate-spin" /> : <Send size={14} />} {isActive ? 'Save changes' : isBroadcast && !data.scheduled_at ? 'Launch & send' : 'Launch'}
                            </PillButton>}
                    </footer>
                </div>
                <MessagePreview data={data} propertyName={propertyName} />
            </div>
        </>
    );
}
