import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Palette, Heart, House, Compass, Star, Gift, Building2, Plus, Trash2, ChevronUp, ChevronDown,
    Smartphone, Monitor, ExternalLink, Loader2, Save, ImagePlus, Wifi, CheckCircle2, PhoneCall, Megaphone,
} from 'lucide-react';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import PillButton from '@/Components/Dashboard/PillButton';
import { notify } from '@/Components/Toast';
import { csrfHeaders } from '@/Support/csrf';
import { TemplatePicker, AccentPicker } from '@/Components/Properties/SplashEditor';
import { normalizeTemplate, originalTemplates, templateDefaults, applyTemplate, recommendedTemplate, findTemplate, tileIcons, nameAndPhoneOnly } from '@/Components/Properties/splashTemplates';
import './Splash.css';
import { BRAND_COLORS } from '@/constants';

const categories = [
    ['food', 'Eat'], ['drink', 'Drink'], ['sights', 'See'], ['shop', 'Shop'],
    ['nature', 'Outdoors'], ['transport', 'Getting around'], ['health', 'Health'], ['other', 'Local tip'],
];

// Each step of the studio, and the splash tab it shows in the preview.
const steps = [
    { key: 'look', label: 'Look', icon: Palette, tab: 'wifi' },
    { key: 'welcome', label: 'Welcome', icon: Heart, tab: 'welcome' },
    { key: 'house', label: 'House info', icon: House, tab: 'house' },
    { key: 'nearby', label: 'Nearby', icon: Compass, tab: 'nearby' },
    { key: 'review', label: 'Reviews', icon: Star, tab: 'welcome' },
    { key: 'offers', label: 'Offers', icon: Gift, tab: 'offers' },
    { key: 'stays', label: 'Cross-sell', icon: Building2, tab: 'stays' },
];

const blank = {
    emergency: { label: '', phone: '' },
    nearby: { name: '', category: 'food', note: '', distance: '', url: '' },
    offers: { title: '', detail: '', when: '' },
    stays: { property_id: '', title: '', detail: '', price: '', url: '' },
};

const tileIconLabels = {
    menu: 'Menu', list: 'List', cal: 'Calendar', gift: 'Gift', tag: 'Deal', star: 'Star', cart: 'Cart', clock: 'Clock', guide: 'Guide', home: 'Home', wifi: 'WiFi',
};

