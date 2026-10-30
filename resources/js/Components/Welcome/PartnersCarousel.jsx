import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { getMedia, extractItems, getText } from '@/lib/cms';
import { SectionWrapper } from './layouts';
import './PartnersCarousel.css';

const KINDS = { hosts: 'Short-term rentals', business: 'Business' };
const TABS = [['all', 'All'], ['hosts', 'Short-term rentals'], ['business', 'Businesses']];

const prefersReducedMotion = () => typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

/**
 * "Trusted by": each client's branded WiFi page in a phone frame.
 * Rows come from Admin → Public Pages (name, url, kind, type_area) with the
 * screenshot in the row's media slot (partner_{i}_logo). Rows without a
 * screenshot, or whose image isn't portrait (an old logo), are left out; with
 * none left the section doesn't render.
 */
export default function PartnersCarousel({ section }) {
    const list = useRef(null);
    const [failed, setFailed] = useState(() => new Set());
    const [filter, setFilter] = useState('all');
    const [overflow, setOverflow] = useState({ prev: false, next: false, fits: true });
    const paused = useRef(false);

    const partners = useMemo(() => {
        if (!section) return [];
        return extractItems(section, 'partners', ['name', 'url', 'kind', 'type_area'])
            .map((partner, i) => ({ ...partner, index: i, image: getMedia(section, `partner_${i}_logo`, '') }))
            .filter(p => p.name && p.image);
    }, [section]);

    const shown = partners.filter(p => !failed.has(p.index));
    const visible = shown.filter(p => filter === 'all' || p.kind === filter);
    const bothKinds = shown.some(p => p.kind === 'hosts') && shown.some(p => p.kind === 'business');

    const drop = (index) => setFailed(prev => new Set(prev).add(index));

    const measure = useCallback(() => {
        const el = list.current;
        if (!el) return;
        const fits = el.scrollWidth <= el.clientWidth + 4;
        setOverflow({ fits, prev: el.scrollLeft > 2, next: el.scrollLeft + el.clientWidth < el.scrollWidth - 2 });
    }, []);

    useEffect(() => {
        measure();
        window.addEventListener('resize', measure);
        return () => window.removeEventListener('resize', measure);
    }, [measure, visible.length]);

    useEffect(() => {
        if (!bothKinds && filter !== 'all') setFilter('all');
    }, [bothKinds, filter]);

    const step = (dir) => {
        const el = list.current;
        const card = el?.querySelector('.trusted-item');
        if (el) el.scrollBy({ left: dir * ((card?.getBoundingClientRect().width || 180) + 18), behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
    };

    // Gentle auto-advance; stops while someone hovers, touches or tabs in, and never with reduced motion.
    useEffect(() => {
        if (prefersReducedMotion()) return undefined;
        const timer = setInterval(() => {
            const el = list.current;
            if (!el || paused.current || document.hidden || el.scrollWidth <= el.clientWidth + 4) return;
            if (el.scrollLeft + el.clientWidth >= el.scrollWidth - 2) el.scrollTo({ left: 0, behavior: 'smooth' });
            else step(1);
        }, 3500);
        return () => clearInterval(timer);
    }, []);

    if (!section || shown.length === 0) return null;

    const title = getText(section, 'title', 'Trusted by hosts and local businesses');
    const subtitle = getText(section, 'subtitle', '');
    const pause = () => { paused.current = true; };

    return (
        <SectionWrapper id="trusted" bg={section.bg || 'white'} className="trusted">
            <div onMouseEnter={pause} onMouseLeave={() => { paused.current = false; }} onFocus={pause} onTouchStart={pause}>
                <div className="trusted-head">
                    <div>
                        <h2 className="trusted-title">{title}</h2>
                        {subtitle && <p className="trusted-subtitle">{subtitle}</p>}
                    </div>
                    {bothKinds && (
                        <div className="trusted-tabs" role="group" aria-label="Show">
                            {TABS.map(([key, label]) => (
                                <button key={key} type="button" aria-pressed={filter === key}
                                    onClick={() => { setFilter(key); if (list.current) list.current.scrollLeft = 0; }}>
                                    {label}
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                <ul ref={list} className={`trusted-list ${overflow.fits ? 'trusted-list--few' : ''}`} aria-label="Client WiFi pages" onScroll={measure}>
                    {visible.map(partner => {
                        const linked = partner.url && partner.url !== '#';
                        return (
                            <li key={partner.index} className="trusted-item" data-kind={partner.kind || undefined}>
                                <figure>
                                    <div className="trusted-phone">
                                        <img
                                            src={partner.image}
                                            alt={`${partner.name} WiFi login page`}
                                            width="600" height="1200" loading="lazy" decoding="async"
                                            onError={() => drop(partner.index)}
                                            // Screenshots are portrait (about 1:2); an old square logo is left out.
                                            onLoad={e => { const { naturalWidth: w, naturalHeight: h } = e.currentTarget; if (!w || h / w < 1.3) drop(partner.index); }}
                                        />
                                    </div>
                                    <figcaption>
                                        {linked
                                            ? <a href={partner.url} target="_blank" rel="noopener noreferrer"><b>{partner.name}</b></a>
                                            : <b>{partner.name}</b>}
                                        {partner.type_area && <span>{partner.type_area}</span>}
                                        {KINDS[partner.kind] && <span className={`trusted-tag trusted-tag--${partner.kind}`}>{KINDS[partner.kind]}</span>}
                                    </figcaption>
                                </figure>
                            </li>
                        );
                    })}
                </ul>

                {!overflow.fits && (
                    <div className="trusted-nav">
                        <button type="button" onClick={() => step(-1)} disabled={!overflow.prev} aria-label="Previous"><ChevronLeft size={20} /></button>
                        <button type="button" onClick={() => step(1)} disabled={!overflow.next} aria-label="Next"><ChevronRight size={20} /></button>
                    </div>
                )}
            </div>
        </SectionWrapper>
    );
}
