import React, { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { SiteLayout, Container, SectionHeading, Eyebrow, PillLink, SECTION_OFFSET } from '@okjt/site';
import V2Site from '@/Components/V2/V2Site';
import { Pricing as PlanCards } from '@/Components/V2/Sections';
import { extractItems, stripHtml, textLines } from '@/lib/cms';
import { devicesFrom, formatKes, monthlyWithDevice, firstYear, fill, contentText as text } from '@/lib/devices';
import './Pricing.css';

/**
 * /pricing: the TenaFi devices (Lite, Pro, Outdoor) and the two ways to pay,
 * from Glen's "Devices & Pricing" (November 2026). Words and device prices are
 * edited in Admin -> Public Pages -> Devices & pricing; plan prices are the
 * live /hosts plans, so the price table and the example are worked out here
 * and always match both.
 */
export default function V2Pricing({ sections = [], planSections = {}, seo = {} }) {
    const { plans = [], site = {} } = usePage().props;
    const devicesSection = sections.find((s) => s.section_key === 'devices');
    const pay = sections.find((s) => s.section_key === 'pay_options');
    const devices = devicesFrom(devicesSection);

    return (
        <V2Site joinHref="#ways-to-pay">
            <Head title={seo.title || 'TenaFi devices and pricing'} />
            <SiteLayout cta={false}>
                <Plans planSections={planSections} site={site} />
                {devicesSection && <Devices section={devicesSection} devices={devices} />}
                {pay && <WaysToPay section={pay} devices={devices} plans={plans.filter((p) => p.price_kes > 0)} />}
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
    const upfront = text(section, 'upfront_label', 'Buy upfront, installed');
    const monthlyLine = text(section, 'monthly_line', 'or {monthly} a month for {months} months');

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
                                    {d.monthly > 0 && (
                                        <span className="pricing-device__monthly">
                                            {fill(monthlyLine, { monthly: `+${formatKes(d.monthly)}`, months: d.months })}
                                        </span>
                                    )}
                                </div>
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

function WaysToPay({ section, devices, plans }) {
    const site = usePage().props.site || {};
    const options = extractItems(section, 'options', ['badge', 'title', 'text', 'steps', 'style']).filter((o) => stripHtml(o.title));
    const notes = extractItems(section, 'notes', ['title', 'text']).filter((n) => stripHtml(n.title));
    const buttons = extractItems(section, 'buttons', ['label', 'href']).filter((b) => stripHtml(b.label));
    const cheapest = plans.length ? formatKes(Math.min(...plans.map((p) => p.price_kes))) : '';

    const plan = plans.find((p) => p.id === text(section, 'example_plan')) || plans[1] || plans[0];
    const device = devices.find((d) => d.id === text(section, 'example_device')) || devices[1] || devices[0];
    const year = plan && device ? firstYear(plan.price_kes, device) : null;

    return (
        <section id={text(section, 'anchor', 'ways-to-pay')} className={`${SECTION_OFFSET} bg-tint py-20`}>
            <Container>
                <SectionHeading align="left" eyebrow={text(section, 'badge')} title={text(section, 'title')} text={text(section, 'subtitle')} />

                <div className="pricing-options mt-12">
                    {options.map((o, i) => (
                        <div key={i} className={`pricing-option pricing-option--${stripHtml(o.style) || 'light'}`}>
                            {stripHtml(o.badge) && <span className={`pricing-chip pricing-chip--${stripHtml(o.style) === 'dark' ? 'yellow' : 'soft'}`}>{stripHtml(o.badge)}</span>}
                            <h3><span className="sr-only">Option {i + 1}: </span>{i + 1} · {stripHtml(o.title)}</h3>
                            <p>{stripHtml(o.text)}</p>
                            <ol>
                                {textLines(o.steps, site).map((line, n) => (
                                    <li key={n}><span aria-hidden="true">{n + 1}</span>{fill(line.text, { cheapest })}</li>
                                ))}
                            </ol>
                        </div>
                    ))}
                </div>

                {plans.length > 0 && devices.length > 0 && (
                    <div className="pricing-table mt-10">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">{text(section, 'table_label', 'Monthly price')}</th>
                                    {plans.map((p) => <th key={p.id} scope="col">{p.label}</th>)}
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="pricing-table__best">
                                    <th scope="row">{text(section, 'row_upfront')}<small>{text(section, 'row_upfront_note')}</small></th>
                                    {plans.map((p) => <td key={p.id}>{formatKes(p.price_kes)}</td>)}
                                </tr>
                                {devices.map((d) => (
                                    <tr key={d.id}>
                                        <th scope="row">
                                            {fill(text(section, 'row_monthly', 'Paying monthly with {device}'), { device: d.name })}
                                            <small>{fill(text(section, 'row_monthly_note'), { months: d.months })}</small>
                                        </th>
                                        {plans.map((p) => <td key={p.id}>{formatKes(monthlyWithDevice(p.price_kes, d))}</td>)}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
                {plans.length > 2 && devices.length > 0 && <p className="pricing-table__hint">Swipe the table to see every plan.</p>}

                {year && (
                    <>
                        <div className="pricing-example mt-6">
                            <div className="pricing-example__intro">
                                <b>{text(section, 'example_title')}</b>
                                <span>{text(section, 'example_text')}</span>
                            </div>
                            <div className="pricing-example__way pricing-example__way--best">
                                <span className="pricing-label">{text(section, 'example_upfront', 'Buy the device')}</span>
                                <b>{formatKes(year.upfront)}</b>
                                <span>{formatKes(device.price)} once + {formatKes(plan.price_kes)} × 12</span>
                            </div>
                            <div className="pricing-example__way">
                                <span className="pricing-label">{text(section, 'example_monthly', 'Pay monthly')}</span>
                                <b>{formatKes(year.monthly)}</b>
                                <span>{formatKes(monthlyWithDevice(plan.price_kes, device))} × {Math.min(device.months, 12)}{device.months < 12 ? ` + ${formatKes(plan.price_kes)} × ${12 - device.months}` : ''}</span>
                            </div>
                        </div>
                        {year.saving > 0 && (
                            <p className="pricing-saving mt-4">
                                <span>{text(section, 'example_saving')}</span>
                                <b>{formatKes(year.saving)}</b>
                            </p>
                        )}
                    </>
                )}

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
