import React from 'react';
import './SplashEditor.css';

export const defaultSplashDesign = { template: 'classic', accent: '#FFD300', heading: '', message: '', button: '' };

const templates = [
    ['classic', 'Classic', 'Clean, compact and familiar.'],
    ['retreat', 'Retreat', 'Warm, photo-led hospitality.'],
    ['bold', 'Bold', 'High contrast with a full-screen photo.'],
];

export default function SplashEditor({ value, onChange, errors = {}, propertyId, onLogoChange, logoUrl }) {
    const design = { ...defaultSplashDesign, ...value };
    const set = (key, next) => onChange({ ...design, [key]: next });
    return (
        <fieldset className="splash-editor">
            <legend>Splash page design</legend>
            <div className="splash-editor__templates">
                {templates.map(([key, name, description]) => (
                    <label key={key} className={`splash-choice splash-choice--${key}`}>
                        <input type="radio" name="splash-template" value={key} checked={design.template === key} onChange={() => set('template', key)} />
                        <span className="splash-choice__sample" aria-hidden="true"><i /><b>Welcome</b><em>Connect to WiFi</em></span>
                        <strong>{name}</strong><small>{description}</small>
                    </label>
                ))}
            </div>
            {errors['branding_json.template'] && <p role="alert">{errors['branding_json.template']}</p>}
            <label>Accent colour <input type="color" value={design.accent || '#FFD300'} onChange={e => set('accent', e.target.value)} /></label>
            {[
                ['heading', 'Welcome heading', 'Welcome to your property', 100],
                ['message', 'Welcome message', 'Connect to the WiFi and stay in touch.', 240],
                ['button', 'Connect button', 'Connect to WiFi', 40],
            ].map(([key, label, placeholder, maxLength]) => (
                <label key={key}>{label}
                    <input type="text" value={design[key] || ''} maxLength={maxLength} placeholder={placeholder} onChange={e => set(key, e.target.value)} />
                    {errors[`branding_json.${key}`] && <small role="alert">{errors[`branding_json.${key}`]}</small>}
                </label>
            ))}
            <label>Property logo (PNG, JPEG or WebP, up to 2 MB)
                <input type="file" accept="image/png,image/jpeg,image/webp" onChange={e => onLogoChange(e.target.files[0] || null)} />
                {errors.logo && <small role="alert">{errors.logo}</small>}
            </label>
            {logoUrl && <img className="splash-editor__logo" src={logoUrl} alt="Saved property logo" />}
            {errors.splash_image && <p role="alert">{errors.splash_image}</p>}
            <p>Use the splash image below with any template. Text and images appear on the actual guest connection page.</p>
            {propertyId && <a href={route('host.properties.splash-preview', { property: propertyId, template: design.template })} target="_blank" rel="noopener noreferrer">Preview selected template with saved content ↗</a>}
        </fieldset>
    );
}
