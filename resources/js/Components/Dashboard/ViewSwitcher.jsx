import React from 'react';
import { useTerms } from './Terms';
import './ViewSwitcher.css';

// Segmented tabs that switch a dashboard between views: views = [{ id, label, icon }].
export default function ViewSwitcher({ views, value, onChange, label = 'Dashboard view' }) {
    const t = useTerms();

    return (
        <div className="view-switcher" role="tablist" aria-label={label}>
            {views.map((view) => (
                <button
                    key={view.id}
                    type="button"
                    role="tab"
                    aria-selected={value === view.id}
                    onClick={() => onChange(view.id)}
                    className={`view-switcher__tab ${value === view.id ? 'view-switcher__tab--active' : ''}`}
                >
                    {view.icon}
                    {t(view.label)}
                </button>
            ))}
        </div>
    );
}
