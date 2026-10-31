import React from 'react';
import { Check, X } from 'lucide-react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, extractItems, stripHtml } from '@/lib/cms';
import './ComparisonTable.css';

/**
 * A three-column table: what you need, without TenaFi, with TenaFi.
 * Reusable on any page (section type "comparison_table").
 */
export default function ComparisonTable({ section }) {
    if (!section) return null;

    const rows = extractItems(section, 'rows', ['label', 'without', 'with']).filter(r => stripHtml(r.label));
    const col = (key, fallback) => stripHtml(getContent(section, key, fallback));

    return (
        <SectionWrapper bg={section.bg || 'white'}>
            <SectionHeader
                badge={stripHtml(getContent(section, 'badge', ''))}
                title={stripHtml(getContent(section, 'title', ''))}
                subtitle={getContent(section, 'subtitle', '')}
            />
            <table className="ctable">
                <thead>
                    <tr>
                        <th scope="col">{col('col_label', 'What you need')}</th>
                        <th scope="col">{col('col_without', 'Without')}</th>
                        <th scope="col" className="ctable__with">{col('col_with', 'With TenaFi')}</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row, i) => (
                        <tr key={i}>
                            <th scope="row">{stripHtml(row.label)}</th>
                            <td data-label={col('col_without', 'Without')}><X size={16} aria-hidden="true" className="ctable__no" />{stripHtml(row.without)}</td>
                            <td data-label={col('col_with', 'With TenaFi')} className="ctable__with"><Check size={16} aria-hidden="true" className="ctable__yes" />{stripHtml(row.with)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </SectionWrapper>
    );
}
