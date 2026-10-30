import React from 'react';
import { getText, isTruthy } from '@/lib/cms';
import { track } from '@/lib/analytics';
import { usePublic } from './PublicContext';
import './WhatsAppButton.css';

/**
 * Floating "WhatsApp us" button on every public page, with a pre-filled
 * message for the page (Site-wide → Floating WhatsApp button).
 */
export default function WhatsAppButton() {
    const { site, page } = usePublic();
    const settings = site.whatsapp_button;
    const number = getText(settings, 'number').replace(/\D/g, '');
    if (!settings || !isTruthy(getText(settings, 'enabled', '1')) || !number) return null;

    const kind = ['hosts', 'business'].includes(page?.slug) ? page.slug : 'home';
    const message = getText(settings, `message_${kind}`) || getText(settings, 'message_home');
    const label = getText(settings, 'label', 'WhatsApp us');

    return (
        <a
            className="whatsapp-float"
            href={`https://wa.me/${number}${message ? `?text=${encodeURIComponent(message)}` : ''}`}
            target="_blank"
            rel="noopener noreferrer"
            aria-label={`${label} (opens WhatsApp)`}
            onClick={() => track('whatsapp_click', page?.slug)}
        >
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <path d="M3.5 20.5l1.3-4.1A8.5 8.5 0 1 1 8 19.4z" />
                <path d="M9 9.2c.2 1.9 2 3.8 3.9 4.6l1.3-1.1 2 .9c-.2 1.2-1.1 1.9-2.3 1.9-3.2 0-6.6-3.4-6.6-6.6 0-1.2.7-2.1 1.9-2.3l.9 2z" />
            </svg>
            <span className="whatsapp-float__label">{label}</span>
        </a>
    );
}
