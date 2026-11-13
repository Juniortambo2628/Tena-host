import React, { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { SiteLayout, Container, SectionHeading, Eyebrow, PillLink, SECTION_OFFSET } from '@okjt/site';
import V2Site from '@/Components/V2/V2Site';
import InstallSteps from '@/Components/V2/InstallSteps';
import { Pricing as PlanCards, Faq } from '@/Components/V2/Sections';
import { extractItems, stripHtml } from '@/lib/cms';
import { devicesFrom, formatKes, contentText as text } from '@/lib/devices';
import './Pricing.css';

/**
 * /pricing: the TenaFi devices (Lite, Pro, Outdoor) and the two ways to pay,
 * from Glen's "Devices & Pricing" (November 2026). Words and device prices are
 * edited in Admin -> Public Pages -> Devices & pricing; the plans, the three
 * steps and the example are each audience page's own pricing section, so
 * /pricing always quotes what /hosts and /business quote. Each device is
 * paid once; there is no paying monthly.
 */
export default function V2Pricing({ sections = [], planSections = {}, seo = {} }) {
    const { site = {} } = usePage().props;
    const devicesSection = sections.find((s) => s.section_key === 'devices');
    const pay = sections.find((s) => s.section_key === 'pay_options');
    const faq = sections.find((s) => s.section_key === 'faq');
    const devices = devicesFrom(devicesSection);

    return (
        <V2Site joinHref="#ways-to-pay">
            <Head title={seo.title || 'TenaFi devices and pricing'} />
            <SiteLayout cta={false}>
                <Plans planSections={planSections} site={site} />
                {devicesSection && <Devices section={devicesSection} devices={devices} />}
                {devicesSection && <InstallSteps section={devicesSection} className="bg-white" />}
                {pay && <GoodToKnow section={pay} />}
                {faq && <Faq section={faq} id="faq" />}
            </SiteLayout>
        </V2Site>
    );
}

/** Where each audience's "Choose …" buttons go: its own sign-up form. */
const AUDIENCES = [
    { id: 'hosts', label: 'Short-term rentals', join: '/hosts#join' },
    { id: 'business', label: 'Businesses', join: '/business#signup' },
];

/**
 * The plans, exactly as each audience page's pricing section shows them,
 * with a switch between rentals (per unit) and businesses (per location).
 */
function Plans({ planSections, site }) {
    const audiences = AUDIENCES.filter((a) => planSections[a.id]);
    const [current, setCurrent] = useState(audiences[0]?.id);
    const audience = audiences.find((a) => a.id === current) || audiences[0];
    if (!audience) return null;

    return (
        <div className="pricing-plans">
            {audiences.length > 1 && (
                <div className="pricing-plans__switch" role="group" aria-label="Show plans for">
                    {audiences.map((a) => (
                        <button key={a.id} type="button" aria-pressed={a.id === audience.id} onClick={() => setCurrent(a.id)}>
                            {a.label}
                        </button>
                    ))}
                </div>
            )}
            <PlanCards key={audience.id} section={planSections[audience.id]} site={site} id="plans" joinHref={audience.join} devicesLink={false} />
        </div>
    );
}

const PICTURE = { light: 'bg-paper-warm', yellow: 'bg-accent-soft', dark: 'bg-ink' };

function Devices({ section, devices }) {
    const combos = extractItems(section, 'combos', ['label', 'value']).filter((c) => stripHtml(c.label));
    const perks = extractItems(section, 'perks', ['title', 'text']).filter((p) => stripHtml(p.title));
    const upfront = text(section, 'upfront_label', 'Pay once');

    return (
        <section id={text(section, 'anchor', 'devices')} className={`${SECTION_OFFSET} bg-white py-20`}>
            <Container>
                <SectionHeading align="left" eyebrow={text(section, 'badge')} title={text(section, 'title')} text={text(section, 'subtitle')} />

                <ul className="pricing-devices mt-12">
                    {devices.map((d) => (
                        <li key={d.id} className="pricing-device">
                            <div className={`pricing-device__picture ${PICTURE[d.style] || PICTURE.light}`}>
                                {d.image && <img src={d.image} alt={d.name} loading="lazy" decoding="async" width="660" height="390" />}
                            </div>
                            <div className="pricing-device__body">
                                {d.badge && <span className={`pricing-chip pricing-chip--${d.style}`}>{d.badge}</span>}
                                <h3 className="pricing-device__name">{d.name}</h3>
                                {d.bestFor && <p className="pricing-device__for">{d.bestFor}</p>}
                                <dl className="pricing-device__specs">
                                    {d.coverage && <div><dt>{d.coverageLabel}</dt><dd>{d.coverage}</dd></div>}
                                    {d.capacity && <div><dt>{d.capacityLabel}</dt><dd>{d.capacity}</dd></div>}
                                </dl>
                                <div className="pricing-device__price">
                                    <span className="pricing-label">{upfront}</span>
                                    <b>{formatKes(d.price)}</b>
                                </div>
                                {/* The device's own page: specifications and more pictures. */}
                                <PillLink to={`/pricing/${d.id}`} variant="outline" size="sm" arrow className="mt-4 self-start">
                                    {text(section, 'details_label', 'See details')}
                                </PillLink>
                            </div>
                        </li>
                    ))}
                </ul>

                {combos.length > 0 && (
                    <div className="pricing-more mt-10">
                        <div className="pricing-more__intro">
                            <h3>{text(section, 'more_title')}</h3>
                            <p>{text(section, 'more_text')}</p>
                        </div>
                        <ul className="pricing-more__list">
                            {combos.map((c, i) => (
                                <li key={i}><span>{stripHtml(c.label)}</span><b>{stripHtml(c.value)}</b></li>
                            ))}
                        </ul>
                    </div>
                )}

                {perks.length > 0 && (
                    <ul className="pricing-perks mt-6">
                        {perks.map((p, i) => <li key={i}><b>{stripHtml(p.title)}</b><span>{stripHtml(p.text)}</span></li>)}
                    </ul>
                )}

                {text(section, 'note') && <p className="pricing-footnote mt-6">{text(section, 'note')}</p>}
            </Container>
        </section>
    );
}

/**
 * "Good to know" (section key pay_options, kept so live content stays
 * attached): the notes, the footnote and the buttons. The two ways to pay,
 * their price table and example are gone (Nov 2026); stored rows stay in the
 * database but are no longer shown.
 */
function GoodToKnow({ section }) {
    const notes = extractItems(section, 'notes', ['title', 'text']).filter((n) => stripHtml(n.title));
    const buttons = extractItems(section, 'buttons', ['label', 'href']).filter((b) => stripHtml(b.label));

    return (
        <section id={text(section, 'anchor', 'ways-to-pay')} className={`${SECTION_OFFSET} bg-tint py-20`}>
            <Container>
                <SectionHeading align="left" eyebrow={text(section, 'badge')} title={text(section, 'title')} text={text(section, 'subtitle')} />

                {notes.length > 0 && (
                    <ul className="pricing-notes mt-6">
                        {notes.map((n, i) => <li key={i}><b>{stripHtml(n.title)}</b><span>{stripHtml(n.text)}</span></li>)}
                    </ul>
                )}

                {text(section, 'footnote') && <p className="pricing-footnote mt-6">{text(section, 'footnote')}</p>}

                {buttons.length > 0 && (
                    <div className="pricing-cta mt-12">
                        <div>
                            <Eyebrow>{text(section, 'cta_title')}</Eyebrow>
                            <p>{text(section, 'cta_text')}</p>
                        </div>
                        <div className="pricing-cta__buttons">
                            {buttons.map((b, i) => (
                                <PillLink key={i} to={stripHtml(b.href)} variant={i === 0 ? 'dark' : 'outline'} arrow>{stripHtml(b.label)}</PillLink>
                            ))}
                        </div>
                    </div>
                )}
            </Container>
        </section>
    );
}
