import React from 'react';
import { Plus } from 'lucide-react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, extractItems, sanitizeHtml, stripHtml } from '@/lib/cms';
import './FaqSection.css';

export default function FaqSection({ section }) {
    if (!section) return null;

    const items = extractItems(section, 'items', ['question', 'answer']);

    return (
        <SectionWrapper bg={section.bg || 'white'} width="narrow">
            <SectionHeader title={stripHtml(getContent(section, 'title', 'Questions'))} subtitle={getContent(section, 'subtitle', '')} />
            <div className="faq-list">
                {items.map((item, i) => (
                    <details key={i} className="faq-item">
                        <summary className="faq-question">
                            {stripHtml(item.question)}
                            <Plus size={18} className="faq-icon" aria-hidden="true" />
                        </summary>
                        <div className="faq-answer" dangerouslySetInnerHTML={{ __html: sanitizeHtml(item.answer) }} />
                    </details>
                ))}
            </div>
        </SectionWrapper>
    );
}
