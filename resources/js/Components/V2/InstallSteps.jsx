import React from 'react';
import { Container, SectionHeading, SECTION_OFFSET } from '@okjt/site';
import { extractItems, getUpload, stripHtml } from '@/lib/cms';
import './InstallSteps.css';

/**
 * "How it's installed": how a TenaFi device connects (internet, router, one
 * cable) and how it's mounted on a wall or ceiling, from the /pricing
 * devices section's install_steps. A step shows only once its picture
 * (install_N_image) is uploaded, and the strip only when one has, so it
 * appears by itself when the images arrive. Used on /pricing and on each
 * device page (/pricing/pro, ...).
 */
export default function InstallSteps({ section, className = '' }) {
    const steps = extractItems(section, 'install_steps', ['title', 'text'])
        .map((step, i) => ({ title: stripHtml(step.title), text: stripHtml(step.text), image: getUpload(section, `install_${i}_image`) }))
        .filter((step) => step.title && step.image);
    if (!steps.length) return null;

    return (
        <section id="installed" className={`${SECTION_OFFSET} py-16 ${className}`}>
            <Container>
                <SectionHeading
                    align="left"
                    as="h2"
                    title={stripHtml(section?.content?.install_title ?? '') || 'How it’s installed'}
                    text={stripHtml(section?.content?.install_text ?? '')}
                />
                <ol className="install-steps">
                    {steps.map((step, i) => (
                        <li key={step.title} className="install-step">
                            <img src={step.image} alt={step.title} loading="lazy" decoding="async" width="1600" height="1200" />
                            <div className="install-step__body">
                                <span className="install-step__number" aria-hidden="true">{i + 1}</span>
                                <div className="min-w-0">
                                    <h3 className="install-step__title">{step.title}</h3>
                                    {step.text && <p className="install-step__text">{step.text}</p>}
                                </div>
                            </div>
                        </li>
                    ))}
                </ol>
            </Container>
        </section>
    );
}
