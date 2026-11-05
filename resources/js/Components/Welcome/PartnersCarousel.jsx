import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { getMedia, extractItems, getText } from '@/lib/cms';
import EditablePicture from '@/Components/Public/EditablePicture';
import pictureKind from '@/lib/pictureKind';
import { SectionWrapper } from './layouts';
import './PartnersCarousel.css';

const KINDS = { hosts: 'Short-term rentals', business: 'Business' };
const TABS = [['all', 'All'], ['hosts', 'Short-term rentals'], ['business', 'Businesses']];

/**
 * A drawn WiFi page for a client without a screenshot. With a logo uploaded
 * it shows the logo and takes the logo's main colour; otherwise their initial.
 */
function PlaceholderPage({ partner, logo = null }) {
    const brand = logo?.color ? { '--brand-bg': logo.color, '--brand-ink': logo.ink } : undefined;
    return (
        <div className={`trusted-placeholder trusted-placeholder--${partner.kind === 'hosts' ? 'hosts' : 'business'} ${brand ? 'trusted-placeholder--brand' : ''}`} style={brand} role="img" aria-label={`${partner.name} WiFi page`}>
            <div className="trusted-placeholder__head">
                {logo
                    ? <span className="trusted-placeholder__logo" aria-hidden="true"><img src={logo.src} alt="" loading="lazy" decoding="async" /></span>
                    : <span className="trusted-placeholder__mono" aria-hidden="true">{partner.name.trim().charAt(0).toUpperCase()}</span>}
                <b>{partner.name}</b>
                {partner.type_area && <small>{partner.type_area}</small>}
            </div>
            <div className="trusted-placeholder__body" aria-hidden="true">
                <span className="trusted-placeholder__h">Free WiFi</span>
                <i /><i /><i />
                <span className="trusted-placeholder__cta">Connect to WiFi</span>
                <small>Powered by TenaFi</small>
            </div>
        </div>
    );
}

const prefersReducedMotion = () => typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

/**
 * "Trusted by": each client's branded WiFi page in a phone frame.
 * Rows come from Admin → Public Pages (name, url, kind, type_area) with the
 * picture in the row's media slot (partner_{i}_logo). A tall phone
 * screenshot fills the phone; a logo goes on a drawn WiFi page in the logo's
 * colour; with nothing uploaded the drawn page shows the client's initial.
 */
export default function PartnersCarousel({ section }) {
    const list = useRef(null);
    // What each uploaded picture is: a phone screenshot, or a logo (and its colour).
    const [looks, setLooks] = useState(() => new Map());
    const [filter, setFilter] = useState('all');
    const [overflow, setOverflow] = useState({ prev: false, next: false, fits: true });
    const paused = useRef(false);

    const partners = useMemo(() => {
        if (!section) return [];
        return extractItems(section, 'partners', ['name', 'url', 'kind', 'type_area'])
            .map((partner, i) => ({ ...partner, index: i, image: getMedia(section, `partner_${i}_logo`, '') }))
            .filter(p => p.name);
    }, [section]);

    // Checked in the browser once per picture; until then (and on the server) the drawn page shows.
    const checked = useRef(new Set());
    useEffect(() => {
        partners.filter(p => p.image && !checked.current.has(p.image)).forEach(p => {
            checked.current.add(p.image);
            pictureKind(p.image).then(look => setLooks(prev => new Map(prev).set(p.image, look)));
        });
    }, [partners]);

    const visible = partners.filter(p => filter === 'all' || p.kind === filter);
    const bothKinds = partners.some(p => p.kind === 'hosts') && partners.some(p => p.kind === 'business');

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

    if (!section || partners.length === 0) return null;

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
                                    <EditablePicture section={section} slot={`partner_${partner.index}_logo`}>
                                    <div className="trusted-phone">
                                        {looks.get(partner.image)?.kind === 'shot' ? (
                                            <img
                                                src={partner.image}
                                                alt={`${partner.name} WiFi login page`}
                                                width="600" height="1200" loading="lazy" decoding="async"
                                            />
                                        ) : (
                                            <PlaceholderPage
                                                partner={partner}
                                                logo={looks.get(partner.image)?.kind === 'logo' ? { src: partner.image, ...looks.get(partner.image) } : null}
                                            />
                                        )}
                                    </div>
                                    </EditablePicture>
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
