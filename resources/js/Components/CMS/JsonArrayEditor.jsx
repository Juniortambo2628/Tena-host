import React, { useState } from 'react';
import IconPicker from './IconPicker';
import './JsonArrayEditor.css';

const DEFAULT_ITEM = { icon: 'fas fa-star', title: 'New Feature', desc: 'Description here' };

// Sign-up field types understood by SignupForm and SignupFormSchema.
const FIELD_TYPES = ['text', 'email', 'tel', 'number', 'select', 'radio', 'textarea', 'checkbox'];

const FIELD_HINTS = {
    feature: 'Feature-status key (Site-wide → Feature status) — shows "Coming soon" until live. Leave empty for none.',
    key: 'Stored as this key on the sign-up record. Use lowercase_with_underscores.',
    options: 'Comma-separated choices (for select / radio).',
};

const humanize = (key) => key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

function parse(value) {
    try {
        const items = typeof value === 'string' ? JSON.parse(value) : value;
        return Array.isArray(items) ? items : [];
    } catch {
        return [];
    }
}

/** Blank copy of an existing row so new rows have the same shape. */
function blankLike(item) {
    if (!item) return { ...DEFAULT_ITEM };
    return Object.fromEntries(Object.entries(item).map(([k, v]) => [
        k,
        Array.isArray(v) ? [] : typeof v === 'boolean' ? false : '',
    ]));
}

function FieldInput({ name, value, onChange }) {
    if (name === 'icon') {
        return <IconPicker value={value || ''} onChange={onChange} />;
    }
    if (typeof value === 'boolean' || name === 'required') {
        return (
            <label className="json-array-editor__checkbox">
                <input type="checkbox" checked={value === true || value === '1' || value === 'true'} onChange={(e) => onChange(e.target.checked)} />
                Yes
            </label>
        );
    }
    if (name === 'type') {
        return (
            <select value={value || 'text'} onChange={(e) => onChange(e.target.value)} className="json-array-editor__input">
                {FIELD_TYPES.map((t) => <option key={t} value={t}>{t}</option>)}
            </select>
        );
    }
    if (Array.isArray(value)) {
        return (
            <input
                key={value.join('|')}
                type="text"
                defaultValue={value.join(', ')}
                onBlur={(e) => onChange(e.target.value.split(',').map((o) => o.trim()).filter(Boolean))}
                className="json-array-editor__input"
            />
        );
    }
    if (name === 'desc' || name === 'description') {
        return <textarea value={value || ''} onChange={(e) => onChange(e.target.value)} rows={2} className="json-array-editor__textarea" />;
    }
    return <input type="text" value={value ?? ''} onChange={(e) => onChange(e.target.value)} className="json-array-editor__input" />;
}

/**
 * Edits a JSON-encoded list of objects (feature rows, sign-up fields, ...).
 * Renders one input per key found on the rows, so new row shapes need no
 * editor changes.
 */
export default function JsonArrayEditor({ label, value, onChange }) {
    const [localItems, setLocalItems] = useState(() => parse(value));

    const commit = (updated) => {
        setLocalItems(updated);
        onChange(JSON.stringify(updated));
    };

    const updateItem = (index, field, newValue) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: newValue };
        commit(updated);
    };

    const addItem = () => commit([...localItems, blankLike(localItems[0])]);
    const removeItem = (index) => commit(localItems.filter((_, i) => i !== index));
    const moveItem = (index, delta) => {
        const target = index + delta;
        if (target < 0 || target >= localItems.length) return;
        const updated = [...localItems];
        [updated[index], updated[target]] = [updated[target], updated[index]];
        commit(updated);
    };

    return (
        <div className="json-array-editor">
            <div className="json-array-editor__header">
                <label className="json-array-editor__label">{label}</label>
                <button type="button" onClick={addItem} className="json-array-editor__add">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                    </svg>
                    Add Item
                </button>
            </div>
            <div className="json-array-editor__items">
                {localItems.map((item, idx) => (
                    <div key={idx} className="json-array-editor__item">
                        <div className="json-array-editor__item-header">
                            <span className="json-array-editor__item-num">{idx + 1}</span>
                            <span className="json-array-editor__item-title">{item.title || item.label || item.key || 'Untitled'}</span>
                            <button type="button" onClick={() => moveItem(idx, -1)} className="json-array-editor__item-move" title="Move up" disabled={idx === 0}>↑</button>
                            <button type="button" onClick={() => moveItem(idx, 1)} className="json-array-editor__item-move" title="Move down" disabled={idx === localItems.length - 1}>↓</button>
                            <button type="button" onClick={() => removeItem(idx)} className="json-array-editor__item-remove" title="Remove">
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                        <div className="json-array-editor__item-fields">
                            {Object.entries(item).map(([field, fieldValue]) => (
                                <div key={field} className="json-array-editor__field">
                                    <label className="json-array-editor__field-label">{humanize(field)}</label>
                                    <FieldInput name={field} value={fieldValue} onChange={(v) => updateItem(idx, field, v)} />
                                    {FIELD_HINTS[field] && <p className="json-array-editor__hint">{FIELD_HINTS[field]}</p>}
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
                {localItems.length === 0 && (
                    <button type="button" onClick={addItem} className="json-array-editor__empty-add">
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                        </svg>
                        Add First Item
                    </button>
                )}
            </div>
        </div>
    );
}
