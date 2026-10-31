import React from 'react';
import './Mockup.css';

/**
 * Product mockups drawn in HTML/CSS from an editable "screen" field, so
 * admins change the words without uploading pictures. Any section can use
 * it with a `mockup` (the first panel's type) and a `screen` field.
 *
 * The screen holds one item per line. "---" starts another panel, and a
 * panel can name its own type on its first line ("@chat"). Lines:
 *
 *   all         color: #0F4C45            the brand colour of that panel
 *   splash      brand: Name | Tagline     headline: …   tiles: a | b | c
 *               any plain line is a form field          optin: …   sponsor: …
 *   chat        head: Name | WhatsApp     msg: text | button | time | dark | label
 *   rating      name: …   score: 4.5   meta: …   row: BO | text | when
 *   stats       score: 4.5   row: label | value
 *   attendees   head: …   row: JM | Name | status   tag: …
 *   guide       head: …   row: 📖 | item | price
 *   mpesa       to: Pay KES 2,000 to …?   ok: Paid. Your host has been told.
 *
 * Every drawing carries an "Example" label: names and numbers are made up.
 */
export const MOCKUP_TYPES = ['splash', 'chat', 'rating', 'stats', 'attendees', 'guide', 'mpesa'];

const split = (value) => String(value).split('|').map(p => p.trim());

const toLines = (text) => String(text || '')
    .replace(/<br\s*\/?>|<\/p>/gi, '\n')
    .replace(/<[^>]*>/g, '')
    .replace(/&amp;/g, '&')
    .split('\n')
    .map(l => l.trim())
    .filter(Boolean);

/** Parse one panel's lines into { type, color, props, rows, msgs, fields }. */
function parsePanel(lines, fallbackType) {
    const panel = { type: fallbackType, color: '#1E1E1E', props: {}, rows: [], msgs: [], fields: [] };
    lines.forEach((line, i) => {
        if (i === 0 && line.startsWith('@')) {
            panel.type = line.slice(1).trim();
            return;
        }
        const m = /^([a-z]+):\s*(.*)$/i.exec(line);
        const key = m?.[1].toLowerCase();
        if (key === 'color') panel.color = /^#[0-9a-f]{6}$/i.test(m[2]) ? m[2] : panel.color;
        else if (key === 'row') panel.rows.push(split(m[2]));
        else if (key === 'msg') panel.msgs.push(split(m[2]));
        else if (key && ['brand', 'headline', 'tiles', 'optin', 'sponsor', 'head', 'name', 'score', 'meta', 'tag', 'to', 'ok', 'button'].includes(key)) panel.props[key] = m[2];
        else panel.fields.push(line);
    });
    return panel;
}

export function parseScreen(text, type) {
    const panels = [[]];
    toLines(text).forEach(line => (line === '---' ? panels.push([]) : panels[panels.length - 1].push(line)));
    return panels.filter(p => p.length).map(p => parsePanel(p, type));
}