function initialBranding(saved = {}, business = false) {
    const template = normalizeTemplate(saved.template);
    // Mirrors SplashDesign::forProperty: older designs keep their old fallbacks.
    const defaults = originalTemplates.includes(template) ? {} : templateDefaults(template, business);
    const rental = !(business && !originalTemplates.includes(template));
    const sections = { welcome: true, rules: rental, emergency: rental, nearby: rental, review: true, offers: true, stays: rental, ...(saved.sections || {}) };
    return {
        template,
        fields: nameAndPhoneOnly.includes(template) ? { email: false, birthday: false } : { email: true, birthday: business, ...(defaults.fields || {}), ...(saved.fields || {}) },
        opt_in: saved.opt_in || '',
        tiles: business ? (saved.tiles || defaults.tiles || []).map(t => ({ icon: 'star', label: '', url: '', ...t, url: t.url || '' })) : [],
        accent: saved.accent || BRAND_COLORS.primary,
        heading: saved.heading || '', message: saved.message || '', button: saved.button || '',
        welcome_note: saved.welcome_note || '', host_name: saved.host_name || '', review_prompt: saved.review_prompt || '',
        sections,
        house_rules: saved.house_rules || [],
        emergency: (saved.emergency || []).map(r => ({ ...blank.emergency, ...r })),
        nearby: (saved.nearby || []).map(r => ({ ...blank.nearby, ...r })),
        offers: (saved.offers || []).map(r => ({ ...blank.offers, ...r })),
        stays: (saved.stays || []).map(r => ({ ...blank.stays, ...r, property_id: r.property_id ?? '' })),
    };
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

function Toggle({ checked, onChange, label }) {
    return (
        <button type="button" role="switch" aria-checked={checked} className="studio-switch" onClick={() => onChange(!checked)}>
            <span className="studio-switch__track"><span /></span>
            <span>{label}</span>
        </button>
    );
}

// A reorderable list of small cards, used for rules, contacts, places, offers and stays.
function RowList({ rows, onChange, render, addLabel, onAdd, max, empty }) {
    const move = (i, step) => {
        const next = [...rows];
        [next[i], next[i + step]] = [next[i + step], next[i]];
        onChange(next);
    };
    return (
        <div className="studio-rows">
            {rows.length === 0 && <p className="studio-empty">{empty}</p>}
            {rows.map((row, i) => (
                <div className="studio-row" key={i}>
                    <div className="studio-row__body">{render(row, i, value => onChange(rows.map((r, j) => (j === i ? value : r))))}</div>
                    <div className="studio-row__tools">
                        <button type="button" onClick={() => move(i, -1)} disabled={i === 0} aria-label="Move up"><ChevronUp size={16} /></button>
                        <button type="button" onClick={() => move(i, 1)} disabled={i === rows.length - 1} aria-label="Move down"><ChevronDown size={16} /></button>
                        <button type="button" onClick={() => onChange(rows.filter((_, j) => j !== i))} aria-label="Remove"><Trash2 size={16} /></button>
                    </div>
                </div>
            ))}
            {rows.length < max && (
                <button type="button" className="studio-add" onClick={onAdd}><Plus size={16} /> {addLabel}</button>
            )}
        </div>
    );
}

function SectionHead({ title, description, sectionKey, branding, setSection, extra }) {
    return (
        <header className="studio-section__head">
            <div>
                <h2>{title}</h2>
                <p>{description}</p>
            </div>
            {sectionKey && <Toggle checked={!!branding.sections[sectionKey]} onChange={v => setSection(sectionKey, v)} label={branding.sections[sectionKey] ? 'Shown' : 'Hidden'} />}
            {extra}
        </header>
    );
}

function Preview({ propertyId, data, focusTab, coverFile, logoFile }) {
    const frame = useRef(null);
    const box = useRef(null);
    const [device, setDevice] = useState('phone');
    const [page, setPage] = useState('splash');
    const [status, setStatus] = useState('loading');
    const [scale, setScale] = useState(1);
    const size = device === 'phone' ? { w: 390, h: 800 } : { w: 1440, h: 900 };
    const urls = useMemo(() => ({
        cover: coverFile ? URL.createObjectURL(coverFile) : null,
        logo: logoFile ? URL.createObjectURL(logoFile) : null,
    }), [coverFile, logoFile]);
    useEffect(() => () => { urls.cover && URL.revokeObjectURL(urls.cover); urls.logo && URL.revokeObjectURL(urls.logo); }, [urls]);

    useEffect(() => {
        if (!box.current) return;
        const ro = new ResizeObserver(([entry]) => setScale(Math.min(1, entry.contentRect.width / size.w)));
        ro.observe(box.current);
        return () => ro.disconnect();
    }, [size.w]);

    // Unsaved photos never reach the server; swap them into the rendered page instead.
    const decorate = () => {
        const doc = frame.current?.contentDocument;
        if (!doc) return;
        if (urls.cover) doc.querySelectorAll('[data-sp-cover]').forEach(el => { el.style.backgroundImage = `url("${urls.cover}")`; el.classList.remove('sp-photo--empty'); });
        if (urls.logo) doc.querySelectorAll('[data-sp-logo]').forEach(el => {
            const old = el.querySelector('.sp-brand__logo, .sp-brand__mono');
            const img = doc.createElement('img');
            img.className = 'sp-brand__logo';
            img.src = urls.logo;
            img.alt = '';
            old ? old.replaceWith(img) : el.prepend(img);
        });
        (doc.querySelector(`[data-tab="${focusTab}"]`) || doc.querySelector('[data-tab="wifi"]'))?.click();
    };

    useEffect(() => {
        const controller = new AbortController();
        const timer = setTimeout(async () => {
            setStatus('loading');
            try {
                const res = await fetch(route('host.properties.splash-preview', propertyId), {
                    method: 'POST', signal: controller.signal, credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...csrfHeaders() },
                    body: JSON.stringify({ branding_json: data.branding_json, review_url: data.review_url || null, page }),
                });
                if (res.status === 422) { setStatus('invalid'); return; }
                if (!res.ok) throw new Error(String(res.status));
                const html = await res.text();
                if (frame.current) frame.current.srcdoc = html;
                setStatus('ready');
            } catch (e) {
                if (e.name !== 'AbortError') setStatus('error');
            }
        }, 450);
        return () => { clearTimeout(timer); controller.abort(); };
    }, [JSON.stringify(data.branding_json), data.review_url, page, propertyId]);

    useEffect(() => { decorate(); }, [focusTab, urls]);

    return (
        <div className="studio-preview">
            <div className="studio-preview__bar">
                <div className="studio-seg" role="group" aria-label="Device">
                    <button type="button" aria-pressed={device === 'phone'} onClick={() => setDevice('phone')}><Smartphone size={15} /> Phone</button>
                    <button type="button" aria-pressed={device === 'desktop'} onClick={() => setDevice('desktop')}><Monitor size={15} /> Laptop</button>
                </div>
                <div className="studio-seg" role="group" aria-label="Guest moment">
                    <button type="button" aria-pressed={page === 'splash'} onClick={() => setPage('splash')}><Wifi size={15} /> Before</button>
                    <button type="button" aria-pressed={page === 'connected'} onClick={() => setPage('connected')}><CheckCircle2 size={15} /> Connected</button>
                </div>
                <span className={`studio-preview__status studio-preview__status--${status}`}>
                    {status === 'loading' && <><Loader2 size={13} className="animate-spin" /> Updating</>}
                    {status === 'ready' && 'Live preview'}
                    {status === 'invalid' && 'Fix the highlighted field to refresh'}
                    {status === 'error' && 'Preview unavailable'}
                </span>
            </div>
            <div className="studio-preview__stage" ref={box}>
                <div className={`studio-device studio-device--${device}`} style={{ width: size.w * scale, height: size.h * scale }}>
                    <iframe
                        ref={frame}
                        title="Splash page preview"
                        onLoad={decorate}
                        style={{ width: size.w, height: size.h, transform: `scale(${scale})` }}
                    />
                </div>
            </div>
            <p className="studio-preview__note">This is exactly what guests see on the WiFi sign-in page. Buttons are disabled in the preview.</p>
        </div>
    );
}

