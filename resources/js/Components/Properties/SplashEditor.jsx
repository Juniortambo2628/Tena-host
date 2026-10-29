import React from 'react';
import { Link } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { splashTemplates, accentPresets, defaultSplashDesign, normalizeTemplate } from './splashTemplates';
import './SplashEditor.css';
import { BRAND_COLORS } from '@/constants';

export { defaultSplashDesign };

// A miniature of each template's layout, drawn with CSS so it tracks the accent colour.
export function TemplateThumb({ template, accent = BRAND_COLORS.primary }) {
    return (
        <span className={`splash-thumb splash-thumb--${template}`} style={{ '--thumb-accent': accent }} aria-hidden="true">
            <i className="splash-thumb__photo" />
            <i className="splash-thumb__panel"><b /><b /><b /></i>
            <i className="splash-thumb__cards"><b /><b /><b /></i>
        </span>
    );
}

export function TemplatePicker({ value, accent, onChange, compact = false }) {
    const current = normalizeTemplate(value);
    return (
        <div className={`splash-templates ${compact ? 'splash-templates--compact' : ''}`} role="radiogroup" aria-label="Splash template">
            {splashTemplates.map(t => (
                <label key={t.key} className="splash-template">
                    <input type="radio" name="splash-template" value={t.key} checked={current === t.key} onChange={() => onChange(t.key)} />
                    <TemplateThumb template={t.key} accent={accent} />
                    <span className="splash-template__text">
                        <strong>{t.name}</strong>
                        {!compact && <small>{t.description}</small>}
                        <em>{t.best}</em>
                    </span>
                </label>
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
export default function SplashEditor({ value, onChange, errors = {}, propertyId }) {
    const design = { ...defaultSplashDesign, ...value };
    const set = (key, next) => onChange({ ...design, [key]: next });
    return (
        <div className="splash-editor">
            <div>
                <p className="splash-editor__label">Template</p>
                <TemplatePicker value={design.template} accent={design.accent} onChange={t => set('template', t)} compact />
                {errors['branding_json.template'] && <p className="splash-editor__error" role="alert">{errors['branding_json.template']}</p>}
            </div>
            <div>
                <p className="splash-editor__label">Accent colour</p>
                <AccentPicker value={design.accent} onChange={c => set('accent', c)} />
                {errors['branding_json.accent'] && <p className="splash-editor__error" role="alert">{errors['branding_json.accent']}</p>}
            </div>
            <div className="splash-editor__studio">
                <Sparkles size={18} />
                <p>
                    {propertyId
                        ? <>Add a welcome note, house rules, emergency numbers, local picks, reviews and offers in the <Link href={route('host.properties.splash', propertyId)}>Splash Studio</Link>, with a live preview.</>
                        : <>After saving, open the property's <strong>Splash Studio</strong> to add a welcome note, house rules, local picks, reviews and offers.</>}
                </p>
            </div>
        </div>
    );
}
