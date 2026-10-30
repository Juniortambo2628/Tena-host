import React from 'react';
import { getContent, extractItems, getText, stripHtml } from '@/lib/cms';
import { SectionWrapper } from './layouts';
import { BRAND_COLORS } from '@/constants';
import './JourneySection.css';

/**
 * "What you get in your first month": five numbered steps with phone
 * mockups, a labelled example month, the outcomes and a booking bar.
 *
 * Each step's mockup is drawn from its "screen" field, one item per line,
 * parts split by " | ":
 *   wifi     network names; the first one is yours
 *   tent     big text, small line, network name
 *   splash   headline, then sign-up fields; "[ ] text" is the opt-in
 *   guide    "hero title | subtitle", then "icon | title | subtitle" rows
 *   welcome  like guide, plus "offer: small | big | small | button"
 *   chat     "message | button | time", add "| dark" for a dark button
 *   crm      "icon | title | subtitle" boxes, "↓" arrows, "highlight: …" in the brand colour
 */
const parts = (line) => line.split('|').map(p => p.trim());
const screenLines = (text) => String(text || '').replace(/<br\s*\/?>|<\/p>/gi, '\n').split('\n').map(stripHtml).filter(Boolean);

const readableInk = (hex) => {
    const m = /^#?([0-9a-f]{6})$/i.exec(hex || '');
    if (!m) return '#FFFFFF';
    const [r, g, b] = [0, 2, 4].map(i => parseInt(m[1].slice(i, i + 2), 16) / 255).map(c => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
    return 0.2126 * r + 0.7152 * g + 0.0722 * b > 0.4 ? BRAND_COLORS.dark : '#FFFFFF';
};

function WifiIcon({ color = 'currentColor', bars = 3 }) {
    return (
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke={color} strokeWidth="2.4" strokeLinecap="round" aria-hidden="true">
            {bars > 2 && <path d="M2 8.5a15 15 0 0 1 20 0" />}
            {bars > 1 && <path d="M5.5 12a10 10 0 0 1 13 0" />}
            <path d="M9 15.5a5 5 0 0 1 6 0" />
        </svg>
    );
}

function GoogleG() {
    return (
        <svg width="12" height="12" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#4285F4" d="M21.6 12.2c0-.7-.1-1.4-.2-2H12v3.8h5.4a4.6 4.6 0 0 1-2 3v2.5h3.2c1.9-1.7 3-4.3 3-7.3z" />
            <path fill="#34A853" d="M12 22c2.7 0 5-.9 6.6-2.4l-3.2-2.5c-.9.6-2 1-3.4 1-2.6 0-4.8-1.8-5.6-4.1H3.1v2.6A10 10 0 0 0 12 22z" />
            <path fill="#FBBC05" d="M6.4 14c-.2-.6-.3-1.3-.3-2s.1-1.4.3-2V7.4H3.1a10 10 0 0 0 0 9.2z" />
            <path fill="#EA4335" d="M12 5.9c1.5 0 2.8.5 3.8 1.5l2.9-2.9A10 10 0 0 0 3.1 7.4L6.4 10c.8-2.3 3-4.1 5.6-4.1z" />
        </svg>
    );
}

const Phone = ({ children, bar = true }) => (
    <div className="jr-ph">
        <div className="jr-ph__scr">
            {bar && <div className="jr-ph__bar"><span>9:41</span><span>●●●</span></div>}
            {children}
        </div>
    </div>
);

const Rows = ({ lines }) => lines.map((line, i) => {
    const [icon, title, sub] = parts(line);
    return (
        <div key={i} className="jr-ph__row">
            <span className="jr-ph__ic">{icon}</span>
            <div><b>{title}</b>{sub && <small>{sub}</small>}</div>
            <span className="jr-ph__chev">›</span>
        </div>
    );
});

function Mockup({ type, lines, brand }) {
    const { name, tagline, color, ink } = brand;
    const fill = { background: color, color: ink };

    switch (type) {
    case 'wifi':
        return (
            <Phone>
                <div className="jr-ph__wifi">
                    <h4>Wi-Fi</h4>
                    {lines.map((net, i) => (
                        <div key={i} className={`jr-ph__net ${i === 0 ? 'is-on' : ''}`}>
                            <WifiIcon color={i === 0 ? BRAND_COLORS.dark : '#9A9DA6'} bars={3 - Math.min(i, 2)} />
                            <div>{net}{i === 0 && <small>Powered by TenaFi</small>}</div>
                            {i === 0 && <span className="jr-ph__tick">✓</span>}
                        </div>
                    ))}
                </div>
            </Phone>
        );
    case 'tent':
        return (
            <div className="jr-tent" style={{ color }}>
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" aria-hidden="true"><path d="M2 8.5a15 15 0 0 1 20 0" /><path d="M5.5 12a10 10 0 0 1 13 0" /><path d="M9 15.5a5 5 0 0 1 6 0" /><circle cx="12" cy="19" r="1.2" fill="currentColor" /></svg>
                <b>{lines[0]}</b>
                <p>{lines[1]}<br /><strong>{lines[2]}</strong></p>
                <small>Powered by TenaFi</small>
            </div>
        );
    case 'splash': {
        const [headline, ...rest] = lines;
        const optIn = rest.find(l => l.startsWith('[ ]'));
        return (
            <Phone>
                <div className="jr-ph__brand" style={fill}><b>{name}</b><i>{tagline}</i></div>
                <div className="jr-ph__body">
                    <div className="jr-ph__h">{headline}</div>
                    {rest.filter(l => l !== optIn).map((f, i) => <div key={i} className="jr-ph__field">{f}</div>)}
                    {optIn && <div className="jr-ph__opt"><span style={{ background: color }} />{optIn.replace('[ ]', '').trim()}</div>}
                    <div className="jr-ph__cta" style={fill}>Connect to WiFi</div>
                    <div className="jr-ph__pw">Powered by TenaFi</div>
                </div>
            </Phone>
        );
    }
    case 'guide':
    case 'welcome': {
        const [hero, ...rest] = lines;
        const [heroTitle, heroSub] = parts(hero || '');
        const offer = rest.find(l => l.startsWith('offer:'));
        const [offerTop, offerBig, offerSub, offerBtn] = offer ? parts(offer.slice(6)) : [];
        return (
            <Phone>
                <div className="jr-ph__hero" style={fill}><b>{heroTitle}</b>{heroSub && <small>{heroSub}</small>}</div>
                {offer && (
                    <div className="jr-ph__offer">
                        <small>{offerTop}</small><b>{offerBig}</b><small>{offerSub}</small>
                        {offerBtn && <span className="jr-ph__obtn" style={fill}>{offerBtn}</span>}
                    </div>
                )}
                <Rows lines={rest.filter(l => l !== offer)} />
            </Phone>
        );
    }
    case 'chat':
        return (
            <Phone bar={false}>
                <div className="jr-ph__chathead">
                    <span className="jr-ph__av" style={fill}>{name.split(/\s+/).map(w => w[0]).join('').slice(0, 2).toUpperCase()}</span>
                    <div>{name}<small>WhatsApp</small></div>
                </div>
                <div className="jr-ph__chat">
                    {lines.map((line, i) => {
                        const [message, button, time, style] = parts(line);
                        return (
                            <div key={i} className="jr-ph__bub">
                                {message}
                                {button && (
                                    <span className={`jr-ph__btn ${style === 'dark' ? 'is-dark' : ''}`} style={style === 'dark' ? fill : undefined}>
                                        {/google/i.test(button) && <GoogleG />}{button}
                                    </span>
                                )}
                                {time && <span className="jr-ph__t">{time}</span>}
                            </div>
                        );
                    })}
                </div>
            </Phone>
        );
    case 'crm':
        return (
            <div className="jr-crm">
                {lines.map((line, i) => {
                    if (line === '↓') return <span key={i} className="jr-crm__down" aria-hidden="true">↓</span>;
                    const highlight = line.startsWith('highlight:');
                    const p = parts(highlight ? line.slice(10) : line);
                    if (p.length < 2) return <div key={i} className="jr-crm__box jr-crm__box--plain"><b>{p[0]}</b></div>;
                    const [icon, title, sub] = p;
                    return (
                        <div key={i} className={`jr-crm__box ${highlight ? 'is-highlight' : ''}`} style={highlight ? fill : undefined}>
                            <span className={`jr-crm__ic ${i === 0 ? 'is-first' : ''}`}>{icon}</span>
                            <div><b>{title}</b>{sub && <small>{sub}</small>}</div>
                        </div>
                    );
                })}
            </div>
        );
    default:
        return null;
    }
}

export default function JourneySection({ section }) {
    if (!section) return null;

    const color = /^#[0-9a-f]{6}$/i.test(getText(section, 'brand_color')) ? getText(section, 'brand_color') : BRAND_COLORS.dark;
    const brand = { name: getText(section, 'brand_name', 'Your business'), tagline: getText(section, 'brand_tagline'), color, ink: readableInk(color) };
    const steps = extractItems(section, 'steps', ['title', 'text', 'mockup', 'screen']).filter(s => s.title);
    const numbers = extractItems(section, 'numbers', ['value', 'label']).filter(n => n.value);
    const outcomes = extractItems(section, 'outcomes', ['title', 'text']).filter(o => o.title);
    const title = getText(section, 'title', 'What you get in your first month');
    const exampleTitle = getText(section, 'example_title', 'Example first month');
    const ctaLabel = getText(section, 'cta_label', 'Book my free trial');
    const ctaUrl = getText(section, 'cta_url', '#join');

    return (
        <SectionWrapper bg={section.bg || 'white'} width="wide" className="journey">
            <div className="journey-head">
                {getText(section, 'eyebrow') && <span className="journey-eyebrow">{getText(section, 'eyebrow')}</span>}
                <h2>{title}</h2>
                {getText(section, 'subtitle') && <p>{getText(section, 'subtitle')}</p>}
            </div>

            {steps.length > 0 && (
                <ol className="journey-steps" style={{ '--journey-steps': steps.length }}>
                    {steps.map((step, i) => (
                        <li key={i} className="journey-step">
                            <div className="journey-step__top">
                                <span className="journey-step__n" aria-hidden="true">{i + 1}</span>
                                <div>
                                    <h3><span className="sr-only">Step {i + 1}: </span>{stripHtml(step.title)}</h3>
                                    {step.text && <p>{stripHtml(step.text)}</p>}
                                </div>
                            </div>
                            <div className="journey-step__art" aria-hidden="true">
                                <Mockup type={stripHtml(step.mockup)} lines={screenLines(step.screen)} brand={brand} />
                            </div>
                        </li>
                    ))}
                </ol>
            )}

            {numbers.length > 0 && (
                <div className="journey-example">
                    <div className="journey-example__head">
                        {/* Example numbers are always labelled as such, whatever the admin calls them. */}
                        <b>
                            {!/example/i.test(exampleTitle) && <span className="journey-example__tag">Example</span>}
                            {exampleTitle}
                        </b>
                        {getText(section, 'example_caption') && <span>{getText(section, 'example_caption')}</span>}
                    </div>
                    <div className="journey-example__grid">
                        {numbers.map((n, i) => <div key={i}><b>{stripHtml(n.value)}</b><span>{stripHtml(n.label)}</span></div>)}
                    </div>
                    {getText(section, 'example_note') && <p>{getText(section, 'example_note')}</p>}
                </div>
            )}

            {outcomes.length > 0 && (
                <div className="journey-outcomes">
                    <div className="journey-outcomes__label">{getText(section, 'outcome_label', 'The outcome')}</div>
                    {outcomes.map((o, i) => (
                        <div key={i} className="journey-outcome"><b>{stripHtml(o.title)}</b>{o.text && <span>{stripHtml(o.text)}</span>}</div>
                    ))}
                </div>
            )}

            <div className="journey-bar">
                <p><b>{getText(section, 'bar_lead', 'One product, one team:')}</b> {getText(section, 'bar_text')}</p>
                <a href={ctaUrl}>{ctaLabel}</a>
            </div>
        </SectionWrapper>
    );
}