export default function SplashStudio({ property, otherProperties = [], hostPhone = null, isBusiness = false }) {
    const [step, setStep] = useState('look');
    const { data, setData, post, processing, errors, isDirty } = useForm({
        branding_json: initialBranding(property.branding_json || {}, isBusiness),
        review_url: property.review_url || '',
        splash_image: null,
        logo: null,
    });
    const b = data.branding_json;
    const picked = useMemo(() => ({
        splash_image: data.splash_image ? URL.createObjectURL(data.splash_image) : null,
        logo: data.logo ? URL.createObjectURL(data.logo) : null,
    }), [data.splash_image, data.logo]);
    useEffect(() => () => Object.values(picked).forEach(url => url && URL.revokeObjectURL(url)), [picked]);
    const set = (key, value) => setData('branding_json', { ...b, [key]: value });
    const setSection = (key, on) => set('sections', { ...b.sections, [key]: on });
    const err = key => errors[`branding_json.${key}`];
    const rowErr = (list, i, key) => errors[`branding_json.${list}.${i}.${key}`];

    useEffect(() => {
        const warn = e => { if (isDirty) { e.preventDefault(); e.returnValue = ''; } };
        window.addEventListener('beforeunload', warn);
        return () => window.removeEventListener('beforeunload', warn);
    }, [isDirty]);

    const save = () => post(route('host.properties.splash.update', property.id), {
        preserveScroll: true,
        queryStringArrayFormat: 'indices',
        onSuccess: () => { setData(d => ({ ...d, splash_image: null, logo: null })); notify.success('Splash page saved'); },
        onError: () => notify.error('Some fields need attention'),
    });

    const counts = {
        look: null,
        welcome: b.welcome_note ? 1 : 0,
        house: b.house_rules.filter(Boolean).length + b.emergency.filter(r => r.phone).length,
        nearby: b.nearby.filter(r => r.name).length,
        review: data.review_url ? 1 : 0,
        offers: b.offers.filter(r => r.title).length,
        stays: b.stays.filter(r => r.title || r.property_id).length,
    };
    const stepErrors = key => Object.keys(errors).some(k => {
        const map = { look: ['template', 'accent', 'heading', 'message', 'button'], welcome: ['welcome_note', 'host_name', 'heading', 'message', 'button', 'tiles', 'fields', 'opt_in'], house: ['house_rules', 'emergency'], nearby: ['nearby'], review: ['review_prompt'], offers: ['offers'], stays: ['stays'] };
        if (key === 'look' && (k === 'logo' || k === 'splash_image')) return true;
        if (key === 'review' && k === 'review_url') return true;
        return (map[key] || []).some(f => k.startsWith(`branding_json.${f}`));
    });

    const addKenyaEssentials = () => {
        const have = new Set(b.emergency.map(r => r.phone.replace(/\s/g, '')));
        const extra = [
            { label: 'Police & ambulance', phone: '999' },
            { label: 'Emergency (any network)', phone: '112' },
            hostPhone ? { label: 'Your host', phone: hostPhone } : null,
        ].filter(r => r && !have.has(r.phone.replace(/\s/g, '')));
        set('emergency', [...b.emergency, ...extra].slice(0, 8));
    };

    const linked = new Set(b.stays.map(s => String(s.property_id)).filter(Boolean));
    const headlineFallback = originalTemplates.includes(b.template)
        ? `Welcome to ${property.name}`
        : (templateDefaults(b.template, isBusiness).heading || '').replace('{name}', property.name);
    const breadcrumbs = [
        { label: 'WiFi Access', href: route('host.properties.index') },
        { label: property.name, href: route('host.properties.show', property.id) },
        { label: 'Splash Studio' },
    ];

    const sectionBody = {
        look: (
            <>
                <SectionHead title="Template & colour" description="Pick a layout, then a colour that matches your brand. You can switch any time; your content carries over." />
                <TemplatePicker value={b.template} accent={b.accent} recommended={recommendedTemplate(property.business_type)}
                    onChange={t => setData('branding_json', applyTemplate(b, t, { business: isBusiness, keepAccent: true }))} />
                {err('template') && <p className="studio-field__error">{err('template')}</p>}
                <Field label="Accent colour" error={err('accent')}><AccentPicker value={b.accent} onChange={c => set('accent', c)} /></Field>
                <div className="studio-media">
                    {[
                        ['splash_image', 'Cover photo', 'A wide, bright photo of your place. Up to 4 MB.', property.splash_image_path],
                        ['logo', 'Logo', 'PNG with a transparent background works best. Up to 2 MB.', property.logo_path],
                    ].map(([key, label, hint, saved]) => (
                        <label key={key} className={`studio-upload studio-upload--${key}`}>
                            <span className="studio-upload__art" style={picked[key] || saved ? { backgroundImage: `url("${picked[key] || saved}")` } : undefined}>
                                {!(data[key] || saved) && <ImagePlus size={22} />}
                            </span>
                            <span className="studio-upload__text">
                                <strong>{label}</strong>
                                <small>{data[key] ? `${data[key].name} · not saved yet` : hint}</small>
                                {errors[key] && <small className="studio-field__error">{errors[key]}</small>}
                            </span>
                            <span className="studio-upload__btn">{data[key] || saved ? 'Replace' : 'Upload'}</span>
                            <input type="file" accept="image/png,image/jpeg,image/webp" onChange={e => setData(key, e.target.files[0] || null)} />
                        </label>
                    ))}
                </div>
            </>
        ),
        welcome: (
            <>
                <SectionHead title="Welcome" description="The headline and the note guests read first." />
                <Field label="Headline" hint={`Leave empty for “${headlineFallback}”. Your ${isBusiness ? 'business' : 'property'} name is highlighted automatically.`} error={err('heading')} count={<em>{b.heading.length}/100</em>}>
                    <input type="text" maxLength={100} value={b.heading} placeholder={headlineFallback} onChange={e => set('heading', e.target.value)} />
                </Field>
                <Field label="Short intro" error={err('message')} count={<em>{b.message.length}/240</em>}>
                    <textarea rows={2} maxLength={240} value={b.message} placeholder="Connect to the WiFi and stay in touch." onChange={e => set('message', e.target.value)} />
                </Field>
                <Field label="Connect button" error={err('button')}>
                    <input type="text" maxLength={40} value={b.button} placeholder="Connect to WiFi" onChange={e => set('button', e.target.value)} />
                </Field>
                {isBusiness && (
                    <>
                        <div className="studio-subhead"><h3>Quick links</h3></div>
                        <p className="studio-field__hint">Three tiles above the sign-up form. Without a link, menu and list tiles open your customer homepage, and gift and deal tiles open your offers.</p>
                        <RowList
                            rows={b.tiles} max={3} addLabel="Add a tile" empty="No quick links."
                            onAdd={() => set('tiles', [...b.tiles, { icon: 'star', label: '', url: '' }])}
                            onChange={rows => set('tiles', rows)}
                            render={(row, i, update) => (
                                <>
                                    <div className="studio-grid-2">
                                        <Field label="Label" error={rowErr('tiles', i, 'label')}><input type="text" maxLength={30} value={row.label} placeholder="View menu" onChange={e => update({ ...row, label: e.target.value })} /></Field>
                                        <Field label="Icon" error={rowErr('tiles', i, 'icon')}>
                                            <select value={row.icon} onChange={e => update({ ...row, icon: e.target.value })}>
                                                {tileIcons.map(icon => <option key={icon} value={icon}>{tileIconLabels[icon]}</option>)}
                                            </select>
                                        </Field>
                                    </div>
                                    <Field label="Link" hint="Optional: a booking page, menu or wa.me link." error={rowErr('tiles', i, 'url')}>
                                        <input type="url" maxLength={500} value={row.url} placeholder="https://wa.me/2547…" onChange={e => update({ ...row, url: e.target.value })} />
                                    </Field>
                                </>
                            )}
                        />
                    </>
                )}
                <div className="studio-subhead"><h3>Sign-up form</h3></div>
                <p className="studio-field__hint">First name and WhatsApp number are always asked for.</p>
                {nameAndPhoneOnly.includes(b.template) ? (
                    <p className="studio-field__hint">{findTemplate(b.template).name} pages only ask for a first name and phone number. Please don't add health questions to a WiFi page.</p>
                ) : (
                    <div className="studio-toggles">
                        <Toggle checked={!!b.fields.email} onChange={v => set('fields', { ...b.fields, email: v })} label="Ask for email (optional)" />
                        <Toggle checked={!!b.fields.birthday} onChange={v => set('fields', { ...b.fields, birthday: v })} label="Ask for birthday (optional, for a birthday treat)" />
                    </div>
                )}
                <Field label="Offers checkbox" hint="The optional marketing opt-in next to the sign-up button." error={err('opt_in')} count={<em>{b.opt_in.length}/120</em>}>
                    <input type="text" maxLength={120} value={b.opt_in} placeholder={templateDefaults(b.template, isBusiness).opt_in || 'Send me offers and early access to direct deals'} onChange={e => set('opt_in', e.target.value)} />
                </Field>
                <div className="studio-subhead">
                    <h3>Note from you</h3>
                    <Toggle checked={!!b.sections.welcome} onChange={v => setSection('welcome', v)} label={b.sections.welcome ? 'Shown' : 'Hidden'} />
                </div>
                <Field label="Welcome note" hint="A warm, personal paragraph or two. Line breaks are kept." error={err('welcome_note')} count={<em>{b.welcome_note.length}/1200</em>}>
                    <textarea rows={6} maxLength={1200} value={b.welcome_note} placeholder={"Karibu! We're so glad you're here.\n\nThe coffee is on the counter and the best sunset is from the roof…"} onChange={e => set('welcome_note', e.target.value)} />
                </Field>
                <Field label="Signed by" error={err('host_name')}>
                    <input type="text" maxLength={80} value={b.host_name} placeholder={`The ${property.name} team`} onChange={e => set('host_name', e.target.value)} />
                </Field>
            </>
        ),
        house: (
            <>
                <SectionHead title="House rules" description="Short, friendly rules. Guests see them numbered." sectionKey="rules" branding={b} setSection={setSection} />
                <RowList
                    rows={b.house_rules} max={12} addLabel="Add a rule" empty="No rules yet: quiet hours, check-out time, smoking…"
                    onAdd={() => set('house_rules', [...b.house_rules, ''])}
                    onChange={rows => set('house_rules', rows)}
                    render={(rule, i, update) => (
                        <Field label={`Rule ${i + 1}`} error={err(`house_rules.${i}`)}>
                            <input type="text" maxLength={160} value={rule} placeholder="Quiet hours 10pm – 7am" onChange={e => update(e.target.value)} autoFocus={!rule && i === b.house_rules.length - 1} />
                        </Field>
                    )}
                />
                <div className="studio-subhead">
                    <h3>Emergency &amp; help numbers</h3>
                    <Toggle checked={!!b.sections.emergency} onChange={v => setSection('emergency', v)} label={b.sections.emergency ? 'Shown' : 'Hidden'} />
                </div>
                <button type="button" className="studio-chip-btn" onClick={addKenyaEssentials}><PhoneCall size={14} /> Add Kenya essentials (999, 112{hostPhone ? ', your number' : ''})</button>
                <RowList
                    rows={b.emergency} max={8} addLabel="Add a number" empty="Guests can tap to call any number you add."
                    onAdd={() => set('emergency', [...b.emergency, { ...blank.emergency }])}
                    onChange={rows => set('emergency', rows)}
                    render={(row, i, update) => (
                        <div className="studio-grid-2">
                            <Field label="Label" error={rowErr('emergency', i, 'label')}><input type="text" maxLength={60} value={row.label} placeholder="Nearest hospital" onChange={e => update({ ...row, label: e.target.value })} /></Field>
                            <Field label="Phone" error={rowErr('emergency', i, 'phone')}><input type="tel" maxLength={30} value={row.phone} placeholder="+254 7XX XXX XXX" onChange={e => update({ ...row, phone: e.target.value })} /></Field>
                        </div>
                    )}
                />
            </>
        ),
        nearby: (
            <>
                <SectionHead title="Nearby recommendations" description="Your favourite spots. Guests swipe through them and get directions." sectionKey="nearby" branding={b} setSection={setSection} />
                <RowList
                    rows={b.nearby} max={12} addLabel="Add a place" empty="Add restaurants, beaches, shops or your trusted taxi driver."
                    onAdd={() => set('nearby', [...b.nearby, { ...blank.nearby }])}
                    onChange={rows => set('nearby', rows)}
                    render={(row, i, update) => (
                        <>
                            <div className="studio-cats" role="radiogroup" aria-label="Category">
                                {categories.map(([key, label]) => (
                                    <button key={key} type="button" role="radio" aria-checked={row.category === key} onClick={() => update({ ...row, category: key })}>{label}</button>
                                ))}
                            </div>
                            <div className="studio-grid-2">
                                <Field label="Name" error={rowErr('nearby', i, 'name')}><input type="text" maxLength={80} value={row.name} placeholder="Papa Remo Beach" onChange={e => update({ ...row, name: e.target.value })} /></Field>
                                <Field label="Distance" error={rowErr('nearby', i, 'distance')}><input type="text" maxLength={30} value={row.distance} placeholder="5 min walk" onChange={e => update({ ...row, distance: e.target.value })} /></Field>
                            </div>
                            <Field label="Why you love it" error={rowErr('nearby', i, 'note')}><input type="text" maxLength={200} value={row.note} placeholder="Turtle watching at dawn" onChange={e => update({ ...row, note: e.target.value })} /></Field>
                            <Field label="Map or website link" hint="Optional. Without one, “Directions” searches Google Maps for the name near your address." error={rowErr('nearby', i, 'url')}>
                                <input type="url" maxLength={500} value={row.url} placeholder="https://maps.app.goo.gl/…" onChange={e => update({ ...row, url: e.target.value })} />
                            </Field>
                        </>
                    )}
                />
            </>
        ),
        review: (
            <>
                <SectionHead title="Google reviews" description="A review card on the Welcome tab and the connected page. Guests can leave a review as soon as they're online." sectionKey="review" branding={b} setSection={setSection} />
                <Field label="Google review link" hint="In Google Business Profile, choose “Ask for reviews” and copy the link. It's also used for automatic review requests." error={errors.review_url}>
                    <input type="url" maxLength={500} value={data.review_url} placeholder="https://g.page/r/…/review" onChange={e => setData('review_url', e.target.value)} />
                </Field>
                <Field label="Card message" error={err('review_prompt')} count={<em>{b.review_prompt.length}/160</em>}>
                    <input type="text" maxLength={160} value={b.review_prompt} placeholder="Loved your stay? A Google review helps other guests find us." onChange={e => set('review_prompt', e.target.value)} />
                </Field>
            </>
        ),
        offers: (
            <>
                <SectionHead title="Offers" description="Upcoming deals and events. Guests who opt in to offers hear about them first." sectionKey="offers" branding={b} setSection={setSection} />
                {b.offers.some(o => o.title) && (
                    <a className="studio-chip-btn" href={route('host.marketing.builder')}>
                        <Megaphone size={14} /> Send these to opted-in guests in the Campaign Studio
                    </a>
                )}
                <RowList
                    rows={b.offers} max={8} addLabel="Add an offer" empty="e.g. 20% off your next stay when you book direct."
                    onAdd={() => set('offers', [...b.offers, { ...blank.offers }])}
                    onChange={rows => set('offers', rows)}
                    render={(row, i, update) => (
                        <>
                            <div className="studio-grid-2">
                                <Field label="Offer" error={rowErr('offers', i, 'title')}><input type="text" maxLength={80} value={row.title} placeholder="20% off your next stay" onChange={e => update({ ...row, title: e.target.value })} /></Field>
                                <Field label="When" error={rowErr('offers', i, 'when')}><input type="text" maxLength={60} value={row.when} placeholder="Until 31 Dec" onChange={e => update({ ...row, when: e.target.value })} /></Field>
                            </div>
                            <Field label="Details" error={rowErr('offers', i, 'detail')}><input type="text" maxLength={200} value={row.detail} placeholder="Book direct on WhatsApp and mention this page." onChange={e => update({ ...row, detail: e.target.value })} /></Field>
                        </>
                    )}
                />
            </>
        ),
        stays: (
            <>
                <SectionHead title="Cross-sell" description={isBusiness ? 'Promote your other locations or services.' : 'Promote your other listings, or extras like airport transfers and tours.'} sectionKey="stays" branding={b} setSection={setSection} />
                {otherProperties.length > 0 && (
                    <div className="studio-picks">
                        <p>Your other properties</p>
                        <div>
                            {otherProperties.map(p => (
                                <button key={p.id} type="button" disabled={linked.has(String(p.id)) || b.stays.length >= 8}
                                    onClick={() => set('stays', [...b.stays, { ...blank.stays, property_id: p.id }])}
                                    style={p.splash_image_path ? { backgroundImage: `url("${p.splash_image_path}")` } : undefined}>
                                    <span>{linked.has(String(p.id)) ? <CheckCircle2 size={14} /> : <Plus size={14} />} {p.name}</span>
                                </button>
                            ))}
                        </div>
                    </div>
                )}
                <RowList
                    rows={b.stays} max={8} addLabel="Add a custom card" empty="Nothing to promote yet."
                    onAdd={() => set('stays', [...b.stays, { ...blank.stays }])}
                    onChange={rows => set('stays', rows)}
                    render={(row, i, update) => {
                        const source = otherProperties.find(p => String(p.id) === String(row.property_id));
                        return (
                            <>
                                {source && <p className="studio-linked"><Building2 size={14} /> Uses the name and photo of <strong>{source.name}</strong></p>}
                                <div className="studio-grid-2">
                                    <Field label="Title" error={rowErr('stays', i, 'title')}><input type="text" maxLength={80} value={row.title} placeholder={source?.name || 'Airport transfer'} onChange={e => update({ ...row, title: e.target.value })} /></Field>
                                    <Field label="Price" error={rowErr('stays', i, 'price')}><input type="text" maxLength={40} value={row.price} placeholder="From KES 9,500" onChange={e => update({ ...row, price: e.target.value })} /></Field>
                                </div>
                                <Field label="Details" error={rowErr('stays', i, 'detail')}><input type="text" maxLength={200} value={row.detail} placeholder={source?.address || 'Sleeps 4 · 10 min from the beach'} onChange={e => update({ ...row, detail: e.target.value })} /></Field>
                                <Field label="Booking link" hint="Optional: your booking page, Airbnb listing or a wa.me link." error={rowErr('stays', i, 'url')}>
                                    <input type="url" maxLength={500} value={row.url} placeholder="https://wa.me/2547…" onChange={e => update({ ...row, url: e.target.value })} />
                                </Field>
                            </>
                        );
                    }}
                />
            </>
        ),
    };

    const current = steps.find(s => s.key === step);
    const index = steps.indexOf(current);

    return (
        <>
            <Head title={`Splash Studio · ${property.name}`} />
            <DashboardHero
                title="Splash Studio"
                breadcrumbs={breadcrumbs}
                actions={[
                    { label: 'Open full preview', icon: <ExternalLink size={16} />, onClick: () => window.open(route('host.properties.splash-preview', property.id), '_blank', 'noopener') },
                    { label: processing ? 'Saving…' : 'Save changes', variant: 'primary', icon: processing ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />, onClick: save },
                ]}
            />

            <div className="studio">
                <div className="studio-editor">
                    <nav className="studio-steps" aria-label="Splash sections">
                        {steps.map(({ key, label, icon: Icon }) => (
                            <button key={key} type="button" aria-current={step === key ? 'step' : undefined} onClick={() => setStep(key)}
                                className={stepErrors(key) ? 'has-error' : ''}>
                                <Icon size={16} />
                                <span>{label}</span>
                                {counts[key] > 0 && <em>{counts[key]}</em>}
                            </button>
                        ))}
                    </nav>

                    <section className="studio-section" aria-label={current.label}>
                        {sectionBody[step]}
                    </section>

                    <footer className="studio-footer">
                        <PillButton variant="secondary" onClick={() => setStep(steps[Math.max(0, index - 1)].key)} disabled={index === 0}>Back</PillButton>
                        <span className={`studio-dirty ${isDirty ? 'is-on' : ''}`}>{isDirty ? 'Unsaved changes' : 'All changes saved'}</span>
                        {index < steps.length - 1
                            ? <PillButton variant="secondary" onClick={() => setStep(steps[index + 1].key)}>Next: {steps[index + 1].label}</PillButton>
                            : <PillButton variant="primary" onClick={save} disabled={processing}>Save changes</PillButton>}
                    </footer>
                </div>

                <Preview propertyId={property.id} data={data} focusTab={current.tab} coverFile={data.splash_image} logoFile={data.logo} />
            </div>
        </>
    );
}
