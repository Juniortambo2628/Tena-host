import React from 'react';
import { Check, X } from 'lucide-react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, extractItems, stripHtml, isTruthy } from '@/lib/cms';
import LineList from '@/Components/Public/LineList';
import './ComparisonSection.css';

/**
 * Side-by-side panels: the problem for each audience, QR card vs TenaFi,
 * booking platform vs TenaFi WiFi. A "highlight" column gets brand colour.
 */
export default function ComparisonSection({ section }) {
    if (!section) return null;

    const columns = extractItems(section, 'columns', ['label', 'stat', 'stat_label', 'points', 'text', 'highlight']);
    const note = stripHtml(getContent(section, 'note', ''));
    const sources = stripHtml(getContent(section, 'sources', ''));
    // "versus": the plain column gets crosses, the highlighted one is dark with ticks.
    const versus = stripHtml(getContent(section, 'style', '')) === 'versus';
    const stats = extractItems(section, 'stats', ['value', 'label']).filter(s => stripHtml(s.value));

    return (
        <SectionWrapper bg={section.bg || 'white'}>
            <SectionHeader
                badge={stripHtml(getContent(section, 'badge', ''))}
                title={stripHtml(getContent(section, 'title', ''))}
                subtitle={getContent(section, 'subtitle', '')}
            />
            <div className={`comparison-grid ${versus ? 'comparison-grid--versus' : ''}`}>
                {columns.map((col, i) => (
                    <div key={i} className={`comparison-col ${isTruthy(col.highlight) ? 'comparison-col--highlight' : ''}`}>
                        <h3 className="comparison-label">{stripHtml(col.label)}</h3>
                        {stripHtml(col.stat) && (
                            <p className="comparison-stat">
                                <span className="comparison-stat-value">{stripHtml(col.stat)}</span>
                                <span className="comparison-stat-label">{stripHtml(col.stat_label)}</span>
                            </p>
                        )}
                        <LineList
                            text={col.points}
                            className="comparison-points"
                            icon={versus && !isTruthy(col.highlight)
                                ? <X size={16} className="comparison-point-icon comparison-point-icon--no" />
                                : <Check size={16} className="comparison-point-icon" />}
                        />
                        {stripHtml(col.text) && <p className="comparison-text">{stripHtml(col.text)}</p>}
                    </div>
                ))}
            </div>
            {stats.length > 0 && (
                <ul className="comparison-stats">
                    {stats.map((s, i) => <li key={i}><b>{stripHtml(s.value)}</b><span>{stripHtml(s.label)}</span></li>)}
                </ul>
            )}
            {note && <p className="comparison-note">{note}</p>}
            {sources && <p className="comparison-sources">{sources}</p>}
        </SectionWrapper>
    );
}