function ink(hex) {
    const m = /^#?([0-9a-f]{6})$/i.exec(hex || '');
    if (!m) return '#FFFFFF';
    const [r, g, b] = [0, 2, 4].map(i => parseInt(m[1].slice(i, i + 2), 16) / 255).map(c => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
    return 0.2126 * r + 0.7152 * g + 0.0722 * b > 0.4 ? '#1E1E1E' : '#FFFFFF';
}

export function Phone({ children, small = false }) {
    return <div className={`mk-phone ${small ? 'mk-phone--sm' : ''}`}><div className="mk-phone__scr">{children}</div></div>;
}

function Splash({ p, fill, small }) {
    const [name, tagline] = split(p.props.brand || 'Your business');
    const tiles = p.props.tiles ? split(p.props.tiles) : [];
    return (
        <Phone small={small}>
            <div className="mk-splash__brand" style={fill}><b>{name}</b>{tagline && <i>{tagline}</i>}</div>
            <div className="mk-splash__body">
                {p.props.headline && <div className="mk-splash__h">{p.props.headline}</div>}
                {tiles.length > 0 && (
                    <div className="mk-splash__tiles">{tiles.map((t, i) => <span key={i}>{t}</span>)}</div>
                )}
                {p.fields.map((f, i) => <div key={i} className="mk-splash__field">{f}</div>)}
                {p.props.optin && <div className="mk-splash__opt"><span style={{ background: p.color }} />{p.props.optin}</div>}
                <div className="mk-splash__cta" style={fill}>{p.props.button || 'Connect to WiFi'}</div>
                {p.props.sponsor
                    ? <div className="mk-splash__sponsor">{p.props.sponsor}</div>
                    : <div className="mk-splash__pw">Powered by TenaFi</div>}
            </div>
        </Phone>
    );
}

function Chat({ p, fill }) {
    const [name, sub] = split(p.props.head || 'WhatsApp');
    const initials = name.split(/\s+/).map(w => w[0]).join('').slice(0, 2).toUpperCase();
    return (
        <div className="mk-chat">
            <div className="mk-chat__head"><span className="mk-av" style={fill}>{initials}</span><div><b>{name}</b>{sub && <small>{sub}</small>}</div></div>
            <div className="mk-chat__body">
                {p.msgs.map(([text, button, time, style, label], i) => (
                    <div key={i} className="mk-bubble">
                        {label && <span className="mk-bubble__label">{label}</span>}
                        {text}
                        {button && (
                            <span className={`mk-bubble__btn ${style === 'dark' ? 'is-dark' : ''}`} style={style === 'dark' ? fill : undefined}>
                                {/google/i.test(button) && <b className="mk-g" aria-hidden="true">G</b>}{button}
                            </span>
                        )}
                        {time && <span className="mk-bubble__time">{time}</span>}
                    </div>
                ))}
            </div>
        </div>
    );
}

function Rating({ p }) {
    return (
        <div className="mk-card mk-rating">
            <b>{p.props.name}</b>
            <div className="mk-rating__score"><span>{p.props.score}</span><span className="mk-stars" aria-hidden="true">★★★★★</span></div>
            {p.props.meta && <small>{p.props.meta}</small>}
            {p.rows.map(([initials, text, when], i) => (
                <div key={i} className="mk-row"><span className="mk-av mk-av--soft">{initials}</span><span>{text}</span><small>{when}</small></div>
            ))}
        </div>
    );
}

function Stats({ p }) {
    return (
        <div className="mk-card mk-stats">
            <div className="mk-rating__score"><span>{p.props.score}</span><span className="mk-stars" aria-hidden="true">★★★★★</span></div>
            {p.rows.map(([label, value], i) => <div key={i} className="mk-row"><span>{label}</span><b>{value}</b></div>)}
        </div>
    );
}

function Attendees({ p }) {
    return (
        <div className="mk-card mk-attendees">
            <b>{p.props.head}</b>
            {p.rows.map(([initials, name, status], i) => (
                <div key={i} className="mk-row"><span className="mk-av mk-av--soft">{initials}</span><span>{name}</span><small className="mk-pill">{status}</small></div>
            ))}
            {p.props.tag && <small className="mk-attendees__tag">{p.props.tag}</small>}
        </div>
    );
}

function Guide({ p, fill, small }) {
    return (
        <Phone small={small}>
            <div className="mk-guide__head" style={fill}><b>{p.props.head}</b></div>
            {p.rows.map(([icon, item, price], i) => (
                <div key={i} className="mk-guide__row"><span className="mk-guide__ic">{icon}</span><span>{item}</span><b>{price}</b></div>
            ))}
        </Phone>
    );
}

function Mpesa({ p }) {
    return (
        <div className="mk-card mk-mpesa">
            <div className="mk-mpesa__top"><span className="mk-mpesa__logo">M-PESA</span>{p.props.to}</div>
            <div className="mk-mpesa__pin">Enter M-PESA PIN</div>
            {p.props.ok && <div className="mk-mpesa__ok">✓ {p.props.ok}</div>}
        </div>
    );
}

const PANELS = { splash: Splash, chat: Chat, rating: Rating, stats: Stats, attendees: Attendees, guide: Guide, mpesa: Mpesa };

/** Draws every panel of a screen side by side, with an "Example" label. */
export default function Mockup({ type, screen, className = '' }) {
    const panels = parseScreen(screen, type).filter(p => PANELS[p.type]);
    if (!panels.length) return null;
    const phones = panels.filter(p => ['splash', 'guide'].includes(p.type)).length;

    return (
        <div className={`mk-stage mk-stage--${panels.length} ${className}`} aria-hidden="true">
            <span className="mk-example">Example</span>
            {panels.map((p, i) => {
                const Panel = PANELS[p.type];
                return <div key={i} className="mk-panel"><Panel p={p} fill={{ background: p.color, color: ink(p.color) }} small={phones > 1} /></div>;
            })}
        </div>
    );
}
