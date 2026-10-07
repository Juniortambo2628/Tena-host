import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { SectionWrapper, SectionHeader } from '@/Components/Welcome/layouts';
import { sanitizeHtml } from '@/lib/cms';
import './Policy.css';

/**
 * Privacy Policy / Terms, edited under Admin -> Policies.
 */
export default function Policy({ page, document, site = {}, seo = {} }) {
    const updated = document.effective_date || document.updated_at;

    return (
        <PublicLayout site={site} page={page} seo={seo} joinHref="/#paths" hasSections={false}>
            <SectionWrapper bg="white" width="narrow" padding="lg" className="policy-page">
                <SectionHeader
                    title={document.title}
                    subtitle={updated ? `Version ${document.version || '1.0'} · Last updated ${new Date(updated).toLocaleDateString('en-KE', { dateStyle: 'long' })}` : null}
                />
                <article
                    className="policy-page__content"
                    dangerouslySetInnerHTML={{ __html: sanitizeHtml(document.content) }}
                />
            </SectionWrapper>
        </PublicLayout>
    );
}
