import React, { useEffect, useRef, useState } from 'react';
import { getContent, getText, isTruthy, stripHtml } from '@/lib/cms';
import { track } from '@/lib/analytics';
import { usePublic } from './PublicContext';
import './WhatsAppButton.css';

const slug = (text) => text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 20) || 'option';

/** "Label | pre-filled message" lines from the page's menu field. */
function menuOptions(settings, kind) {
    return String(getContent(settings, `menu_${kind}`, '') || '')
        .replace(/<br\s*\/?>|<\/p>/gi, '\n').split('\n')
        .map(line => line.split('|').map(part => stripHtml(part)))
        .filter(([label, message]) => label && message)
        .map(([label, message]) => ({ label, message, key: slug(label) }));
}

/**
 * Floating "WhatsApp us" button on every public page (Site-wide → Floating
 * WhatsApp button). When the page has menu options (menu_home, menu_hosts,
 * menu_business, menu_events: "Label | message" per line) it opens a small menu, each
 * option with its own pre-filled message; otherwise it opens WhatsApp
 * straight away with the page's message.
 */
export default function WhatsAppButton() {
    const { site, page } = usePublic();
    const [open, setOpen] = useState(false);
    const wrap = useRef(null);
    const toggle = useRef(null);
    const settings = site.whatsapp_button;
    const number = getText(settings, 'number').replace(/\D/g, '');

    useEffect(() => {
        if (!open) return undefined;
        const onKey = (e) => { if (e.key === 'Escape') { setOpen(false); toggle.current?.focus(); } };
        const onClick = (e) => { if (wrap.current && !wrap.current.contains(e.target)) setOpen(false); };
        document.addEventListener('keydown', onKey);
        document.addEventListener('mousedown', onClick);
        document.addEventListener('touchstart', onClick, { passive: true });
        wrap.current?.querySelector('.whatsapp-menu a')?.focus();
        return () => {
            document.removeEventListener('keydown', onKey);
            document.removeEventListener('mousedown', onClick);
            document.removeEventListener('touchstart', onClick);
        };
    }, [open]);

    if (!settings || !isTruthy(getText(settings, 'enabled', '1')) || !number) return null;

    const kind = ['hosts', 'business', 'events'].includes(page?.slug) ? page.slug : 'home';
    const message = getText(settings, `message_${kind}`) || getText(settings, 'message_home');
    const label = getText(settings, 'label', 'WhatsApp us');
    const options = menuOptions(settings, kind);
    const link = (text) => `https://wa.me/${number}${text ? `?text=${encodeURIComponent(text)}` : ''}`;
    const icon = (
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M3.5 20.5l1.3-4.1A8.5 8.5 0 1 1 8 19.4z" />
            <path d="M9 9.2c.2 1.9 2 3.8 3.9 4.6l1.3-1.1 2 .9c-.2 1.2-1.1 1.9-2.3 1.9-3.2 0-6.6-3.4-6.6-6.6 0-1.2.7-2.1 1.9-2.3l.9 2z" />
        </svg>
    );

    if (!options.length) {
        return (
            <a className="whatsapp-float" href={link(message)} target="_blank" rel="noopener noreferrer"
                aria-label={`${label} (opens WhatsApp)`} onClick={() => track('whatsapp_click', page?.slug)}>
                {icon}<span className="whatsapp-float__label">{label}</span>
            </a>
        );
    }

    return (
        <div className="whatsapp-wrap" ref={wrap}>
            {open && (
                <div className="whatsapp-menu" id="whatsapp-menu" role="dialog" aria-label={label}>
                    <p className="whatsapp-menu__hi">{getText(settings, 'menu_greeting', 'Hi there! What brings you to TenaFi?')}</p>
                    {options.map(option => (
                        <a key={option.key} href={link(option.message)} target="_blank" rel="noopener noreferrer"
                            onClick={() => { track('whatsapp_click', page?.slug, option.key); setOpen(false); }}>
                            {option.label}
                        </a>
                    ))}
                    <small>Opens WhatsApp · {getText(settings, 'number')}</small>
                </div>
            )}
            <button ref={toggle} type="button" className="whatsapp-float" aria-expanded={open} aria-controls="whatsapp-menu"
                aria-label={`${label}: choose a topic`} onClick={() => setOpen(v => !v)}>
                {icon}<span className="whatsapp-float__label">{label}</span>
            </button>
        </div>
    );
}
