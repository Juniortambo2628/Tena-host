import React from 'react';
import { Head } from '@inertiajs/react';
import { SiteLayout, Container, SectionHeading, PillLink, SECTION_OFFSET } from '@okjt/site';
import { ArrowLeft, Check } from 'lucide-react';
import V2Site from '@/Components/V2/V2Site';
import { extractItems, stripHtml, textLines } from '@/lib/cms';
import { devicesFrom, formatKes, fill, contentText as text } from '@/lib/devices';
import './Pricing.css';
import './Device.css';

const PICTURE = { light: 'bg-paper-warm', yellow: 'bg-accent-soft', dark: 'bg-ink' };

/**
 * A TenaFi device's own page (/pricing/lite, /pricing/pro, /pricing/outdoor):
 * the picture, price, specifications, highlights, more pictures and what
 * every device includes. Everything comes from the /pricing devices section
 * (Admin -> Public Pages -> Devices & pricing), so it matches /pricing.
 */
export default function V2Device({ section, index, pay = null, seo = {} }) {
    const devices = devicesFrom(section);
    const device = devices.find((d) => d.id === stripHtml(section?.content?.[`items.${index}.id`] ?? '')) || devices[index];
    if (!device) return null;

    const others = devices.filter((d) => d.id !== device.id);
    const buttons = extractItems(pay, 'buttons', ['label', 'href']).filter((b) => stripHtml(b.label) && stripHtml(b.href));
    const included = textLines(section?.content?.included ?? '');
    const monthlyLine = text(section, 'monthly_line', 'or {monthly} a month for {months} months');

    return (
        <V2Site joinHref={stripHtml(buttons[0]?.href ?? '') || '/hosts#join'}>
            <Head title={seo.title || `${device.name} | TenaFi`} />
            <SiteLayout cta={false}>
                <section className="pt-32 pb-16">
                    <Container>
                        <PillLink to="/pricing#devices" variant="outline" size="sm">
                            <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                            {text(section, 'back_label', 'All devices and pricing')}
                        </PillLink>

                        <div className="device-hero mt-8">
                            <div className={`device-hero__picture ${PICTURE[device.style] || PICTURE.light}`}>
                                {device.image && <img src={device.image} alt={device.name} decoding="async" width="660" height="390" />}
                            </div>
                            <div className="device-hero__body">
                                {device.badge && <span className={`pricing-chip pricing-chip--${device.style}`}>{device.badge}</span>}
                                <h1 className="device-hero__name">{device.name}</h1>
                                {device.tagline && <p className="device-hero__tagline">{device.tagline}</p>}
                                {device.bestFor && <p className="pricing-device__for">{device.bestFor}</p>}
                                <dl className="pricing-device__specs">
                                    {device.coverage && <div><dt>{device.coverageLabel}</dt><dd>{device.coverage}</dd></div>}
                                    {device.capacity && <div><dt>{device.capacityLabel}</dt><dd>{device.capacity}</dd></div>}
                                </dl>
                                <div className="pricing-device__price">
                                    <span className="pricing-label">{text(section, 'upfront_label', 'Buy upfront, installed')}</span>
                                    <b>{formatKes(device.price)}</b>
                                    {device.monthly > 0 && (
                                        <span className="pricing-device__monthly">
                                            {fill(monthlyLine, { monthly: `+${formatKes(device.monthly)}`, months: device.months })}
                                        </span>
                                    )}
                                </div>
                                {buttons.length > 0 && (
                                    <div className="device-hero__buttons">
                                        {buttons.map((b, i) => (
                                            <PillLink key={i} to={stripHtml(b.href)} variant={i === 0 ? 'dark' : 'outline'}>{stripHtml(b.label)}</PillLink>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </Container>
                </section>

                {(device.specs.length > 0 || device.highlights.length > 0) && (
                    <section className="bg-white py-16">
                        <Container>
                            <div className="device-details">
                                {device.specs.length > 0 && (
                                    <div>
                                        <h2 className="device-details__title">{text(section, 'specs_title', 'Specifications')}</h2>
                                        <dl className="device-specs">
                                            {device.specs.map(([label, value]) => (
                                                <div key={label}><dt>{label}</dt><dd>{value}</dd></div>
                                            ))}
                                        </dl>
                                        {text(section, 'note') && <p className="device-details__note">{text(section, 'note')}</p>}
                                    </div>
                                )}
                                {device.highlights.length > 0 && (
                                    <div>
                                        <h2 className="device-details__title">{text(section, 'highlights_title', 'Why this one')}</h2>
                                        <ul className="device-list">
                                            {device.highlights.map((h) => <li key={h}><Check className="h-4 w-4" aria-hidden="true" />{h}</li>)}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        </Container>
                    </section>
                )}

                {device.gallery.length > 0 && (
                    <section className="py-16">
                        <Container>
                            <h2 className="device-details__title">{text(section, 'gallery_title', 'See it in use')}</h2>
                            <ul className="device-gallery">
                                {device.gallery.map((src, i) => (
                                    <li key={src}><img src={src} alt={`${device.name} in use, picture ${i + 1}`} loading="lazy" decoding="async" /></li>
                                ))}
                            </ul>
                        </Container>
                    </section>
                )}

                {included.length > 0 && (
                    <section className={`${SECTION_OFFSET} bg-white py-16`}>
                        <Container>
                            <h2 className="device-details__title">{text(section, 'included_title', 'Included with every TenaFi device')}</h2>
                            <ul className="device-list device-list--grid">
                                {included.map((line) => <li key={line.text}><Check className="h-4 w-4" aria-hidden="true" />{line.text}</li>)}
                            </ul>
                        </Container>
                    </section>
                )}

                {others.length > 0 && (
                    <section className="py-16">
                        <Container>
                            <SectionHeading align="left" as="h2" title={text(section, 'title', 'One TenaFi device for every size of space.')} />
                            <ul className="pricing-devices mt-8">
                                {others.map((d) => (
                                    <li key={d.id} className="pricing-device">
                                        <div className={`pricing-device__picture ${PICTURE[d.style] || PICTURE.light}`}>
                                            {d.image && <img src={d.image} alt={d.name} loading="lazy" decoding="async" width="660" height="390" />}
                                        </div>
                                        <div className="pricing-device__body">
                                            <h3 className="pricing-device__name">{d.name}</h3>
                                            {d.bestFor && <p className="pricing-device__for">{d.bestFor}</p>}
                                            <PillLink to={`/pricing/${d.id}`} variant="outline" size="sm" arrow className="mt-4 self-start">
                                                {text(section, 'details_label', 'See details')}
                                            </PillLink>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </Container>
                    </section>
                )}
            </SiteLayout>
        </V2Site>
    );
}
