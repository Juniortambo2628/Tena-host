import React from 'react';
import { Link } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { splashTemplates, templateGroups, accentPresets, defaultSplashDesign, normalizeTemplate } from './splashTemplates';
import './SplashEditor.css';
import { BRAND_COLORS } from '@/constants';

export { defaultSplashDesign };

// Sketches of the band templates (shapes styled in SplashEditor.css).
const bars = (x, y, w, gap, n, cls = 'bar') => Array.from({ length: n }, (_, i) => (
    <i key={`${cls}${i}`} className={cls} style={{ left: `${x}%`, top: `${y + i * gap}%`, width: `${w}%` }} />
));
const pill = (left, top, width) => <i className="pill" style={{ left: `${left}%`, top: `${top}%`, width: `${width}%` }} />;
const BAND_THUMBS = {
    brew: () => <><i className="top" /><i className="cup" /><i className="card" />{[29, 42.5, 56].map(l => <i key={l} className="tile" style={{ left: `${l}%` }} />)}{bars(29, 68, 42, 0, 1)}{pill(29, 80, 42)}</>,
    bistro: () => <><i className="sheet" />{bars(10, 64, 50, 0, 1)}<i className="o" /><i className="f" /></>,
    bloom: () => <><i className="leaf" style={{ left: '8%', top: '18%' }} /><i className="leaf" style={{ right: '8%', top: '56%', transform: 'rotate(90deg)' }} /><i className="arch" />{bars(37, 40, 26, 10, 2)}{pill(37, 66, 26)}</>,
    fade: () => <><i className="pole" /><i className="w" style={{ left: '40%', top: '18%', width: '40%' }} /><i className="w" style={{ left: '40%', top: '34%', width: '28%' }} />{bars(40, 54, 44, 10, 2)}{pill(40, 78, 44)}</>,
    pulse: () => <><i className="slash" /><i className="slash2" /><i className="w" style={{ left: '8%', top: '20%', width: '36%' }} /><i className="w" style={{ left: '8%', top: '37%', width: '26%' }} />{bars(8, 56, 36, 0, 1)}{pill(8, 72, 36)}</>,
    calm: () => <><i className="c1" /><i className="c2" /><i className="card" /><i className="plus" />{bars(37, 44, 26, 10, 2)}{pill(37, 68, 26)}</>,
    corner: () => <><i className="awn" /><i className="sc" /><i className="card" /><i className="tag" />{bars(26, 56, 26, 9, 2)}{pill(26, 78, 48)}</>,
};

// A miniature of each template's layout, drawn with CSS so it tracks the accent colour.
export function TemplateThumb({ template, accent = BRAND_COLORS.primary }) {
    if (BAND_THUMBS[template]) {
        return (
            <span className={`splash-thumb splash-thumb--band splash-thumb--${template}`} style={{ '--thumb-accent': accent }} aria-hidden="true">
                {BAND_THUMBS[template]()}
            </span>
        );
    }
    return (
        <span className={`splash-thumb splash-thumb--${template}`} style={{ '--thumb-accent': accent }} aria-hidden="true">
            <i className="splash-thumb__photo" />
            <i className="splash-thumb__panel"><b /><b /><b /></i>
            <i className="splash-thumb__cards"><b /><b /><b /></i>
        </span>
    );
}

// Templates under small group headings; the one matching the business type is badged.
export function TemplatePicker({ value, accent, onChange, compact = false, recommended = null }) {
    const current = normalizeTemplate(value);
    return (
        <div className="splash-template-groups" role="radiogroup" aria-label="Splash template">
            {templateGroups.map(group => (
                <div key={group.id} className="splash-template-group">
                    <p className="splash-template-group__label">{group.label}</p>
                    <div className={`splash-templates ${compact ? 'splash-templates--compact' : ''}`}>
                        {splashTemplates.filter(t => t.group === group.id).map(t => (
                            <label key={t.key} className="splash-template">
                                <input type="radio" name="splash-template" value={t.key} checked={current === t.key} onChange={() => onChange(t.key)} />
                                <TemplateThumb template={t.key} accent={current === t.key ? accent : t.accent} />
                                {recommended === t.key && <span className="splash-template__badge">Recommended</span>}
                                <span className="splash-template__text">
                                    <strong>{t.name}</strong>
                                    {!compact && <small>{t.description}</small>}
                                    <em>{t.best}</em>
                                </span>
                            </label>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}

export function AccentPicker({ value, onChange }) {
    const accent = value || BRAND_COLORS.primary;
    return (
        <div className="splash-accents" role="radiogroup" aria-label="Accent colour">
            {accentPresets.map(color => (
                <button key={color} type="button" role="radio" aria-checked={accent.toLowerCase() === color.toLowerCase()}
                    aria-label={color} className="splash-accent" style={{ background: color }} onClick={() => onChange(color)} />
            ))}
            <label className="splash-accent splash-accent--custom" title="Custom colour">
                <input type="color" value={accent} onChange={e => onChange(e.target.value)} aria-label="Custom accent colour" />
            </label>
        </div>
    );
}

/**
 * The quick picker inside the property modal: template and colour only.
 * Everything else (welcome note, house rules, nearby picks...) lives in the Splash Studio.
 */
export default function SplashEditor({ value, onChange, onTemplate, onAccent, errors = {}, propertyId, recommended = null, business = false }) {
    const design = { ...defaultSplashDesign, ...value };
    const set = (key, next) => onChange({ ...design, [key]: next });
    const extras = business ? 'a welcome note, quick links, reviews and offers' : 'a welcome note, house rules, emergency numbers, local picks, reviews and offers';
    return (
        <div className="splash-editor">
            <div>
                <p className="splash-editor__label">Template</p>
                <TemplatePicker value={design.template} accent={design.accent} onChange={t => (onTemplate ? onTemplate(t) : set('template', t))} recommended={recommended} compact />
                {errors['branding_json.template'] && <p className="splash-editor__error" role="alert">{errors['branding_json.template']}</p>}
            </div>
            <div>
                <p className="splash-editor__label">Accent colour</p>
                <AccentPicker value={design.accent} onChange={c => { set('accent', c); onAccent?.(c); }} />
                {errors['branding_json.accent'] && <p className="splash-editor__error" role="alert">{errors['branding_json.accent']}</p>}
            </div>
            <div className="splash-editor__studio">
                <Sparkles size={18} />
                <p>
                    {propertyId
                        ? <>Add {extras} in the <Link href={route('host.properties.splash', propertyId)}>Splash Studio</Link>, with a live preview.</>
                        : <>After saving, open the {business ? 'location' : 'property'}'s <strong>Splash Studio</strong> to add {extras}.</>}
                </p>
            </div>
        </div>
    );
}
